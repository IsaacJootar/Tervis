<?php

namespace Tests\Unit;

use App\Services\Reports\NhmisFieldRegistry;
use Tests\TestCase;

class NhmisFieldRegistryTest extends TestCase
{
  public function test_it_generates_full_187_field_matrix_with_contiguous_numbers(): void
  {
    $service = app(NhmisFieldRegistry::class);
    $matrix = $service->generateMatrix();

    $this->assertCount(187, $matrix);

    $numbers = array_map(fn($row) => (int) ($row['field_no'] ?? 0), $matrix);
    sort($numbers);
    $this->assertSame(range(1, 187), $numbers);

    $statuses = collect($matrix)->pluck('status')->all();
    $this->assertNotContains('missing', $statuses, 'Matrix should not contain missing statuses after fallback coverage.');
  }

  public function test_it_marks_known_summary_keys_as_structured(): void
  {
    $service = app(NhmisFieldRegistry::class);
    $matrix = collect($service->generateMatrix());

    $entry = $matrix->first(function ($row) {
      return in_array('immunization.bcg', (array) ($row['summary_keys'] ?? []), true);
    });

    $this->assertNotNull($entry);
    $this->assertSame('structured', $entry['status']);
    $this->assertStringContainsString('immunization_records', (string) $entry['source_table']);
  }

  public function test_it_promotes_resolver_backed_rows_to_structured(): void
  {
    $service = app(NhmisFieldRegistry::class);
    $matrix = collect($service->generateMatrix())->keyBy('field_no');

    $this->assertSame('structured', data_get($matrix, '12.status')); // ANC first visit
    $this->assertSame('structured', data_get($matrix, '75.status')); // Fully immunized
    $this->assertSame('structured', data_get($matrix, '170.status')); // Hypertension diagnosed
    $this->assertSame('structured', data_get($matrix, '181.status')); // Stock-out yes/no now structured
  }

  public function test_it_can_sync_and_reload_matrix_file(): void
  {
    $relativePath = 'storage/framework/testing/nhmis-field-matrix-test.json';
    $fullPath = base_path($relativePath);
    @mkdir(dirname($fullPath), 0777, true);

    $service = app(NhmisFieldRegistry::class);
    $service->syncMatrix($relativePath);
    $this->assertFileExists($fullPath);

    $loaded = $service->loadMatrix($relativePath);
    $this->assertCount(187, $loaded);

    @unlink($fullPath);
  }
}
