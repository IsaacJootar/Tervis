<?php

namespace App\Livewire\Workspaces\Modules\Clinical;

use App\Models\Activity;
use App\Models\DrugCatalogItem;
use App\Models\DrugDispenseLine;
use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientPayment;
use App\Models\Prescription;
use App\Models\Registrations\DinActivation;
use App\Services\Billing\BillingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
class Prescriptions extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $dispensed_date;
  public $dispense_notes;
  public $selected_prescription_map = [];

  public $selected_catalog_id;
  public $selected_catalog_name;
  public $drug_search = '';
  public $entry_quantity = 1;
  public $cart = [];
  public $dispense_code = '';

  public $history_from_date;
  public $history_to_date;

  public $receipt_code;
  public $receipt_lines = [];
  public $receipt_date;

  public $charge_amount = 0;
  public $amount_paid_now = 0;
  public $payment_method = 'Cash';
  public $payment_notes;

  protected $rules = [
    'dispensed_date' => 'required|date',
    'dispense_notes' => 'nullable|string|max:1000',
    'selected_prescription_map' => 'nullable|array',
    'selected_prescription_map.*' => 'boolean',
    'selected_catalog_id' => 'required|integer',
    'drug_search' => 'nullable|string|max:150',
    'entry_quantity' => 'required|numeric|min:0.1',
    'history_from_date' => 'nullable|date',
    'history_to_date' => 'nullable|date',
    'charge_amount' => 'required|numeric|min:0',
    'amount_paid_now' => 'nullable|numeric|min:0',
    'payment_method' => 'nullable|string|max:60',
    'payment_notes' => 'nullable|string|max:1000',
  ];

  public function mount($patientId)
  {
    $this->patientId = $patientId;

    $user = Auth::user();
    if (!$user || $user->role !== 'Data Officer') {
      abort(403, 'Unauthorized: Only Data Officers can access this page.');
    }

    $facility = Facility::with(['stateRelation', 'lgaRelation', 'wardRelation'])->find($user->facility_id);
    if (!$facility) {
      abort(403, 'Invalid facility assignment.');
    }

    $this->facility_id = $facility->id;
    $this->facility_name = $facility->name;
    $this->state_id = $facility->state_id;
    $this->state_name = $facility->stateRelation?->name ?? $facility->state;
    $this->lga_id = $facility->lga_id;
    $this->lga_name = $facility->lgaRelation?->name ?? $facility->lga;
    $this->ward_id = $facility->ward_id;
    $this->ward_name = $facility->wardRelation?->name ?? $facility->ward;

    $this->officer_name = $user->first_name . ' ' . $user->last_name;
    $this->officer_role = $user->role;
    $this->officer_designation = $user->designation;

    $this->validatePatientAccess();
    if ($this->hasAccess) {
      $this->loadPatientData();
    }

    $this->dispensed_date = now()->format('Y-m-d');
    $this->history_from_date = now()->subDays(14)->format('Y-m-d');
    $this->history_to_date = now()->format('Y-m-d');
    $this->hydrateCartFromSession();
  }

  public function goToInvoices()
  {
    return redirect()->route('workspaces-invoices', ['patientId' => $this->patientId]);
  }

  private function billingTablesReady(): bool
  {
    return Schema::hasTable('invoices')
      && Schema::hasTable('invoice_lines')
      && Schema::hasTable('patient_payments')
      && Schema::hasTable('payment_allocations');
  }

  private function validatePatientAccess(): void
  {
    $patient = Patient::with(['facility'])->find($this->patientId);

    if (!$patient) {
      $this->hasAccess = false;
      $this->accessError = 'Patient not found in the system.';
      return;
    }

    $todaysActivation = DinActivation::where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->whereDate('visit_date', today())
      ->first();

    if (!$todaysActivation) {
      $this->hasAccess = false;
      $this->accessError = 'Patient has not been checked in today. Please activate DIN first.';
      return;
    }

    $this->hasAccess = true;
    $this->patient = $patient;
    $this->activation_time = Carbon::parse($todaysActivation->check_in_time)->format('h:i A');
  }

  private function loadPatientData(): void
  {
    if (!$this->patient) {
      return;
    }

    $this->patient_din = $this->patient->din;
    $this->first_name = $this->patient->first_name;
    $this->last_name = $this->patient->last_name;
    $this->middle_name = $this->patient->middle_name;
    $this->patient_phone = $this->patient->phone;
    $this->patient_dob = $this->patient->date_of_birth?->format('d M Y');
    $this->patient_gender = $this->patient->gender;
    $this->patient_age = $this->patient->date_of_birth ? $this->patient->date_of_birth->age : null;
  }

  private function cartSessionKey(): string
  {
    return 'prescriptions_cart_' . Auth::id() . '_' . $this->patientId;
  }

  private function codeSessionKey(): string
  {
    return 'prescriptions_code_' . Auth::id() . '_' . $this->patientId;
  }

  private function hydrateCartFromSession(): void
  {
    $this->cart = session($this->cartSessionKey(), []);
    $this->dispense_code = session($this->codeSessionKey(), '');
  }

  private function persistCartToSession(): void
  {
    session([
      $this->cartSessionKey() => $this->cart,
      $this->codeSessionKey() => $this->dispense_code,
    ]);
  }

  private function resetCartSession(): void
  {
    session()->forget([$this->cartSessionKey(), $this->codeSessionKey()]);
  }

  private function generateDispenseCode(): string
  {
    $randomCode = '';
    for ($i = 0; $i < 14; $i++) {
      $randomCode .= mt_rand(0, 9);
    }
    return $randomCode;
  }

  public function selectCatalogItem(int $id): void
  {
    $item = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->where('is_active', true)
      ->find($id);

    if (!$item) {
      $this->addError('selected_catalog_id', 'Selected drug was not found or is inactive.');
      return;
    }

    $this->selected_catalog_id = (int) $item->id;
    $this->selected_catalog_name = trim($item->drug_name . ' (' . ($item->formulation ?: 'N/A') . ', ' . ($item->strength ?: 'N/A') . ')');
    $this->resetErrorBag(['selected_catalog_id']);
  }

  public function clearCatalogSelection(): void
  {
    $this->selected_catalog_id = null;
    $this->selected_catalog_name = null;
    $this->drug_search = '';
  }

  public function addToCart(): void
  {
    $this->resetErrorBag(['selected_catalog_id', 'entry_quantity', 'checkout']);
    $this->validateOnly('selected_catalog_id');
    $this->validateOnly('entry_quantity');

    $item = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->where('is_active', true)
      ->find($this->selected_catalog_id);

    if (!$item) {
      $this->addError('selected_catalog_id', 'Select an active drug from Drug Catalog.');
      toastr()->error('Select an active drug from Drug Catalog.');
      return;
    }

    if (empty($this->dispense_code)) {
      $this->dispense_code = $this->generateDispenseCode();
    }

    $this->cart[] = [
      'cart_item_id' => Str::uuid()->toString(),
      'drug_catalog_item_id' => $item->id,
      'drug_name' => $item->drug_name,
      'quantity' => (float) $this->entry_quantity,
    ];

    $this->persistCartToSession();
    // Keep current filter and selected item after add for faster repeated entry.
    $this->entry_quantity = 1;
    toastr()->success($item->drug_name . ' added to cart.');
  }

  public function removeFromCart(string $cartItemId): void
  {
    $this->cart = array_values(array_filter(
      $this->cart,
      fn($item) => ($item['cart_item_id'] ?? null) !== $cartItemId
    ));

    if (count($this->cart) === 0) {
      $this->dispense_code = '';
      $this->resetCartSession();
    } else {
      $this->persistCartToSession();
    }
  }

  public function updateCartQuantity(string $cartItemId, $quantity): void
  {
    $quantity = (float) $quantity;
    if ($quantity < 0.1) {
      $quantity = 0.1;
    }

    foreach ($this->cart as &$item) {
      if (($item['cart_item_id'] ?? null) === $cartItemId) {
        $item['quantity'] = $quantity;
        break;
      }
    }
    unset($item);

    $this->persistCartToSession();
  }

  public function clearCart(): void
  {
    $this->cart = [];
    $this->dispense_code = '';
    $this->resetCartSession();
  }

  private function getPendingPrescriptionIds()
  {
    return Prescription::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->where('status', 'pending')
      ->pluck('id')
      ->map(fn($id) => (int) $id)
      ->values();
  }

  private function getSelectedPendingPrescriptionIds()
  {
    $pendingIds = $this->getPendingPrescriptionIds();
    if ($pendingIds->isEmpty()) {
      return collect();
    }

    $selected = collect((array) $this->selected_prescription_map)
      ->filter(fn($checked) => (bool) $checked)
      ->keys()
      ->filter(fn($id) => is_numeric($id))
      ->map(fn($id) => (int) $id)
      ->unique()
      ->values();

    return $selected->intersect($pendingIds)->values();
  }

  private function enforcePendingSelection(): void
  {
    if ($this->getPendingPrescriptionIds()->isEmpty()) {
      return;
    }

    if ($this->getSelectedPendingPrescriptionIds()->isEmpty()) {
      throw ValidationException::withMessages([
        'selected_prescription_map' => 'Select at least one pending prescription before checkout.',
      ]);
    }
  }

  public function checkoutDispensing(): void
  {
    $this->resetErrorBag(['selected_prescription_map', 'checkout', 'dispensed_date', 'dispense_notes', 'charge_amount', 'amount_paid_now']);

    $this->validateOnly('dispensed_date');
    $this->validateOnly('dispense_notes');
    $this->validateOnly('charge_amount');
    $this->validateOnly('amount_paid_now');

    if (count($this->cart) === 0) {
      $this->addError('checkout', 'Cart is empty. Add at least one drug item.');
      toastr()->error('Cart is empty.');
      return;
    }

    $this->enforcePendingSelection();

    DB::beginTransaction();
    try {
      if (empty($this->dispense_code)) {
        $this->dispense_code = $this->generateDispenseCode();
      }

      $selectedIds = $this->getSelectedPendingPrescriptionIds()->all();

      $pendingRecords = Prescription::query()
        ->where('patient_id', $this->patientId)
        ->where('facility_id', $this->facility_id)
        ->where('status', 'pending')
        ->whereIn('id', $selectedIds)
        ->get();

      $pendingPool = [];
      foreach ($pendingRecords as $record) {
        $key = strtolower(trim((string) $record->drug_name));
        $pendingPool[$key] = $pendingPool[$key] ?? [];
        $pendingPool[$key][] = (int) $record->id;
      }

      $matchedQtyByPrescription = [];
      foreach ($this->cart as $line) {
        $key = strtolower(trim((string) ($line['drug_name'] ?? '')));
        $matchedPrescriptionId = null;
        if (isset($pendingPool[$key]) && count($pendingPool[$key]) > 0) {
          $matchedPrescriptionId = array_shift($pendingPool[$key]);
          $matchedQtyByPrescription[$matchedPrescriptionId] = ($matchedQtyByPrescription[$matchedPrescriptionId] ?? 0) + (float) ($line['quantity'] ?? 0);
        }

        DrugDispenseLine::create([
          'patient_id' => $this->patientId,
          'facility_id' => $this->facility_id,
          'state_id' => $this->state_id,
          'lga_id' => $this->lga_id,
          'ward_id' => $this->ward_id,
          'drug_catalog_item_id' => $line['drug_catalog_item_id'] ?? null,
          'prescription_id' => $matchedPrescriptionId,
          'month_year' => Carbon::parse($this->dispensed_date)->startOfMonth()->format('Y-m-d'),
          'dispensed_date' => $this->dispensed_date,
          'dispense_code' => $this->dispense_code,
          'drug_name' => $line['drug_name'],
          'quantity' => (float) ($line['quantity'] ?? 0),
          'dispense_notes' => $this->dispense_notes,
          'dispensed_by' => $this->officer_name,
        ]);
      }

      foreach ($pendingRecords as $record) {
        $record->update([
          'status' => 'dispensed',
          'quantity_dispensed' => $matchedQtyByPrescription[$record->id] ?? $record->quantity_prescribed,
          'dispensed_by' => $this->officer_name,
          'dispensed_date' => $this->dispensed_date,
          'dispense_notes' => $this->dispense_notes,
        ]);
      }

      $lineCount = count($this->cart);
      $chargeAmount = (float) ($this->charge_amount ?? 0);
      $paidNow = (float) ($this->amount_paid_now ?? 0);
      $payment = null;
      $invoice = null;
      $billingNotice = null;

      if ($this->billingTablesReady()) {
        $billingService = app(BillingService::class);
        $invoice = $billingService->findOrCreateOpenInvoice([
          'patient_id' => $this->patientId,
          'facility_id' => $this->facility_id,
          'state_id' => $this->state_id,
          'lga_id' => $this->lga_id,
          'ward_id' => $this->ward_id,
          'created_by' => $this->officer_name,
        ], $this->dispensed_date, 'Auto-generated from module activities.');

        $billingService->addInvoiceLine($invoice, [
          'module' => 'prescriptions',
          'reference_type' => DrugDispenseLine::class,
          'reference_code' => $this->dispense_code,
          'description' => 'Drug issuance (' . $lineCount . ' line item(s), code: ' . $this->dispense_code . ')',
          'quantity' => 1,
          'unit_price' => $chargeAmount,
          'line_amount' => $chargeAmount,
          'service_date' => $this->dispensed_date,
          'created_by' => $this->officer_name,
        ]);

        $invoice = $billingService->refreshInvoiceTotals($invoice);

        if ($paidNow > (float) $invoice->outstanding_amount) {
          throw ValidationException::withMessages([
            'amount_paid_now' => 'Amount paid cannot exceed invoice outstanding balance (' . number_format((float) $invoice->outstanding_amount, 2) . ').',
          ]);
        }

        if ($paidNow > 0) {
          $payment = $billingService->createPaymentAndAllocate($invoice, [
            'payment_date' => $this->dispensed_date,
            'amount_received' => $paidNow,
            'payment_method' => $this->payment_method ?: 'Cash',
            'notes' => $this->payment_notes,
            'received_by' => $this->officer_name,
            'state_id' => $this->state_id,
            'lga_id' => $this->lga_id,
            'ward_id' => $this->ward_id,
          ]);
        }

        $invoice = $billingService->refreshInvoiceTotals($invoice);
      } else {
        $billingNotice = 'Billing tables are not available yet. Dispensing was saved without invoice entry.';
      }

      DB::commit();

      $code = $this->dispense_code;
      $this->logActivity('dispense', 'Dispensed ' . $lineCount . ' line item(s) with code ' . $code . '.');
      $this->openReceipt($code);
      $this->clearCart();
      $this->selected_prescription_map = [];
      $this->dispense_notes = null;
      $this->charge_amount = 0;
      $this->amount_paid_now = 0;
      $this->payment_method = 'Cash';
      $this->payment_notes = null;

      if ($invoice) {
        $message = 'Dispensing checkout completed. Invoice ' . $invoice->invoice_code . ' outstanding: ' . number_format((float) $invoice->outstanding_amount, 2) . '.';
        if ($payment) {
          $message .= ' Payment received: ' . number_format((float) $payment->amount_received, 2) . '.';
        }
      } else {
        $message = 'Dispensing checkout completed.';
      }
      toastr()->success($message);
      if ($billingNotice) {
        toastr()->warning($billingNotice);
      }
    } catch (ValidationException $e) {
      DB::rollBack();
      $this->setErrorBag($e->validator->errors());
      if ($e->validator->errors()->has('amount_paid_now')) {
        toastr()->error($e->validator->errors()->first('amount_paid_now'));
      }
    } catch (Exception $e) {
      DB::rollBack();
      report($e);
      $this->addError('checkout', 'An error occurred during checkout. Please try again.');
      toastr()->error('An error occurred during checkout.');
    }
  }

  public function openReceipt(string $code): void
  {
    $lines = DrugDispenseLine::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->where('dispense_code', $code)
      ->orderBy('id')
      ->get();

    $this->receipt_code = $code;
    $this->receipt_lines = $lines->toArray();
    $this->receipt_date = optional($lines->first())->dispensed_date?->format('Y-m-d');
  }

  public function closeReceipt(): void
  {
    $this->receipt_code = null;
    $this->receipt_lines = [];
    $this->receipt_date = null;
  }

  public function printReceipt(): void
  {
    $this->dispatch('print-drug-receipt');
  }

  public function cancelPending($id): void
  {
    $record = Prescription::where('facility_id', $this->facility_id)
      ->where('patient_id', $this->patientId)
      ->where('status', 'pending')
      ->findOrFail($id);

    $record->update([
      'status' => 'cancelled',
      'dispense_notes' => 'Cancelled by pharmacy desk.',
    ]);
    unset($this->selected_prescription_map[$record->id]);

    $this->logActivity('cancel', 'Cancelled pending prescription: ' . $record->drug_name);
    toastr()->success('Pending prescription cancelled.');
  }

  private function logActivity(string $action, string $description): void
  {
    try {
      Activity::create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'prescriptions',
        'action' => $action,
        'description' => $description,
        'performed_by' => $this->officer_name,
      ]);
    } catch (Exception $e) {
      // Ignore logging errors in workflow.
    }
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $pendingPrescriptions = Prescription::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->where('status', 'pending')
      ->latest('prescribed_date')
      ->latest('id')
      ->get();

    $pendingIds = $pendingPrescriptions->pluck('id')->map(fn($id) => (string) $id)->all();
    $this->selected_prescription_map = collect((array) $this->selected_prescription_map)
      ->filter(fn($checked, $id) => (bool) $checked && in_array((string) $id, $pendingIds, true))
      ->map(fn() => true)
      ->toArray();

    $catalogSearch = trim((string) $this->drug_search);
    $catalogSearchResults = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->where('is_active', true)
      ->when($catalogSearch !== '', function ($query) use ($catalogSearch) {
        $query->where(function ($sub) use ($catalogSearch) {
          $like = '%' . $catalogSearch . '%';
          $sub->where('drug_name', 'like', $like)
            ->orWhere('formulation', 'like', $like)
            ->orWhere('strength', 'like', $like)
            ->orWhere('route', 'like', $like);
        });
      })
      ->orderBy('drug_name')
      ->limit($catalogSearch === '' ? 15 : 30)
      ->get();

    $historyQuery = DrugDispenseLine::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id);

    if ($this->history_from_date) {
      $historyQuery->whereDate('dispensed_date', '>=', $this->history_from_date);
    }
    if ($this->history_to_date) {
      $historyQuery->whereDate('dispensed_date', '<=', $this->history_to_date);
    }

    $dispenseBatches = (clone $historyQuery)
      ->select([
        'dispense_code',
        'dispensed_date',
        DB::raw('COUNT(*) as lines_count'),
        DB::raw('SUM(quantity) as total_quantity'),
      ])
      ->groupBy('dispense_code', 'dispensed_date')
      ->orderByDesc('dispensed_date')
      ->orderByDesc(DB::raw('MAX(id)'))
      ->get();

    $billingSummary = (object) [
      'total_billed' => 0,
      'total_paid' => 0,
      'total_outstanding' => 0,
    ];
    $recentPayments = collect();

    if ($this->billingTablesReady()) {
      $billingSummary = Invoice::query()
        ->where('patient_id', $this->patientId)
        ->where('facility_id', $this->facility_id)
        ->selectRaw('COALESCE(SUM(total_amount),0) as total_billed, COALESCE(SUM(amount_paid),0) as total_paid, COALESCE(SUM(outstanding_amount),0) as total_outstanding')
        ->first();

      $recentPayments = PatientPayment::query()
        ->where('patient_id', $this->patientId)
        ->where('facility_id', $this->facility_id)
        ->latest('payment_date')
        ->latest('id')
        ->limit(5)
        ->get();
    }

    return view('livewire.workspaces.modules.clinical.prescriptions', [
      'pendingPrescriptions' => $pendingPrescriptions,
      'catalogSearchResults' => $catalogSearchResults,
      'dispenseBatches' => $dispenseBatches,
      'billingSummary' => $billingSummary,
      'recentPayments' => $recentPayments,
    ]);
  }
}
