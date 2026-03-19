<?php

namespace App\Services\AI;

use Carbon\Carbon;
use InvalidArgumentException;

class WorkspaceAiAssistantService
{
  private const MIN_ASSESSMENT_NOTE_LENGTH = 40;
  private const HIGH_CHARGE_THRESHOLD = 50000.0;
  private const PRESCRIPTION_OVERDUE_DAYS = 3;
  private const REMINDER_PENDING_RATE_WARNING = 40.0;
  private const REMINDER_FAILED_RATE_ACTION = 15.0;

  private const CRITICAL_VALUES = [
    'hb_critical_low' => 8.0,
    'pcv_critical_low' => 24.0,
    'fbs_warning_high' => 126.0,
    'rbs_warning_high' => 200.0,
  ];

  private const RISK_SCORE_BY_SEVERITY = [
    'action' => 3,
    'watch' => 1,
    'ok' => 0,
  ];

  private const DOCTOR_TEST_SIGNAL_KEYWORDS = [
    'fever',
    'malaria',
    'infection',
    'cough',
    'pain',
    'anaemia',
    'anemia',
    'bleeding',
  ];

  private const DOCTOR_DRUG_SIGNAL_KEYWORDS = [
    'medication',
    'drug',
    'antibiotic',
    'analgesic',
    'treat',
  ];

  private const HIGH_ALERT_DRUG_KEYWORDS = [
    'insulin',
    'warfarin',
    'heparin',
    'morphine',
    'diazepam',
  ];

  private const CHILD_VACCINE_DUE_MAP = [
    'bcg' => 0,
    'hepbo' => 0,
    'opv0' => 0,
    'opv1' => 2,
    'penta1' => 2,
    'pcv1' => 2,
    'rota1' => 2,
    'ipv1' => 2,
    'opv2' => 3,
    'penta2' => 3,
    'pcv2' => 3,
    'rota2' => 3,
    'opv3' => 4,
    'penta3' => 4,
    'pcv3' => 4,
    'rota3' => 4,
    'ipv2' => 4,
    'malaria1' => 5,
    'malaria2' => 6,
    'vita1' => 6,
    'malaria3' => 7,
    'measles1' => 9,
    'yfever' => 9,
    'mening' => 9,
    'measles2' => 15,
    'malaria4' => 24,
    'vita2' => 24,
  ];

  public function analyzeDoctorAssessment(array $input): array
  {
    $this->validateInput($input, [
      'assessment_note',
      'final_diagnosis',
      'management_plan',
      'test_orders',
      'drug_orders',
      'next_appointment_date',
    ]);

    $items = [];

    $assessmentNote = trim((string) ($input['assessment_note'] ?? ''));
    $finalDiagnosis = trim((string) ($input['final_diagnosis'] ?? ''));
    $managementPlan = trim((string) ($input['management_plan'] ?? ''));
    $testOrdersCount = count((array) ($input['test_orders'] ?? []));
    $drugOrdersCount = count((array) ($input['drug_orders'] ?? []));
    $nextAppointment = $input['next_appointment_date'] ?? null;

    if (mb_strlen($assessmentNote) < self::MIN_ASSESSMENT_NOTE_LENGTH) {
      $items[] = $this->item(
        'action',
        'Expand clinical findings',
        'Assessment note is short. Add key findings, exam context, and decision rationale.',
        'high'
      );
    } else {
      $items[] = $this->item(
        'ok',
        'Findings detail looks good',
        'Assessment narrative has enough detail for follow-up continuity.',
        'medium'
      );
    }

    if ($finalDiagnosis === '') {
      $items[] = $this->item(
        'watch',
        'Final diagnosis missing',
        'Consider filling final diagnosis for clearer downstream lab/pharmacy interpretation.',
        'high'
      );
    }

    if ($nextAppointment === null || $nextAppointment === '') {
      $items[] = $this->item(
        'watch',
        'No next appointment set',
        'Add an optional follow-up date to improve reminders and appointment collation.',
        'high'
      );
    }

    $noteForSignals = $assessmentNote . ' ' . $finalDiagnosis . ' ' . $managementPlan;
    if ($testOrdersCount === 0 && $this->detectClinicalSignals($noteForSignals, self::DOCTOR_TEST_SIGNAL_KEYWORDS)) {
      $items[] = $this->item(
        'watch',
        'Possible missing tests',
        'Clinical text suggests investigations may be needed, but no tests are currently listed.',
        'medium'
      );
    }

    if ($drugOrdersCount === 0 && $this->detectClinicalSignals($noteForSignals, self::DOCTOR_DRUG_SIGNAL_KEYWORDS)) {
      $items[] = $this->item(
        'watch',
        'Possible missing prescriptions',
        'Management plan mentions treatment context but no drug orders are listed.',
        'medium'
      );
    }

    if ($testOrdersCount > 0 || $drugOrdersCount > 0) {
      $items[] = $this->item(
        'ok',
        'Downstream orders prepared',
        "Pending orders queued: {$testOrdersCount} test(s), {$drugOrdersCount} prescription(s).",
        'high'
      );
    }

    return $this->finalize(
      'AI Assistant reviewed assessment completeness, follow-up readiness, and downstream order signals.',
      $items
    );
  }

  public function analyzeLaboratory(array $input): array
  {
    $this->validateInput($input, [
      'report_values',
      'mcs_results',
      'comment',
      'pending_count',
      'selected_pending_count',
    ]);

    $items = [];

    $reportValues = (array) ($input['report_values'] ?? []);
    $mcsResults = (array) ($input['mcs_results'] ?? []);
    $comment = trim((string) ($input['comment'] ?? ''));
    $pendingCount = (int) ($input['pending_count'] ?? 0);
    $selectedPendingCount = (int) ($input['selected_pending_count'] ?? 0);

    if ($pendingCount > 0 && $selectedPendingCount === 0) {
      $items[] = $this->item(
        'action',
        'Pending requests not selected',
        'There are pending doctor-requested tests. Select at least one pending request before save.',
        'high'
      );
    }

    $hb = $this->numericValue($reportValues['hb'] ?? null);
    if ($hb !== null && $hb < self::CRITICAL_VALUES['hb_critical_low']) {
      $items[] = $this->item(
        'action',
        'Critical low Hb detected',
        "Hemoglobin is {$hb} g/dL. Flag urgent clinical review.",
        'high'
      );
    }

    $pcv = $this->numericValue($reportValues['pcv'] ?? null);
    if ($pcv !== null && $pcv < self::CRITICAL_VALUES['pcv_critical_low']) {
      $items[] = $this->item(
        'action',
        'Critical low PCV detected',
        "PCV is {$pcv}%. Escalate to clinician for immediate review.",
        'high'
      );
    }

    $fbs = $this->numericValue($reportValues['fbs'] ?? null);
    if ($fbs !== null && $fbs >= self::CRITICAL_VALUES['fbs_warning_high']) {
      $items[] = $this->item(
        'watch',
        'High fasting glucose',
        "FBS is {$fbs} mg/dL, above normal fasting range.",
        'medium'
      );
    }

    $rbs = $this->numericValue($reportValues['rbs'] ?? null);
    if ($rbs !== null && $rbs >= self::CRITICAL_VALUES['rbs_warning_high']) {
      $items[] = $this->item(
        'watch',
        'High random glucose',
        "RBS is {$rbs} mg/dL. Consider urgent glycemic review.",
        'medium'
      );
    }

    $criticalFlags = [];
    foreach (['tb' => 'TB', 'hbsag' => 'HBsAg', 'hcv' => 'HCV'] as $field => $label) {
      if (in_array((string) ($mcsResults[$field] ?? ''), ['Positive', 'Reactive'], true)) {
        $criticalFlags[] = $label;
      }
    }

    if (!empty($criticalFlags)) {
      $items[] = $this->item(
        'action',
        'High-priority positive markers',
        'Positive/reactive findings detected: ' . implode(', ', $criticalFlags) . '.',
        'high'
      );
    }

    if (($reportValues['mp'] ?? null) === 'Positive') {
      $items[] = $this->item(
        'watch',
        'Malaria positive result',
        'Mp result is positive. Confirm treatment plan and follow-up documentation.',
        'high'
      );
    }

    if ($comment === '' && ($criticalFlags || ($reportValues['mp'] ?? null) === 'Positive')) {
      $items[] = $this->item(
        'watch',
        'Clinical comment recommended',
        'Add concise lab comment for positive/high-risk findings.',
        'medium'
      );
    }

    if (empty($items)) {
      $items[] = $this->item(
        'ok',
        'No immediate risk flags',
        'Current laboratory inputs do not trigger high-priority alerts.',
        'medium'
      );
    }

    return $this->finalize(
      'AI Assistant reviewed pending order compliance and lab result risk signals.',
      $items
    );
  }

  public function analyzePrescriptions(array $input): array
  {
    $this->validateInput($input, [
      'pending_records',
      'selected_pending_ids',
      'cart',
      'charge_amount',
    ]);

    $items = [];

    $pendingRecords = (array) ($input['pending_records'] ?? []);
    $selectedPendingIds = collect((array) ($input['selected_pending_ids'] ?? []))
      ->filter(fn($id) => is_numeric($id))
      ->map(fn($id) => (int) $id)
      ->values();
    $cart = (array) ($input['cart'] ?? []);
    $chargeAmount = $this->numericValue($input['charge_amount'] ?? null);

    if (count($pendingRecords) > 0 && $selectedPendingIds->isEmpty()) {
      $items[] = $this->item(
        'action',
        'Pending prescriptions not selected',
        'Select pending prescription lines before checkout to clear queue correctly.',
        'high'
      );
    }

    if (count($cart) === 0) {
      $items[] = $this->item(
        'action',
        'Cart is empty',
        'Add at least one drug line to cart before checkout.',
        'high'
      );
    }

    if ($chargeAmount === null || $chargeAmount <= 0) {
      $items[] = $this->item(
        'action',
        'Charge amount missing',
        'Enter bill amount so invoice can be generated from checkout.',
        'high'
      );
    }

    if ($chargeAmount !== null && $chargeAmount > self::HIGH_CHARGE_THRESHOLD) {
      $items[] = $this->item(
        'watch',
        'High billing amount',
        'Checkout charge is high. Confirm quantities and selections before submit.',
        'medium'
      );
    }

    $overdue = collect($pendingRecords)->filter(function ($row) {
      $date = $row['prescribed_date'] ?? null;
      if (!$date) {
        return false;
      }

      return Carbon::parse($date)->startOfDay()->lt(now()->subDays(self::PRESCRIPTION_OVERDUE_DAYS)->startOfDay());
    });
    if ($overdue->isNotEmpty()) {
      $items[] = $this->item(
        'watch',
        'Aging pending prescriptions',
        $overdue->count() . ' pending prescription(s) are older than 3 days.',
        'medium'
      );
    }

    $totalQuantity = collect($cart)->sum(fn($line) => (float) ($line['quantity'] ?? 0));
    if ($totalQuantity > 0) {
      $items[] = $this->item(
        'ok',
        'Cart ready for dispense',
        'Cart lines: ' . count($cart) . ', total quantity: ' . number_format((float) $totalQuantity, 2) . '.',
        'high'
      );
    }

    $pendingDrugText = collect($pendingRecords)->pluck('drug_name')->filter()->implode(' ');
    if ($pendingDrugText !== '' && $this->detectClinicalSignals($pendingDrugText, self::HIGH_ALERT_DRUG_KEYWORDS)) {
      $items[] = $this->item(
        'watch',
        'High-alert drug present',
        'Pending list contains high-alert medication names. Confirm dose and dispense accuracy before checkout.',
        'medium'
      );
    }

    return $this->finalize(
      'AI Assistant reviewed pending-prescription clearance, checkout safety, and billing readiness.',
      $items
    );
  }

  public function analyzeChildActivity(array $input): array
  {
    $this->validateInput($input, [
      'child_dob',
      'vaccination_dates',
      'weight_entries',
      'breastfeeding_entries',
      'aefi_cases',
    ]);

    $items = [];

    $childDob = $input['child_dob'] ?? null;
    $vaccinationDates = (array) ($input['vaccination_dates'] ?? []);
    $weightEntries = collect((array) ($input['weight_entries'] ?? []))
      ->filter(fn($row) => isset($row['age'], $row['kg']))
      ->sortBy(fn($row) => [(float) ($row['age'] ?? 0), (string) ($row['date'] ?? '')])
      ->values();
    $breastfeedingEntries = (array) ($input['breastfeeding_entries'] ?? []);
    $aefiCases = collect((array) ($input['aefi_cases'] ?? []));

    $ageMonths = $this->calculateAgeMonthsFromDob($childDob);

    if ($ageMonths !== null) {
      $dueMissing = collect(self::CHILD_VACCINE_DUE_MAP)
        ->filter(fn($dueMonth, $key) => $ageMonths >= $dueMonth && empty($vaccinationDates[$key]))
        ->keys()
        ->values();

      if ($dueMissing->isNotEmpty()) {
        $items[] = $this->item(
          'action',
          'Overdue vaccination slots',
          $dueMissing->count() . ' due vaccine slot(s) are still empty for current child age.',
          'high'
        );
      } else {
        $items[] = $this->item(
          'ok',
          'Vaccination schedule on track',
          'No overdue vaccine slots detected for current child age.',
          'medium'
        );
      }
    }

    if ($weightEntries->count() >= 2) {
      $last = $weightEntries->last();
      $prev = $weightEntries->slice(-2, 1)->first();
      $lastKg = (float) ($last['kg'] ?? 0);
      $prevKg = (float) ($prev['kg'] ?? 0);
      if ($lastKg < $prevKg) {
        $items[] = $this->item(
          'watch',
          'Weight trend declined',
          "Latest weight {$lastKg}kg is below previous {$prevKg}kg.",
          'high'
        );
      } else {
        $items[] = $this->item(
          'ok',
          'Weight trend stable/improving',
          "Latest weight {$lastKg}kg compared to previous {$prevKg}kg.",
          'medium'
        );
      }
    } elseif ($weightEntries->count() === 0) {
      $items[] = $this->item(
        'watch',
        'No weight entries',
        'Add weight measurement records to enable growth trend monitoring.',
        'high'
      );
    }

    $monthsOneToSix = collect(range(1, 6))
      ->map(fn($month) => $breastfeedingEntries[(string) $month] ?? null)
      ->filter();
    if ($monthsOneToSix->contains('NO') || $monthsOneToSix->contains('BW')) {
      $items[] = $this->item(
        'watch',
        'Early breastfeeding risk marker',
        'Months 1-6 include NO/BW entries. Consider targeted counseling follow-up.',
        'medium'
      );
    }

    $seriousAefi = $aefiCases->contains(function ($case) {
      $type = (string) ($case['type'] ?? '');
      $outcomeCode = (int) ($case['outcome_code'] ?? 0);
      return $type === 'Serious' || in_array($outcomeCode, [2, 3, 4], true);
    });
    if ($seriousAefi) {
      $items[] = $this->item(
        'action',
        'Serious AEFI flag present',
        'AEFI entries include serious severity/outcome. Confirm escalation and documentation.',
        'high'
      );
    }

    if (empty($items)) {
      $items[] = $this->item(
        'ok',
        'No immediate child-health flags',
        'Current vaccination, growth, and AEFI inputs do not trigger critical flags.',
        'medium'
      );
    }

    return $this->finalize(
      'AI Assistant reviewed vaccine timeliness, growth trend, breastfeeding pattern, and AEFI severity.',
      $items
    );
  }

  public function analyzeRemindersHub(array $input): array
  {
    $this->validateInput($input, ['total', 'pending', 'failed', 'sent', 'canceled']);

    $items = [];

    $total = (int) ($input['total'] ?? 0);
    $pending = (int) ($input['pending'] ?? 0);
    $failed = (int) ($input['failed'] ?? 0);
    $sent = (int) ($input['sent'] ?? 0);
    $canceled = (int) ($input['canceled'] ?? 0);

    if ($total === 0) {
      $items[] = $this->item(
        'watch',
        'No reminders in current filter',
        'Run source sync and confirm appointment dates are being captured from modules.',
        'high'
      );
    } else {
      $pendingRate = $total > 0 ? ($pending / $total) * 100 : 0;
      $failedRate = $total > 0 ? ($failed / $total) * 100 : 0;

      if ($pendingRate >= self::REMINDER_PENDING_RATE_WARNING) {
        $items[] = $this->item(
          'watch',
          'High pending queue load',
          round($pendingRate, 1) . '% of reminders are pending. Consider dispatch run and source cleanup.',
          'medium'
        );
      } else {
        $items[] = $this->item(
          'ok',
          'Pending queue within expected range',
          "{$pending} pending out of {$total} total reminders.",
          'medium'
        );
      }

      if ($failedRate >= self::REMINDER_FAILED_RATE_ACTION) {
        $items[] = $this->item(
          'action',
          'Dispatch failure risk',
          round($failedRate, 1) . '% failure rate detected. Verify recipient contact quality and channel setup.',
          'high'
        );
      } elseif ($failed > 0) {
        $items[] = $this->item(
          'watch',
          'Some dispatch failures present',
          "{$failed} failed dispatch record(s) found in current filter window.",
          'medium'
        );
      } else {
        $items[] = $this->item(
          'ok',
          'No dispatch failures in filter window',
          'Current filtered reminder runs show stable dispatch outcomes.',
          'medium'
        );
      }
    }

    if ($canceled > 0) {
      $items[] = $this->item(
        'watch',
        'Canceled reminders detected',
        "{$canceled} canceled reminder(s) exist. Requeue if they still require action.",
        'medium'
      );
    }

    if ($sent > 0) {
      $items[] = $this->item(
        'ok',
        'Active reminder delivery present',
        "{$sent} reminder(s) already sent in this filtered window.",
        'high'
      );
    }

    return $this->finalize(
      'AI Assistant reviewed queue pressure, failure risk, and delivery stability for this facility reminder view.',
      $items
    );
  }

  private function finalize(string $summary, array $items): array
  {
    $riskScore = collect($items)
      ->sum(fn($item) => self::RISK_SCORE_BY_SEVERITY[(string) ($item['severity'] ?? 'ok')] ?? 0);

    $riskLevel = match (true) {
      $riskScore >= 4 => 'high',
      $riskScore >= 1 => 'medium',
      default => 'low',
    };

    return [
      'summary' => $summary,
      'risk_level' => $riskLevel,
      'items' => array_values($items),
      'generated_at' => now()->format('M d, Y h:i A'),
    ];
  }

  private function validateInput(array $input, array $required): void
  {
    $missing = collect($required)
      ->filter(fn($key) => !array_key_exists((string) $key, $input))
      ->values()
      ->all();

    if (!empty($missing)) {
      throw new InvalidArgumentException(
        'Invalid AI Assistant input payload. Missing keys: ' . implode(', ', $missing)
      );
    }
  }

  private function detectClinicalSignals(string $text, array $keywords): bool
  {
    if (trim($text) === '' || empty($keywords)) {
      return false;
    }

    $escaped = array_map(fn($word) => preg_quote((string) $word, '/'), $keywords);
    $pattern = '/\b(' . implode('|', $escaped) . ')\b/i';

    return preg_match($pattern, $text) === 1;
  }

  private function calculateAgeMonthsFromDob(mixed $dob): ?int
  {
    if ($dob === null || trim((string) $dob) === '') {
      return null;
    }

    try {
      return Carbon::parse((string) $dob)->diffInMonths(now());
    } catch (\Throwable) {
      return null;
    }
  }

  private function item(string $severity, string $title, string $message, string $confidence): array
  {
    return [
      'severity' => $severity,
      'title' => $title,
      'message' => $message,
      'confidence' => $confidence,
    ];
  }

  private function numericValue(mixed $value): ?float
  {
    if ($value === null) {
      return null;
    }

    if (is_numeric($value)) {
      return (float) $value;
    }

    $stringValue = trim((string) $value);
    if ($stringValue === '') {
      return null;
    }

    if (preg_match('/-?\d+(\.\d+)?/', $stringValue, $matches) === 1) {
      return (float) $matches[0];
    }

    return null;
  }
}
