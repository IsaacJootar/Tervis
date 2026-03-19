<?php

namespace Tests\Unit;

use App\Services\AI\WorkspaceAiAssistantService;
use PHPUnit\Framework\TestCase;

class WorkspaceAiAssistantServiceTest extends TestCase
{
  public function test_input_validation_throws_for_missing_required_keys(): void
  {
    $service = new WorkspaceAiAssistantService();

    $this->expectException(\InvalidArgumentException::class);
    $service->analyzeLaboratory([
      'report_values' => [],
    ]);
  }

  public function test_doctor_assessment_analysis_flags_missing_follow_up_and_short_note(): void
  {
    $service = new WorkspaceAiAssistantService();

    $result = $service->analyzeDoctorAssessment([
      'assessment_note' => 'short note',
      'final_diagnosis' => '',
      'management_plan' => '',
      'next_appointment_date' => null,
      'test_orders' => [],
      'drug_orders' => [],
    ]);

    $this->assertIsArray($result);
    $this->assertSame('high', $result['risk_level']);
    $this->assertNotEmpty($result['items']);
  }

  public function test_laboratory_analysis_flags_high_risk_markers(): void
  {
    $service = new WorkspaceAiAssistantService();

    $result = $service->analyzeLaboratory([
      'report_values' => [
        'hb' => '7.2',
        'pcv' => '20',
        'mp' => 'Positive',
      ],
      'mcs_results' => [
        'tb' => 'Positive',
      ],
      'comment' => '',
      'pending_count' => 2,
      'selected_pending_count' => 0,
    ]);

    $this->assertSame('high', $result['risk_level']);
    $this->assertGreaterThanOrEqual(3, count($result['items']));
  }

  public function test_prescriptions_analysis_flags_empty_cart_and_missing_charge(): void
  {
    $service = new WorkspaceAiAssistantService();

    $result = $service->analyzePrescriptions([
      'pending_records' => [
        ['id' => 1, 'drug_name' => 'Amoxicillin', 'prescribed_date' => now()->subDays(5)->format('Y-m-d')],
      ],
      'selected_pending_ids' => [],
      'cart' => [],
      'charge_amount' => null,
    ]);

    $this->assertSame('high', $result['risk_level']);
    $this->assertNotEmpty($result['items']);
  }
}
