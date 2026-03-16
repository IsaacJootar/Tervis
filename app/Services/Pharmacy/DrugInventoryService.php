<?php

namespace App\Services\Pharmacy;

use App\Models\DrugCatalogItem;
use App\Models\DrugStockBatch;
use App\Models\DrugStockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DrugInventoryService
{
  public function getAvailableStock(int $facilityId, int $drugCatalogItemId): float
  {
    return (float) DrugStockBatch::query()
      ->where('facility_id', $facilityId)
      ->where('drug_catalog_item_id', $drugCatalogItemId)
      ->where('is_active', true)
      ->where('quantity_available', '>', 0)
      ->where(function ($query) {
        $query->whereNull('expiry_date')
          ->orWhereDate('expiry_date', '>=', now()->toDateString());
      })
      ->sum('quantity_available');
  }

  public function stockIn(array $payload): DrugStockBatch
  {
    $facilityId = (int) $payload['facility_id'];
    $drugCatalogItemId = (int) $payload['drug_catalog_item_id'];
    $quantity = (float) $payload['quantity_received'];

    $batch = DrugStockBatch::create([
      'facility_id' => $facilityId,
      'drug_catalog_item_id' => $drugCatalogItemId,
      'batch_number' => $this->normalizeText($payload['batch_number'] ?? null),
      'received_date' => Carbon::parse($payload['received_date'])->toDateString(),
      'expiry_date' => !empty($payload['expiry_date']) ? Carbon::parse($payload['expiry_date'])->toDateString() : null,
      'quantity_received' => $quantity,
      'quantity_available' => $quantity,
      'unit_cost' => isset($payload['unit_cost']) && $payload['unit_cost'] !== '' ? (float) $payload['unit_cost'] : null,
      'supplier_name' => $this->normalizeText($payload['supplier_name'] ?? null),
      'notes' => $this->normalizeText($payload['notes'] ?? null),
      'is_active' => true,
    ]);

    $newBalance = $this->getAvailableStock($facilityId, $drugCatalogItemId);

    DrugStockMovement::create([
      'facility_id' => $facilityId,
      'drug_catalog_item_id' => $drugCatalogItemId,
      'drug_stock_batch_id' => $batch->id,
      'movement_type' => 'stock_in',
      'quantity' => $quantity,
      'balance_after' => $newBalance,
      'moved_at' => now(),
      'moved_by' => $this->normalizeText($payload['moved_by'] ?? null),
      'reference_type' => $payload['reference_type'] ?? null,
      'reference_id' => $payload['reference_id'] ?? null,
      'reference_code' => $payload['reference_code'] ?? null,
      'notes' => $this->normalizeText($payload['movement_note'] ?? $payload['notes'] ?? null),
    ]);

    return $batch;
  }

  public function adjustStock(array $payload): void
  {
    $facilityId = (int) $payload['facility_id'];
    $drugCatalogItemId = (int) $payload['drug_catalog_item_id'];
    $mode = strtolower(trim((string) ($payload['mode'] ?? '')));
    $quantity = (float) ($payload['quantity'] ?? 0);

    if ($quantity <= 0) {
      throw new \RuntimeException('Adjustment quantity must be greater than zero.');
    }

    if (!in_array($mode, ['add', 'deduct'], true)) {
      throw new \RuntimeException('Invalid adjustment mode.');
    }

    if ($mode === 'add') {
      $this->stockIn([
        'facility_id' => $facilityId,
        'drug_catalog_item_id' => $drugCatalogItemId,
        'batch_number' => $payload['batch_number'] ?? ('ADJ-' . now()->format('YmdHis')),
        'received_date' => $payload['received_date'] ?? now()->toDateString(),
        'expiry_date' => $payload['expiry_date'] ?? null,
        'quantity_received' => $quantity,
        'unit_cost' => null,
        'supplier_name' => null,
        'notes' => $payload['notes'] ?? null,
        'moved_by' => $payload['moved_by'] ?? null,
        'reference_type' => $payload['reference_type'] ?? null,
        'reference_id' => $payload['reference_id'] ?? null,
        'reference_code' => $payload['reference_code'] ?? null,
        'movement_note' => 'Stock adjustment (add): ' . $this->normalizeText($payload['notes'] ?? null),
      ]);
      return;
    }

    $this->deductStockFromBatches([
      'facility_id' => $facilityId,
      'drug_catalog_item_id' => $drugCatalogItemId,
      'quantity' => $quantity,
      'moved_by' => $payload['moved_by'] ?? null,
      'patient_id' => null,
      'movement_type' => 'adjustment_out',
      'reference_type' => $payload['reference_type'] ?? null,
      'reference_id' => $payload['reference_id'] ?? null,
      'reference_code' => $payload['reference_code'] ?? null,
      'notes' => $payload['notes'] ?? null,
    ]);
  }

  public function issueStock(array $payload): void
  {
    $this->deductStockFromBatches([
      'facility_id' => (int) $payload['facility_id'],
      'drug_catalog_item_id' => (int) $payload['drug_catalog_item_id'],
      'quantity' => (float) $payload['quantity'],
      'moved_by' => $payload['moved_by'] ?? null,
      'patient_id' => $payload['patient_id'] ?? null,
      'movement_type' => 'issue',
      'reference_type' => $payload['reference_type'] ?? null,
      'reference_id' => $payload['reference_id'] ?? null,
      'reference_code' => $payload['reference_code'] ?? null,
      'notes' => $payload['notes'] ?? null,
    ]);
  }

  private function deductStockFromBatches(array $payload): void
  {
    $facilityId = (int) $payload['facility_id'];
    $drugCatalogItemId = (int) $payload['drug_catalog_item_id'];
    $requestedQty = (float) ($payload['quantity'] ?? 0);

    if ($requestedQty <= 0) {
      throw new \RuntimeException('Requested stock quantity must be greater than zero.');
    }

    $availableBalance = $this->getAvailableStock($facilityId, $drugCatalogItemId);
    if ($availableBalance < $requestedQty) {
      $drug = DrugCatalogItem::query()->find($drugCatalogItemId);
      $name = $drug?->drug_name ?? 'Selected drug';
      throw new \RuntimeException("Insufficient stock for {$name}. Available: {$availableBalance}, requested: {$requestedQty}.");
    }

    $remaining = $requestedQty;

    $batches = DrugStockBatch::query()
      ->where('facility_id', $facilityId)
      ->where('drug_catalog_item_id', $drugCatalogItemId)
      ->where('is_active', true)
      ->where('quantity_available', '>', 0)
      ->where(function ($query) {
        $query->whereNull('expiry_date')
          ->orWhereDate('expiry_date', '>=', now()->toDateString());
      })
      ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END ASC')
      ->orderBy('expiry_date')
      ->orderBy('received_date')
      ->orderBy('id')
      ->lockForUpdate()
      ->get();

    foreach ($batches as $batch) {
      if ($remaining <= 0) {
        break;
      }

      $take = min((float) $batch->quantity_available, $remaining);
      if ($take <= 0) {
        continue;
      }

      $batch->quantity_available = (float) $batch->quantity_available - $take;
      $batch->save();

      $availableBalance -= $take;
      $remaining -= $take;

      DrugStockMovement::create([
        'facility_id' => $facilityId,
        'drug_catalog_item_id' => $drugCatalogItemId,
        'drug_stock_batch_id' => $batch->id,
        'patient_id' => $payload['patient_id'] ?? null,
        'movement_type' => $payload['movement_type'] ?? 'issue',
        'quantity' => -1 * $take,
        'balance_after' => max($availableBalance, 0),
        'moved_at' => now(),
        'moved_by' => $this->normalizeText($payload['moved_by'] ?? null),
        'reference_type' => $payload['reference_type'] ?? null,
        'reference_id' => $payload['reference_id'] ?? null,
        'reference_code' => $payload['reference_code'] ?? null,
        'notes' => $this->normalizeText($payload['notes'] ?? null),
      ]);
    }

    if ($remaining > 0) {
      throw new \RuntimeException('Stock deduction could not be completed due to concurrent updates. Please retry.');
    }
  }

  private function normalizeText($value): ?string
  {
    $text = trim((string) $value);
    return $text === '' ? null : $text;
  }
}

