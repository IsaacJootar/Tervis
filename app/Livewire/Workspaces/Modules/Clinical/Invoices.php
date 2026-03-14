<?php

namespace App\Livewire\Workspaces\Modules\Clinical;

use App\Models\Activity;
use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientPayment;
use App\Models\Registrations\DinActivation;
use App\Services\Billing\BillingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
class Invoices extends Component
{
  public $patientId;
  public $patient;

  public $patient_din, $first_name, $last_name, $middle_name, $patient_phone, $patient_dob, $patient_age, $patient_gender;
  public $facility_id, $facility_name, $state_id, $state_name, $lga_id, $lga_name, $ward_id, $ward_name;
  public $officer_name, $officer_role, $officer_designation;
  public $hasAccess = false, $accessError = '', $activation_time;

  public $selected_invoice_id;
  public $payment_date;
  public $payment_amount;
  public $payment_method = 'Cash';
  public $payment_notes;
  public $billingAvailable = false;

  protected $rules = [
    'selected_invoice_id' => 'required|integer',
    'payment_date' => 'required|date',
    'payment_amount' => 'required|numeric|min:0.01',
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

    $this->payment_date = now()->format('Y-m-d');
    $this->billingAvailable = $this->billingTablesReady();
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

  public function selectInvoice($invoiceId): void
  {
    $this->selected_invoice_id = (int) $invoiceId;
  }

  public function recordPayment(): void
  {
    if (!$this->billingAvailable) {
      toastr()->error('Billing tables are not available yet. Run migrations first.');
      return;
    }

    $this->resetErrorBag();
    $this->validate();

    $invoice = Invoice::query()
      ->where('id', $this->selected_invoice_id)
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->first();

    if (!$invoice) {
      $this->addError('selected_invoice_id', 'Selected invoice was not found.');
      toastr()->error('Selected invoice was not found.');
      return;
    }

    DB::beginTransaction();
    try {
      $billingService = app(BillingService::class);
      $invoice = $billingService->refreshInvoiceTotals($invoice);

      if ((float) $invoice->outstanding_amount <= 0 || $invoice->status === 'paid') {
        $this->addError('selected_invoice_id', 'Selected invoice is already fully paid.');
        throw new Exception('Selected invoice is already fully paid.');
      }

      $payAmount = (float) $this->payment_amount;
      if ($payAmount > (float) $invoice->outstanding_amount) {
        $this->addError('payment_amount', 'Amount exceeds invoice outstanding balance.');
        throw new Exception('Amount exceeds invoice outstanding balance.');
      }

      $payment = $billingService->createPaymentAndAllocate($invoice, [
        'payment_date' => $this->payment_date,
        'amount_received' => $payAmount,
        'payment_method' => $this->payment_method,
        'notes' => $this->payment_notes,
        'received_by' => $this->officer_name,
        'state_id' => $this->state_id,
        'lga_id' => $this->lga_id,
        'ward_id' => $this->ward_id,
      ]);

      if (!$payment) {
        throw new Exception('No payment was recorded. Invoice may already be fully paid.');
      }

      $invoice = $billingService->refreshInvoiceTotals($invoice);
      $this->selected_invoice_id = null;

      Activity::create([
        'patient_id' => $this->patientId,
        'facility_id' => $this->facility_id,
        'module' => 'invoices',
        'action' => 'payment',
        'description' => 'Payment ' . $payment->payment_code . ' recorded for invoice ' . $invoice->invoice_code . '.',
        'performed_by' => $this->officer_name,
      ]);

      DB::commit();

      $this->payment_amount = null;
      $this->payment_notes = null;
      toastr()->success('Payment recorded. Outstanding: ' . number_format((float) $invoice->outstanding_amount, 2));
    } catch (Exception $e) {
      DB::rollBack();
      report($e);
      if (!$this->getErrorBag()->has('payment_amount')) {
        toastr()->error('Unable to record payment.');
      }
    }
  }

  public function backToDashboard()
  {
    return redirect()->route('workspace-dashboard', ['patientId' => $this->patientId]);
  }

  public function render()
  {
    $zeroSummary = (object) [
      'total_billed' => 0,
      'total_paid' => 0,
      'total_outstanding' => 0,
    ];

    if (!$this->billingAvailable) {
      return view('livewire.workspaces.modules.clinical.invoices', [
        'invoices' => collect(),
        'selectedInvoice' => null,
        'payments' => collect(),
        'billingSummary' => $zeroSummary,
      ]);
    }

    $invoices = Invoice::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->withCount('lines')
      ->withSum('lines as lines_total_amount', 'line_amount')
      ->withSum('allocations as allocations_total_amount', 'amount_allocated')
      ->latest('invoice_date')
      ->latest('id')
      ->get()
      ->map(function ($invoice) {
        $total = (float) ($invoice->lines_total_amount ?? $invoice->total_amount ?? 0);
        $paid = (float) ($invoice->allocations_total_amount ?? $invoice->amount_paid ?? 0);
        $outstanding = max(0, $total - $paid);

        $status = 'draft';
        if ($total > 0 && $paid <= 0) {
          $status = 'unpaid';
        } elseif ($outstanding > 0 && $paid > 0) {
          $status = 'partially_paid';
        } elseif ($total > 0 && $outstanding <= 0) {
          $status = 'paid';
        }

        $invoice->setAttribute('total_amount', $total);
        $invoice->setAttribute('amount_paid', $paid);
        $invoice->setAttribute('outstanding_amount', $outstanding);
        $invoice->setAttribute('status', $status);

        return $invoice;
      })
      ->values();

    $payableInvoices = $invoices
      ->filter(fn($invoice) => (float) $invoice->outstanding_amount > 0 && in_array($invoice->status, ['draft', 'unpaid', 'partially_paid'], true))
      ->values();

    if ($payableInvoices->isNotEmpty()) {
      $selectedPayable = $payableInvoices->contains(fn($invoice) => (int) $invoice->id === (int) $this->selected_invoice_id);
      if (!$selectedPayable) {
        $this->selected_invoice_id = (int) $payableInvoices->first()->id;
      }
    } elseif (!$this->selected_invoice_id && $invoices->isNotEmpty()) {
      $this->selected_invoice_id = (int) $invoices->first()->id;
    }

    $selectedInvoice = null;
    if ($this->selected_invoice_id) {
      $selectedInvoice = Invoice::query()
        ->where('id', $this->selected_invoice_id)
        ->where('patient_id', $this->patientId)
        ->where('facility_id', $this->facility_id)
        ->with([
          'lines' => fn($q) => $q->orderBy('id', 'desc'),
          'allocations' => fn($q) => $q->with('payment')->latest('id'),
        ])
        ->withSum('lines as lines_total_amount', 'line_amount')
        ->withSum('allocations as allocations_total_amount', 'amount_allocated')
        ->first();

      if ($selectedInvoice) {
        $total = (float) ($selectedInvoice->lines_total_amount ?? $selectedInvoice->total_amount ?? 0);
        $paid = (float) ($selectedInvoice->allocations_total_amount ?? $selectedInvoice->amount_paid ?? 0);
        $outstanding = max(0, $total - $paid);

        $status = 'draft';
        if ($total > 0 && $paid <= 0) {
          $status = 'unpaid';
        } elseif ($outstanding > 0 && $paid > 0) {
          $status = 'partially_paid';
        } elseif ($total > 0 && $outstanding <= 0) {
          $status = 'paid';
        }

        $selectedInvoice->setAttribute('total_amount', $total);
        $selectedInvoice->setAttribute('amount_paid', $paid);
        $selectedInvoice->setAttribute('outstanding_amount', $outstanding);
        $selectedInvoice->setAttribute('status', $status);
      }
    }

    $payments = PatientPayment::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->latest('payment_date')
      ->latest('id')
      ->limit(20)
      ->get();

    $billingSummary = Invoice::query()
      ->where('patient_id', $this->patientId)
      ->where('facility_id', $this->facility_id)
      ->selectRaw('COALESCE(SUM(total_amount),0) as total_billed, COALESCE(SUM(amount_paid),0) as total_paid, COALESCE(SUM(outstanding_amount),0) as total_outstanding')
      ->first();

    return view('livewire.workspaces.modules.clinical.invoices', [
      'invoices' => $invoices,
      'payableInvoices' => $payableInvoices,
      'selectedInvoice' => $selectedInvoice,
      'payments' => $payments,
      'billingSummary' => $billingSummary ?? $zeroSummary,
    ]);
  }
}
