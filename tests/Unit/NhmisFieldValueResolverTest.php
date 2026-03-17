<?php

namespace Tests\Unit;

use App\Services\Reports\NhmisFieldValueResolver;
use Illuminate\Support\Collection;
use Tests\TestCase;

class NhmisFieldValueResolverTest extends TestCase
{
  public function test_it_resolves_non_key_attendance_row_from_context(): void
  {
    $resolver = new NhmisFieldValueResolver();
    $ctx = $this->emptyContext();
    $ctx['attendance'] = collect([(object) ['id' => 1], (object) ['id' => 2], (object) ['id' => 3]]);

    $value = $this->invokeResolveSingle($resolver, 1, 'General Attendance', $ctx, []);

    $this->assertSame(3, $value);
  }

  public function test_it_resolves_non_key_delivery_row_from_context(): void
  {
    $resolver = new NhmisFieldValueResolver();
    $ctx = $this->emptyContext();
    $ctx['deliveries'] = collect([(object) ['id' => 1], (object) ['id' => 2]]);

    $value = $this->invokeResolveSingle($resolver, 41, 'Total Deliveries Conducted', $ctx, []);

    $this->assertSame(2, $value);
  }

  public function test_it_resolves_non_key_vaccine_row_from_summary_keys_payload(): void
  {
    $resolver = new NhmisFieldValueResolver();
    $ctx = $this->emptyContext();
    $keyValues = ['immunization.bcg' => 11];

    $value = $this->invokeResolveSingle($resolver, 63, 'BCG', $ctx, $keyValues);

    $this->assertSame(11, $value);
  }

  public function test_it_resolves_other_services_rows_from_structured_mentions(): void
  {
    $resolver = new NhmisFieldValueResolver();
    $ctx = $this->emptyContext();
    $ctx['doctor'] = collect([
      (object) ['final_diagnosis' => 'Hypertension diagnosed', 'assessment_note' => 'Started treatment'],
    ]);

    $value = $this->invokeResolveSingle($resolver, 170, 'New cases of Hypertension diagnosed (all ages)', $ctx, []);

    $this->assertSame(1, $value);
  }

  private function invokeResolveSingle(
    NhmisFieldValueResolver $resolver,
    int $fieldNo,
    string $label,
    array $ctx,
    array $keyValues
  ): int {
    $method = new \ReflectionMethod(NhmisFieldValueResolver::class, 'resolveSingle');
    $method->setAccessible(true);

    /** @var int $result */
    $result = $method->invoke($resolver, $fieldNo, $label, $ctx, $keyValues);
    return $result;
  }

  /**
   * @return array<string, Collection<int, mixed>>
   */
  private function emptyContext(): array
  {
    return [
      'attendance' => collect(),
      'inpatient_admissions' => collect(),
      'inpatient_discharges' => collect(),
      'antenatal' => collect(),
      'anc_followups' => collect(),
      'deliveries' => collect(),
      'postnatal' => collect(),
      'tetanus' => collect(),
      'immunization' => collect(),
      'activity' => collect(),
      'nutrition' => collect(),
      'lab' => collect(),
      'doctor' => collect(),
      'fp_regs' => collect(),
      'fp_followups' => collect(),
      'referrals' => collect(),
      'prescriptions' => collect(),
      'dispense_lines' => collect(),
    ];
  }
}
