<?php

namespace App\Livewire\Core;

use App\Models\DrugCatalogItem;
use App\Models\DrugStockBatch;
use App\Models\DrugStockMovement;
use App\Models\Facility;
use App\Services\Pharmacy\DrugInventoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PharmacyOperations extends Component
{
  public $facility_id;
  public $facility_name;
  public $officer_name;

  public $stock_drug_catalog_item_id;
  public $stock_batch_number;
  public $stock_quantity_received;
  public $stock_received_date;
  public $stock_expiry_date;
  public $stock_supplier_name;
  public $stock_unit_cost;
  public $stock_notes;

  public $adjust_drug_catalog_item_id;
  public $adjust_mode = 'add';
  public $adjust_quantity;
  public $adjust_reason;

  public $reorderLevels = [];

  protected function stockInRules(): array
  {
    return [
      'stock_drug_catalog_item_id' => [
        'required',
        Rule::exists('drug_catalog_items', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'stock_batch_number' => 'nullable|string|max:120',
      'stock_quantity_received' => 'required|numeric|min:0.01',
      'stock_received_date' => 'required|date',
      'stock_expiry_date' => 'nullable|date',
      'stock_supplier_name' => 'nullable|string|max:160',
      'stock_unit_cost' => 'nullable|numeric|min:0',
      'stock_notes' => 'nullable|string|max:1000',
    ];
  }

  protected function adjustmentRules(): array
  {
    return [
      'adjust_drug_catalog_item_id' => [
        'required',
        Rule::exists('drug_catalog_items', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'adjust_mode' => ['required', Rule::in(['add', 'deduct'])],
      'adjust_quantity' => 'required|numeric|min:0.01',
      'adjust_reason' => 'required|string|max:1000',
    ];
  }

  protected $messages = [
    'stock_drug_catalog_item_id.required' => 'Select a drug for stock-in.',
    'stock_quantity_received.required' => 'Enter quantity received.',
    'adjust_drug_catalog_item_id.required' => 'Select a drug for adjustment.',
    'adjust_reason.required' => 'Adjustment reason is required.',
  ];

  public function mount(): void
  {
    $admin = Auth::user();
    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }

    $this->facility_id = (int) $admin->facility_id;
    $this->facility_name = Facility::find($this->facility_id)?->name ?? 'Unknown Facility';
    $this->officer_name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) ?: ($admin->full_name ?? 'Facility Admin');

    $this->stock_received_date = now()->toDateString();
  }

  public function saveStockIn(DrugInventoryService $inventoryService): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->stockInRules());

      if ($this->stock_expiry_date && $this->stock_expiry_date < $this->stock_received_date) {
        throw ValidationException::withMessages([
          'stock_expiry_date' => 'Expiry date cannot be earlier than received date.',
        ]);
      }

      $inventoryService->stockIn([
        'facility_id' => $this->facility_id,
        'drug_catalog_item_id' => (int) $this->stock_drug_catalog_item_id,
        'batch_number' => $this->stock_batch_number,
        'received_date' => $this->stock_received_date,
        'expiry_date' => $this->stock_expiry_date,
        'quantity_received' => (float) $this->stock_quantity_received,
        'unit_cost' => $this->stock_unit_cost,
        'supplier_name' => $this->stock_supplier_name,
        'notes' => $this->stock_notes,
        'moved_by' => $this->officer_name,
        'reference_type' => 'core_pharmacy_stock_in',
        'reference_code' => 'STKIN-' . now()->format('YmdHis'),
        'movement_note' => 'Stock-in via Pharmacy Operations.',
      ]);

      DB::commit();
      toastr()->success('Stock-in saved successfully.');
      $this->resetStockInForm();
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error('Unable to save stock-in.');
    }
  }

  public function applyAdjustment(DrugInventoryService $inventoryService): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->adjustmentRules());

      $inventoryService->adjustStock([
        'facility_id' => $this->facility_id,
        'drug_catalog_item_id' => (int) $this->adjust_drug_catalog_item_id,
        'mode' => $this->adjust_mode,
        'quantity' => (float) $this->adjust_quantity,
        'notes' => $this->adjust_reason,
        'moved_by' => $this->officer_name,
        'reference_type' => 'core_pharmacy_adjustment',
        'reference_code' => 'ADJ-' . now()->format('YmdHis'),
      ]);

      DB::commit();
      toastr()->success('Stock adjustment applied.');
      $this->resetAdjustmentForm();
      $this->dispatch('close-stock-adjustment-modal');
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      toastr()->error($e->getMessage() ?: 'Unable to apply stock adjustment.');
    }
  }

  public function useDrugForStockIn(int $drugId): void
  {
    $exists = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->where('id', $drugId)
      ->exists();

    if (!$exists) {
      toastr()->warning('Drug not found for this facility.');
      return;
    }

    $this->stock_drug_catalog_item_id = $drugId;
    $this->adjust_drug_catalog_item_id = $drugId;
    $this->dispatch('focus-stock-in-form');
  }

  public function updateReorderLevel(int $drugId): void
  {
    $value = isset($this->reorderLevels[$drugId]) ? (int) $this->reorderLevels[$drugId] : null;
    if ($value === null || $value < 0) {
      toastr()->error('Reorder level must be zero or greater.');
      return;
    }

    $drug = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->find($drugId);

    if (!$drug) {
      toastr()->error('Drug not found.');
      return;
    }

    $drug->update(['reorder_level' => $value]);
    toastr()->success('Reorder level updated.');
  }

  private function resetStockInForm(): void
  {
    $this->reset([
      'stock_batch_number',
      'stock_quantity_received',
      'stock_expiry_date',
      'stock_supplier_name',
      'stock_unit_cost',
      'stock_notes',
    ]);
    $this->stock_received_date = now()->toDateString();
  }

  private function resetAdjustmentForm(): void
  {
    $this->reset([
      'adjust_quantity',
      'adjust_reason',
    ]);
    $this->adjust_mode = 'add';
  }

  private function refreshPageSoon(int $delayMs = 1200): void
  {
    $this->js("setTimeout(() => window.location.reload(), {$delayMs})");
  }

  public function render()
  {
    $today = now()->toDateString();

    $inventoryRows = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->withSum([
        'stockBatches as available_stock' => function ($query) use ($today) {
          $query->where('is_active', true)
            ->where('quantity_available', '>', 0)
            ->where(function ($sub) use ($today) {
              $sub->whereNull('expiry_date')
                ->orWhereDate('expiry_date', '>=', $today);
            });
        }
      ], 'quantity_available')
      ->withSum([
        'stockBatches as expired_stock' => function ($query) use ($today) {
          $query->where('is_active', true)
            ->where('quantity_available', '>', 0)
            ->whereDate('expiry_date', '<', $today);
        }
      ], 'quantity_available')
      ->orderBy('drug_name')
      ->get();

    foreach ($inventoryRows as $row) {
      if (!array_key_exists($row->id, $this->reorderLevels)) {
        $this->reorderLevels[$row->id] = (int) ($row->reorder_level ?? 10);
      }
    }

    $summary = [
      'total_drugs' => $inventoryRows->count(),
      'in_stock' => $inventoryRows->filter(fn($row) => (float) ($row->available_stock ?? 0) > 0)->count(),
      'low_stock' => $inventoryRows->filter(function ($row) {
        $available = (float) ($row->available_stock ?? 0);
        $reorder = (int) ($row->reorder_level ?? 10);
        return $available > 0 && $available <= $reorder;
      })->count(),
      'out_of_stock' => $inventoryRows->filter(fn($row) => (float) ($row->available_stock ?? 0) <= 0)->count(),
      'expired_with_balance' => $inventoryRows->filter(fn($row) => (float) ($row->expired_stock ?? 0) > 0)->count(),
    ];

    $stockBatches = DrugStockBatch::query()
      ->where('facility_id', $this->facility_id)
      ->with('catalogItem:id,drug_name,formulation,strength')
      ->latest('received_date')
      ->latest('id')
      ->limit(1000)
      ->get();

    $stockMovements = DrugStockMovement::query()
      ->where('facility_id', $this->facility_id)
      ->with('catalogItem:id,drug_name')
      ->latest('moved_at')
      ->latest('id')
      ->limit(1000)
      ->get();

    $catalogOptions = DrugCatalogItem::query()
      ->where('facility_id', $this->facility_id)
      ->where('is_active', true)
      ->orderBy('drug_name')
      ->get(['id', 'drug_name', 'formulation', 'strength']);

    return view('livewire.core.pharmacy-operations', [
      'summary' => $summary,
      'inventoryRows' => $inventoryRows,
      'stockBatches' => $stockBatches,
      'stockMovements' => $stockMovements,
      'catalogOptions' => $catalogOptions,
    ])->layout('layouts.facilityAdminLayout');
  }
}
