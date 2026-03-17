<?php

namespace App\Livewire\Core;

use App\Models\Facility;
use App\Models\LabEquipmentLog;
use App\Models\LabProcessingBatch;
use App\Models\LabQcLog;
use App\Models\LabReagentMovement;
use App\Models\LabReagentStock;
use App\Models\LabSample;
use App\Models\LabTestOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LaboratoryOperations extends Component
{
  public const SAMPLE_STATUS_OPTIONS = ['received', 'processing', 'ready_for_result', 'reported', 'rejected'];
  public const BATCH_STATUS_OPTIONS = ['scheduled', 'running', 'completed', 'cancelled'];
  public const QC_TYPE_OPTIONS = ['internal', 'external'];
  public const QC_STATUS_OPTIONS = ['pass', 'fail', 'warning'];
  public const EQUIPMENT_LOG_TYPE_OPTIONS = ['calibration', 'maintenance', 'verification'];
  public const EQUIPMENT_RESULT_OPTIONS = ['pass', 'fail', 'pending'];

  public $facility_id;
  public $facility_name;
  public $officer_name;

  public $sample_id;
  public $sample_lab_test_order_id;
  public $sample_accession_no;
  public $sample_test_name;
  public $sample_specimen_type;
  public $sample_status = 'received';
  public $sample_collected_at;
  public $sample_received_at;
  public $sample_processing_batch_id;
  public $sample_remarks;

  public $batch_code;
  public $batch_test_profile;
  public $batch_analyzer_name;
  public $batch_run_date;
  public $batch_status = 'scheduled';
  public $batch_notes;

  public $assign_sample_id;
  public $assign_batch_id;

  public $qc_date;
  public $qc_type = 'internal';
  public $qc_test_profile;
  public $qc_control_level;
  public $qc_expected_range;
  public $qc_observed_value;
  public $qc_status = 'pass';
  public $qc_reviewed_by;
  public $qc_remarks;

  public $reagent_name;
  public $reagent_lot_number;
  public $reagent_unit = 'units';
  public $reagent_quantity_received;
  public $reagent_reorder_level = 0;
  public $reagent_expiry_date;
  public $reagent_manufacturer;
  public $reagent_notes;

  public $adjust_reagent_stock_id;
  public $adjust_mode = 'add';
  public $adjust_quantity;
  public $adjust_notes;

  public $reorderLevels = [];

  public $equipment_name;
  public $equipment_code;
  public $equipment_log_type = 'calibration';
  public $equipment_performed_date;
  public $equipment_next_due_date;
  public $equipment_result_status = 'pending';
  public $equipment_performed_by;
  public $equipment_notes;

  public function mount(): void
  {
    $admin = Auth::user();
    if (!$admin || $admin->role !== 'Facility Administrator') {
      abort(403, 'Unauthorized: Only Facility Administrators can access this page.');
    }

    $this->facility_id = (int) $admin->facility_id;
    $this->facility_name = Facility::find($this->facility_id)?->name ?? 'Unknown Facility';
    $this->officer_name = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? '')) ?: ($admin->full_name ?? 'Facility Admin');

    $this->sample_received_at = now()->format('Y-m-d\TH:i');
    $this->batch_run_date = now()->toDateString();
    $this->qc_date = now()->toDateString();
    $this->qc_reviewed_by = $this->officer_name;
    $this->equipment_performed_date = now()->toDateString();
    $this->equipment_performed_by = $this->officer_name;
  }

  protected function sampleIntakeRules(): array
  {
    return [
      'sample_lab_test_order_id' => [
        'nullable',
        Rule::exists('lab_test_orders', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'sample_accession_no' => 'nullable|string|max:80',
      'sample_test_name' => 'required|string|max:160',
      'sample_specimen_type' => 'nullable|string|max:120',
      'sample_status' => ['required', Rule::in(self::SAMPLE_STATUS_OPTIONS)],
      'sample_collected_at' => 'nullable|date',
      'sample_received_at' => 'required|date',
      'sample_processing_batch_id' => [
        'nullable',
        Rule::exists('lab_processing_batches', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'sample_remarks' => 'nullable|string|max:1000',
    ];
  }

  protected function batchRules(): array
  {
    return [
      'batch_code' => 'nullable|string|max:80',
      'batch_test_profile' => 'nullable|string|max:160',
      'batch_analyzer_name' => 'nullable|string|max:160',
      'batch_run_date' => 'required|date',
      'batch_status' => ['required', Rule::in(self::BATCH_STATUS_OPTIONS)],
      'batch_notes' => 'nullable|string|max:1000',
    ];
  }

  protected function qcRules(): array
  {
    return [
      'qc_date' => 'required|date',
      'qc_type' => ['required', Rule::in(self::QC_TYPE_OPTIONS)],
      'qc_test_profile' => 'required|string|max:160',
      'qc_control_level' => 'nullable|string|max:80',
      'qc_expected_range' => 'nullable|string|max:120',
      'qc_observed_value' => 'nullable|string|max:120',
      'qc_status' => ['required', Rule::in(self::QC_STATUS_OPTIONS)],
      'qc_reviewed_by' => 'nullable|string|max:160',
      'qc_remarks' => 'nullable|string|max:1000',
    ];
  }

  protected function reagentStockInRules(): array
  {
    return [
      'reagent_name' => 'required|string|max:160',
      'reagent_lot_number' => 'nullable|string|max:120',
      'reagent_unit' => 'required|string|max:60',
      'reagent_quantity_received' => 'required|numeric|min:0.01',
      'reagent_reorder_level' => 'nullable|numeric|min:0',
      'reagent_expiry_date' => 'nullable|date',
      'reagent_manufacturer' => 'nullable|string|max:160',
      'reagent_notes' => 'nullable|string|max:1000',
    ];
  }

  protected function reagentAdjustmentRules(): array
  {
    return [
      'adjust_reagent_stock_id' => [
        'required',
        Rule::exists('lab_reagent_stocks', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'adjust_mode' => ['required', Rule::in(['add', 'deduct'])],
      'adjust_quantity' => 'required|numeric|min:0.01',
      'adjust_notes' => 'required|string|max:1000',
    ];
  }

  protected function equipmentRules(): array
  {
    return [
      'equipment_name' => 'required|string|max:160',
      'equipment_code' => 'nullable|string|max:120',
      'equipment_log_type' => ['required', Rule::in(self::EQUIPMENT_LOG_TYPE_OPTIONS)],
      'equipment_performed_date' => 'required|date',
      'equipment_next_due_date' => 'nullable|date',
      'equipment_result_status' => ['required', Rule::in(self::EQUIPMENT_RESULT_OPTIONS)],
      'equipment_performed_by' => 'nullable|string|max:160',
      'equipment_notes' => 'nullable|string|max:1000',
    ];
  }

  protected function assignBatchRules(): array
  {
    return [
      'assign_sample_id' => [
        'required',
        Rule::exists('lab_samples', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
      'assign_batch_id' => [
        'required',
        Rule::exists('lab_processing_batches', 'id')->where(function ($query) {
          $query->where('facility_id', $this->facility_id);
        }),
      ],
    ];
  }

  private function refreshPageSoon(int $delayMs = 900): void
  {
    $this->js("setTimeout(() => window.location.reload(), {$delayMs})");
  }

  private function generateAccessionNo(): string
  {
    for ($i = 0; $i < 20; $i++) {
      $code = 'LAB-' . str_pad((string) $this->facility_id, 3, '0', STR_PAD_LEFT) . '-' . now()->format('ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
      $exists = LabSample::query()->where('accession_no', $code)->exists();
      if (!$exists) {
        return $code;
      }
    }

    throw ValidationException::withMessages([
      'sample_accession_no' => 'Unable to auto-generate unique accession number. Try manual accession number.',
    ]);
  }

  private function generateBatchCode(): string
  {
    for ($i = 0; $i < 20; $i++) {
      $code = 'LB-' . str_pad((string) $this->facility_id, 3, '0', STR_PAD_LEFT) . '-' . now()->format('ymd-His') . '-' . str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
      $exists = LabProcessingBatch::query()->where('batch_code', $code)->exists();
      if (!$exists) {
        return $code;
      }
    }

    throw ValidationException::withMessages([
      'batch_code' => 'Unable to auto-generate unique batch code. Try manual batch code.',
    ]);
  }

  private function syncBatchSampleCount(?int $batchId): void
  {
    if (!$batchId) {
      return;
    }

    $batch = LabProcessingBatch::query()
      ->where('facility_id', $this->facility_id)
      ->find($batchId);

    if (!$batch) {
      return;
    }

    $count = LabSample::query()
      ->where('facility_id', $this->facility_id)
      ->where('processing_batch_id', $batchId)
      ->count();

    $batch->sample_count = $count;
    if ($batch->status === 'scheduled' && $count > 0) {
      $batch->status = 'running';
    }
    $batch->save();
  }

  public function preloadSampleFromOrder(int $orderId): void
  {
    $order = LabTestOrder::query()
      ->where('facility_id', $this->facility_id)
      ->where('status', 'pending')
      ->find($orderId);

    if (!$order) {
      toastr()->warning('Pending order not found.');
      return;
    }

    $this->sample_id = null;
    $this->sample_lab_test_order_id = (int) $order->id;
    $this->sample_test_name = $order->test_name;
    $this->sample_specimen_type = $order->specimen;
    $this->sample_status = 'received';
    $this->sample_processing_batch_id = null;
    $this->sample_remarks = $order->instructions;
    if (!$this->sample_accession_no) {
      $this->sample_accession_no = $this->generateAccessionNo();
    }
    $this->dispatch('focus-lab-sample-intake');
  }

  public function clearSampleIntakeForm(): void
  {
    $this->reset([
      'sample_id',
      'sample_lab_test_order_id',
      'sample_accession_no',
      'sample_test_name',
      'sample_specimen_type',
      'sample_collected_at',
      'sample_processing_batch_id',
      'sample_remarks',
    ]);
    $this->sample_status = 'received';
    $this->sample_received_at = now()->format('Y-m-d\TH:i');
  }

  public function saveSampleIntake(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->sampleIntakeRules());

      if (empty($this->sample_accession_no)) {
        $this->sample_accession_no = $this->generateAccessionNo();
      }

      if ($this->sample_status === 'processing' && !$this->sample_processing_batch_id) {
        throw ValidationException::withMessages([
          'sample_processing_batch_id' => 'Select processing batch when sample status is Processing.',
        ]);
      }

      $order = null;
      if ($this->sample_lab_test_order_id) {
        $order = LabTestOrder::query()
          ->where('facility_id', $this->facility_id)
          ->find($this->sample_lab_test_order_id);

        if (!$order) {
          throw ValidationException::withMessages([
            'sample_lab_test_order_id' => 'Selected order was not found.',
          ]);
        }
      }

      $sample = null;
      if ($this->sample_id) {
        $sample = LabSample::query()
          ->where('facility_id', $this->facility_id)
          ->find($this->sample_id);
      } elseif ($this->sample_lab_test_order_id) {
        $sample = LabSample::query()
          ->where('facility_id', $this->facility_id)
          ->where('lab_test_order_id', $this->sample_lab_test_order_id)
          ->first();
      }

      if (!$sample) {
        $sample = new LabSample();
        $sample->facility_id = $this->facility_id;
      }

      $oldBatchId = $sample->processing_batch_id ? (int) $sample->processing_batch_id : null;

      $sample->patient_id = $order?->patient_id;
      $sample->lab_test_order_id = $this->sample_lab_test_order_id ?: null;
      $sample->processing_batch_id = $this->sample_processing_batch_id ?: null;
      $sample->accession_no = $this->sample_accession_no;
      $sample->test_name = $this->sample_test_name;
      $sample->specimen_type = $this->sample_specimen_type;
      $sample->sample_status = $this->sample_status;
      $sample->collected_at = $this->sample_collected_at ? date('Y-m-d H:i:s', strtotime($this->sample_collected_at)) : null;
      $sample->received_at = date('Y-m-d H:i:s', strtotime($this->sample_received_at));
      $sample->received_by = $this->officer_name;
      $sample->remarks = $this->sample_remarks;
      $sample->save();

      if ($oldBatchId && $oldBatchId !== (int) $sample->processing_batch_id) {
        $this->syncBatchSampleCount($oldBatchId);
      }
      $this->syncBatchSampleCount($sample->processing_batch_id ? (int) $sample->processing_batch_id : null);

      DB::commit();
      toastr()->success('Sample intake saved successfully.');
      $this->clearSampleIntakeForm();
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to save sample intake.');
    }
  }

  public function clearBatchForm(): void
  {
    $this->reset([
      'batch_code',
      'batch_test_profile',
      'batch_analyzer_name',
      'batch_notes',
    ]);
    $this->batch_status = 'scheduled';
    $this->batch_run_date = now()->toDateString();
  }

  public function saveProcessingBatch(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->batchRules());

      $batchCode = $this->batch_code ?: $this->generateBatchCode();

      LabProcessingBatch::create([
        'facility_id' => $this->facility_id,
        'batch_code' => $batchCode,
        'test_profile' => $this->batch_test_profile,
        'analyzer_name' => $this->batch_analyzer_name,
        'run_date' => $this->batch_run_date,
        'status' => $this->batch_status,
        'sample_count' => 0,
        'notes' => $this->batch_notes,
        'created_by' => $this->officer_name,
      ]);

      DB::commit();
      toastr()->success('Processing batch created.');
      $this->clearBatchForm();
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to create processing batch.');
    }
  }

  public function updatedSampleLabTestOrderId($orderId): void
  {
    if (!$orderId) {
      return;
    }

    $this->preloadSampleFromOrder((int) $orderId);
  }

  public function updatedAssignSampleId($sampleId): void
  {
    if (!$sampleId) {
      $this->assign_batch_id = null;
      return;
    }

    $sample = LabSample::query()
      ->where('facility_id', $this->facility_id)
      ->find((int) $sampleId);

    if (!$sample) {
      $this->assign_batch_id = null;
      return;
    }

    $this->assign_batch_id = $sample->processing_batch_id
      ?: LabProcessingBatch::query()
      ->where('facility_id', $this->facility_id)
      ->whereIn('status', ['scheduled', 'running'])
      ->orderByDesc('run_date')
      ->value('id');
  }

  public function openAssignBatchModal(?int $sampleId = null): void
  {
    if (!$sampleId) {
      $sampleId = LabSample::query()
        ->where('facility_id', $this->facility_id)
        ->latest('received_at')
        ->latest('id')
        ->value('id');
    }

    if (!$sampleId) {
      $this->assign_sample_id = null;
      $this->assign_batch_id = null;
      toastr()->warning('No samples available yet. Save sample intake first.');
      $this->dispatch('open-lab-sample-batch-modal');
      return;
    }

    $sample = LabSample::query()
      ->where('facility_id', $this->facility_id)
      ->find($sampleId);

    if (!$sample) {
      toastr()->warning('Sample was not found.');
      return;
    }

    $this->assign_sample_id = (int) $sample->id;
    $this->assign_batch_id = $sample->processing_batch_id
      ?: LabProcessingBatch::query()
      ->where('facility_id', $this->facility_id)
      ->whereIn('status', ['scheduled', 'running'])
      ->orderByDesc('run_date')
      ->value('id');

    $this->dispatch('open-lab-sample-batch-modal');
  }

  public function assignSelectedSampleToBatch(): void
  {
    $this->validate($this->assignBatchRules());
    $this->assignSampleToBatch((int) $this->assign_sample_id, (int) $this->assign_batch_id);
  }

  public function assignSampleToBatch(int $sampleId, int $batchId): void
  {
    DB::beginTransaction();
    try {
      $sample = LabSample::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($sampleId);

      $batch = LabProcessingBatch::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($batchId);

      $oldBatchId = $sample->processing_batch_id ? (int) $sample->processing_batch_id : null;
      $sample->processing_batch_id = $batch->id;
      if ($sample->sample_status !== 'rejected' && $sample->sample_status !== 'reported') {
        $sample->sample_status = 'processing';
      }
      $sample->save();

      if ($batch->status === 'scheduled') {
        $batch->status = 'running';
        $batch->save();
      }

      if ($oldBatchId && $oldBatchId !== (int) $batch->id) {
        $this->syncBatchSampleCount($oldBatchId);
      }
      $this->syncBatchSampleCount((int) $batch->id);

      DB::commit();
      toastr()->success('Sample assigned to processing batch.');
      $this->dispatch('close-lab-sample-batch-modal');
      $this->refreshPageSoon();
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to assign sample to batch.');
    }
  }

  public function markBatchCompleted(int $batchId): void
  {
    DB::beginTransaction();
    try {
      $batch = LabProcessingBatch::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($batchId);

      $batch->status = 'completed';
      $batch->completed_by = $this->officer_name;
      $batch->completed_at = now();
      $batch->save();

      LabSample::query()
        ->where('facility_id', $this->facility_id)
        ->where('processing_batch_id', $batch->id)
        ->whereIn('sample_status', ['received', 'processing'])
        ->update(['sample_status' => 'ready_for_result']);

      $this->syncBatchSampleCount($batch->id);

      DB::commit();
      toastr()->success('Batch marked completed. Samples moved to Ready for Result.');
      $this->refreshPageSoon();
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to complete batch.');
    }
  }

  public function clearQcForm(): void
  {
    $this->reset([
      'qc_test_profile',
      'qc_control_level',
      'qc_expected_range',
      'qc_observed_value',
      'qc_remarks',
    ]);
    $this->qc_type = 'internal';
    $this->qc_status = 'pass';
    $this->qc_date = now()->toDateString();
    $this->qc_reviewed_by = $this->officer_name;
  }

  public function saveQcLog(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->qcRules());

      LabQcLog::create([
        'facility_id' => $this->facility_id,
        'qc_date' => $this->qc_date,
        'qc_type' => $this->qc_type,
        'test_profile' => $this->qc_test_profile,
        'control_level' => $this->qc_control_level,
        'expected_range' => $this->qc_expected_range,
        'observed_value' => $this->qc_observed_value,
        'status' => $this->qc_status,
        'reviewed_by' => $this->qc_reviewed_by ?: $this->officer_name,
        'remarks' => $this->qc_remarks,
      ]);

      DB::commit();
      toastr()->success('QC log saved successfully.');
      $this->clearQcForm();
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to save QC log.');
    }
  }

  public function clearReagentStockInForm(): void
  {
    $this->reset([
      'reagent_name',
      'reagent_lot_number',
      'reagent_quantity_received',
      'reagent_expiry_date',
      'reagent_manufacturer',
      'reagent_notes',
    ]);
    $this->reagent_unit = 'units';
    $this->reagent_reorder_level = 0;
  }

  public function saveReagentStockIn(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->reagentStockInRules());

      $normalizedName = trim((string) $this->reagent_name);
      $lotNumber = $this->reagent_lot_number ?: null;
      $expiryDate = $this->reagent_expiry_date ?: null;
      $manufacturer = $this->reagent_manufacturer ?: null;
      $unit = trim((string) $this->reagent_unit);
      $quantityIn = (float) $this->reagent_quantity_received;
      $reorderLevel = (float) ($this->reagent_reorder_level ?? 0);

      $stock = LabReagentStock::query()
        ->where('facility_id', $this->facility_id)
        ->whereRaw('LOWER(reagent_name) = ?', [strtolower($normalizedName)])
        ->where('lot_number', $lotNumber)
        ->where('expiry_date', $expiryDate)
        ->where('manufacturer', $manufacturer)
        ->where('unit', $unit)
        ->where('is_active', true)
        ->first();

      if ($stock) {
        $stock->quantity_available = (float) $stock->quantity_available + $quantityIn;
        $stock->reorder_level = $reorderLevel;
        $stock->notes = $this->reagent_notes ?: $stock->notes;
        $stock->save();
      } else {
        $stock = LabReagentStock::create([
          'facility_id' => $this->facility_id,
          'reagent_name' => $normalizedName,
          'lot_number' => $lotNumber,
          'unit' => $unit,
          'quantity_available' => $quantityIn,
          'reorder_level' => $reorderLevel,
          'expiry_date' => $expiryDate,
          'manufacturer' => $manufacturer,
          'is_active' => true,
          'notes' => $this->reagent_notes,
        ]);
      }

      LabReagentMovement::create([
        'facility_id' => $this->facility_id,
        'lab_reagent_stock_id' => $stock->id,
        'movement_type' => 'stock_in',
        'quantity' => $quantityIn,
        'balance_after' => (float) $stock->quantity_available,
        'moved_at' => now(),
        'moved_by' => $this->officer_name,
        'reference_code' => 'LBR-STKIN-' . now()->format('YmdHis'),
        'notes' => 'Stock-in via Laboratory Operations.',
      ]);

      DB::commit();
      toastr()->success('Reagent stock-in saved successfully.');
      $this->clearReagentStockInForm();
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to save reagent stock-in.');
    }
  }

  public function openReagentAdjustmentModal(int $reagentStockId): void
  {
    $reagent = LabReagentStock::query()
      ->where('facility_id', $this->facility_id)
      ->find($reagentStockId);

    if (!$reagent) {
      toastr()->warning('Reagent stock was not found.');
      return;
    }

    $this->adjust_reagent_stock_id = (int) $reagent->id;
    $this->adjust_mode = 'add';
    $this->adjust_quantity = null;
    $this->adjust_notes = null;
    $this->dispatch('open-lab-reagent-adjustment-modal');
  }

  public function applyReagentAdjustment(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->reagentAdjustmentRules());

      $stock = LabReagentStock::query()
        ->where('facility_id', $this->facility_id)
        ->findOrFail($this->adjust_reagent_stock_id);

      $quantity = (float) $this->adjust_quantity;
      $current = (float) $stock->quantity_available;
      $newBalance = $this->adjust_mode === 'add' ? ($current + $quantity) : ($current - $quantity);

      if ($newBalance < 0) {
        throw ValidationException::withMessages([
          'adjust_quantity' => 'Adjustment exceeds available reagent quantity.',
        ]);
      }

      $stock->quantity_available = $newBalance;
      $stock->save();

      LabReagentMovement::create([
        'facility_id' => $this->facility_id,
        'lab_reagent_stock_id' => $stock->id,
        'movement_type' => $this->adjust_mode === 'add' ? 'adjust_add' : 'adjust_deduct',
        'quantity' => $this->adjust_mode === 'add' ? $quantity : -$quantity,
        'balance_after' => $newBalance,
        'moved_at' => now(),
        'moved_by' => $this->officer_name,
        'reference_code' => 'LBR-ADJ-' . now()->format('YmdHis'),
        'notes' => $this->adjust_notes,
      ]);

      DB::commit();
      toastr()->success('Reagent adjustment applied.');
      $this->dispatch('close-lab-reagent-adjustment-modal');
      $this->reset(['adjust_reagent_stock_id', 'adjust_quantity', 'adjust_notes']);
      $this->adjust_mode = 'add';
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to apply reagent adjustment.');
    }
  }

  public function updateReagentReorderLevel(int $reagentId): void
  {
    $value = isset($this->reorderLevels[$reagentId]) ? (float) $this->reorderLevels[$reagentId] : null;
    if ($value === null || $value < 0) {
      toastr()->error('Reorder level must be zero or greater.');
      return;
    }

    $stock = LabReagentStock::query()
      ->where('facility_id', $this->facility_id)
      ->find($reagentId);

    if (!$stock) {
      toastr()->error('Reagent stock not found.');
      return;
    }

    $stock->reorder_level = $value;
    $stock->save();
    toastr()->success('Reorder level updated.');
  }

  public function clearEquipmentForm(): void
  {
    $this->reset([
      'equipment_name',
      'equipment_code',
      'equipment_next_due_date',
      'equipment_notes',
    ]);
    $this->equipment_log_type = 'calibration';
    $this->equipment_performed_date = now()->toDateString();
    $this->equipment_result_status = 'pending';
    $this->equipment_performed_by = $this->officer_name;
  }

  public function saveEquipmentLog(): void
  {
    DB::beginTransaction();
    try {
      $this->validate($this->equipmentRules());

      if ($this->equipment_next_due_date && $this->equipment_next_due_date < $this->equipment_performed_date) {
        throw ValidationException::withMessages([
          'equipment_next_due_date' => 'Next due date cannot be before performed date.',
        ]);
      }

      LabEquipmentLog::create([
        'facility_id' => $this->facility_id,
        'equipment_name' => $this->equipment_name,
        'equipment_code' => $this->equipment_code,
        'log_type' => $this->equipment_log_type,
        'performed_date' => $this->equipment_performed_date,
        'next_due_date' => $this->equipment_next_due_date,
        'result_status' => $this->equipment_result_status,
        'performed_by' => $this->equipment_performed_by ?: $this->officer_name,
        'notes' => $this->equipment_notes,
      ]);

      DB::commit();
      toastr()->success('Equipment log saved successfully.');
      $this->clearEquipmentForm();
      $this->refreshPageSoon();
    } catch (ValidationException $e) {
      DB::rollBack();
      foreach ($e->errors() as $errors) {
        toastr()->error($errors[0]);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      report($e);
      toastr()->error('Unable to save equipment log.');
    }
  }

  public function render()
  {
    $today = now()->toDateString();
    $nextSeven = now()->addDays(7)->toDateString();

    $pendingOrders = LabTestOrder::query()
      ->where('facility_id', $this->facility_id)
      ->where('status', 'pending')
      ->with('patient:id,din,first_name,last_name')
      ->latest('requested_at')
      ->latest('id')
      ->limit(1000)
      ->get();

    $samples = LabSample::query()
      ->where('facility_id', $this->facility_id)
      ->with([
        'patient:id,din,first_name,last_name',
        'order:id,status,requested_by,requested_at',
        'processingBatch:id,batch_code,status',
      ])
      ->latest('received_at')
      ->latest('id')
      ->limit(1000)
      ->get();

    $batches = LabProcessingBatch::query()
      ->where('facility_id', $this->facility_id)
      ->latest('run_date')
      ->latest('id')
      ->limit(1000)
      ->get();

    $qcLogs = LabQcLog::query()
      ->where('facility_id', $this->facility_id)
      ->latest('qc_date')
      ->latest('id')
      ->limit(1000)
      ->get();

    $reagents = LabReagentStock::query()
      ->where('facility_id', $this->facility_id)
      ->where('is_active', true)
      ->orderBy('reagent_name')
      ->limit(1000)
      ->get();

    foreach ($reagents as $row) {
      if (!array_key_exists($row->id, $this->reorderLevels)) {
        $this->reorderLevels[$row->id] = (float) ($row->reorder_level ?? 0);
      }
    }

    $reagentMovements = LabReagentMovement::query()
      ->where('facility_id', $this->facility_id)
      ->with('stock:id,reagent_name,unit')
      ->latest('moved_at')
      ->latest('id')
      ->limit(1000)
      ->get();

    $equipmentLogs = LabEquipmentLog::query()
      ->where('facility_id', $this->facility_id)
      ->latest('performed_date')
      ->latest('id')
      ->limit(1000)
      ->get();

    $summary = [
      'pending_orders' => $pendingOrders->count(),
      'samples_received' => (int) LabSample::query()->where('facility_id', $this->facility_id)->where('sample_status', 'received')->count(),
      'samples_processing' => (int) LabSample::query()->where('facility_id', $this->facility_id)->where('sample_status', 'processing')->count(),
      'ready_for_result' => (int) LabSample::query()->where('facility_id', $this->facility_id)->where('sample_status', 'ready_for_result')->count(),
      'qc_failed_last_30_days' => (int) LabQcLog::query()
        ->where('facility_id', $this->facility_id)
        ->where('status', 'fail')
        ->whereDate('qc_date', '>=', now()->subDays(30)->toDateString())
        ->count(),
      'low_reagents' => $reagents->filter(function ($row) {
        $available = (float) ($row->quantity_available ?? 0);
        $reorder = (float) ($row->reorder_level ?? 0);
        return $available > 0 && $available <= $reorder;
      })->count(),
      'out_of_stock_reagents' => $reagents->filter(fn($row) => (float) ($row->quantity_available ?? 0) <= 0)->count(),
      'equipment_due_soon' => (int) LabEquipmentLog::query()
        ->where('facility_id', $this->facility_id)
        ->whereNotNull('next_due_date')
        ->whereBetween('next_due_date', [$today, $nextSeven])
        ->count(),
    ];

    return view('livewire.core.laboratory-operations', [
      'summary' => $summary,
      'pendingOrders' => $pendingOrders,
      'samples' => $samples,
      'batches' => $batches,
      'qcLogs' => $qcLogs,
      'reagents' => $reagents,
      'reagentMovements' => $reagentMovements,
      'equipmentLogs' => $equipmentLogs,
      'sampleStatusOptions' => self::SAMPLE_STATUS_OPTIONS,
      'batchStatusOptions' => self::BATCH_STATUS_OPTIONS,
      'qcTypeOptions' => self::QC_TYPE_OPTIONS,
      'qcStatusOptions' => self::QC_STATUS_OPTIONS,
      'equipmentLogTypeOptions' => self::EQUIPMENT_LOG_TYPE_OPTIONS,
      'equipmentResultOptions' => self::EQUIPMENT_RESULT_OPTIONS,
    ])->layout('layouts.facilityAdminLayout');
  }
}
