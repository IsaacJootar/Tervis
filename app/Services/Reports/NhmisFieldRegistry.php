<?php

namespace App\Services\Reports;

use RuntimeException;

class NhmisFieldRegistry
{
  public const DEFAULT_MATRIX_PATH = 'docs/nhmis-field-matrix.json';

  public function loadOrSyncMatrix(?string $matrixPath = null): array
  {
    $matrix = $this->loadMatrix($matrixPath);
    if (count($matrix) === 187) {
      return $matrix;
    }

    return $this->syncMatrix($matrixPath);
  }

  public function loadMatrix(?string $matrixPath = null): array
  {
    $path = $this->resolveMatrixPath($matrixPath);
    if (!is_file($path)) {
      return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
      return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  public function syncMatrix(?string $matrixPath = null, ?string $templatePath = null): array
  {
    $matrix = $this->generateMatrix($templatePath);
    $path = $this->resolveMatrixPath($matrixPath);

    $encoded = json_encode($matrix, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
      throw new RuntimeException('Unable to encode NHMIS matrix JSON.');
    }

    file_put_contents($path, $encoded . PHP_EOL);
    return $matrix;
  }

  public function generateMatrix(?string $templatePath = null): array
  {
    $templateFile = $templatePath ?: resource_path('MONTHLY NHMIS REPORT.html');
    if (!is_file($templateFile)) {
      throw new RuntimeException('NHMIS template file not found: ' . $templateFile);
    }

    $html = file_get_contents($templateFile);
    if ($html === false) {
      throw new RuntimeException('Unable to read NHMIS template file.');
    }

    preg_match_all('/<tr>\\s*<td class="rn">\\s*(\\d+)\\s*<\\/td>\\s*<td class="fn">(.*?)<\\/td>(.*?)<\\/tr>/si', $html, $matches, PREG_SET_ORDER);

    $rows = [];
    foreach ($matches as $match) {
      $fieldNo = (int) ($match[1] ?? 0);
      if ($fieldNo <= 0) {
        continue;
      }

      $fieldLabel = trim(preg_replace('/\\s+/', ' ', strip_tags((string) ($match[2] ?? ''))));
      $cellsHtml = (string) ($match[3] ?? '');
      preg_match_all('/data-summary-key="([^"]+)"/i', $cellsHtml, $keysMatch);
      $summaryKeys = collect($keysMatch[1] ?? [])->map(fn($key) => trim((string) $key))->filter()->unique()->values()->toArray();

      $meta = $this->resolveSummaryKeyMeta($summaryKeys);

      $rows[$fieldNo] = [
        'field_no' => $fieldNo,
        'field_label' => $fieldLabel !== '' ? $fieldLabel : ('Field ' . $fieldNo),
        'summary_keys' => $summaryKeys,
        'source_table' => $meta['source_table'],
        'source_field' => $meta['source_field'],
        'transform_rule' => $meta['transform_rule'],
        'status' => $meta['status'],
      ];
    }

    // Blocks 186/187 are signature metadata outside main numeric table rows.
    $rows[186] = $rows[186] ?? [
      'field_no' => 186,
      'field_label' => 'Completed by',
      'summary_keys' => [],
      'source_table' => 'users',
      'source_field' => 'first_name,last_name,role',
      'transform_rule' => 'Printable metadata from authenticated officer at generation time.',
      'status' => 'structured',
    ];
    $rows[187] = $rows[187] ?? [
      'field_no' => 187,
      'field_label' => 'Verified by',
      'summary_keys' => [],
      'source_table' => 'users',
      'source_field' => 'first_name,last_name,role',
      'transform_rule' => 'Printable metadata from authenticated officer at generation time.',
      'status' => 'structured',
    ];

    // Guarantee a complete 1..187 matrix even where template structure changes.
    for ($i = 1; $i <= 187; $i++) {
      if (!isset($rows[$i])) {
        $rows[$i] = [
          'field_no' => $i,
          'field_label' => 'Field ' . $i,
          'summary_keys' => [],
          'source_table' => '',
          'source_field' => '',
          'transform_rule' => 'Not found in template parse. Requires manual mapping.',
          'status' => 'missing',
        ];
      }
    }

    $rows = $this->applyFallbackCoverage($rows);

    ksort($rows);
    return array_values($rows);
  }

  private function applyFallbackCoverage(array $rows): array
  {
    $resolverStructured = array_flip((new NhmisFieldValueResolver())->structuredFieldNumbers());

    foreach ($rows as $fieldNo => $row) {
      $status = (string) ($row['status'] ?? '');
      if ($status !== 'missing') {
        continue;
      }

      $fallback = $this->inferFallbackMeta((int) $fieldNo, (string) ($row['field_label'] ?? ''));
      $rows[$fieldNo]['source_table'] = $fallback['source_table'];
      $rows[$fieldNo]['source_field'] = $fallback['source_field'];
      $rows[$fieldNo]['transform_rule'] = $fallback['transform_rule'];
      $rows[$fieldNo]['status'] = isset($resolverStructured[(int) $fieldNo]) ? 'structured' : 'fallback';

      if ($rows[$fieldNo]['status'] === 'structured') {
        $rows[$fieldNo]['transform_rule'] = 'Structured row-level resolver metric. ' . $rows[$fieldNo]['transform_rule'];
      }
    }

    return $rows;
  }

  private function inferFallbackMeta(int $fieldNo, string $fieldLabel): array
  {
    $label = strtolower(trim($fieldLabel));

    if ($fieldNo >= 1 && $fieldNo <= 2) {
      return [
        'source_table' => 'daily_attendances',
        'source_field' => 'visit_date, date_of_birth, gender',
        'transform_rule' => 'Fallback aggregation from attendance age/sex buckets for NHMIS attendance rows.',
      ];
    }

    if ($fieldNo >= 3 && $fieldNo <= 5) {
      return [
        'source_table' => 'inpatient_admissions',
        'source_field' => 'admitted_at, discharged_at, status, patient_id',
        'transform_rule' => 'Fallback aggregation from admissions/discharges where row-level NHMIS split is not yet explicitly keyed.',
      ];
    }

    if ($fieldNo >= 6 && $fieldNo <= 11) {
      return [
        'source_table' => 'deliveries',
        'source_field' => 'dead, baby_dead, still_birth, complications, dodel',
        'transform_rule' => 'Fallback mortality mapping from delivery outcomes and complications pending full cause-coded registry.',
      ];
    }

    if ($fieldNo >= 12 && $fieldNo <= 35) {
      return [
        'source_table' => 'antenatal_registrations + antenatal_follow_up_assessments + deliveries',
        'source_field' => 'registration_date, gestational_age_weeks, visit_date, special_instructions, dodel',
        'transform_rule' => 'Fallback maternal/ANC mapping from canonical ANC + follow-up + delivery sources until per-row keys are explicitly configured.',
      ];
    }

    if ($fieldNo >= 36 && $fieldNo <= 40) {
      return [
        'source_table' => 'tetanus_vaccinations',
        'source_field' => 'current_tt_dose, visit_date, dose_date',
        'transform_rule' => 'Fallback TT/TD row mapping from tetanus vaccination dose records.',
      ];
    }

    if ($fieldNo >= 41 && $fieldNo <= 62) {
      return [
        'source_table' => 'deliveries',
        'source_field' => 'alive, still_birth, weight, baby_sex, dodel, newborn_care',
        'transform_rule' => 'Fallback newborn mapping from delivery/newborn outcome fields pending full per-row NHMIS key expansion.',
      ];
    }

    if ($fieldNo >= 63 && $fieldNo <= 97) {
      return [
        'source_table' => 'immunization_records + child_health_activity_records + tetanus_vaccinations',
        'source_field' => 'dose date columns + vaccination_dates + current_tt_dose',
        'transform_rule' => 'Fallback immunization mapping using merged deduplicated vaccine-dose sources where template row has no explicit key marker.',
      ];
    }

    if ($fieldNo >= 98 && $fieldNo <= 114) {
      return [
        'source_table' => 'nutrition_records + child_health_activity_records + immunization_records',
        'source_field' => 'muac/growth/feeding + breastfeeding_entries + vitamin A dose dates',
        'transform_rule' => 'Fallback child-health mapping from nutrition and child activity tables pending explicit row key binding.',
      ];
    }

    if ($fieldNo >= 115 && $fieldNo <= 183) {
      return [
        'source_table' => 'postnatal_records + doctor_assessments + lab_tests + prescriptions + drug_dispense_lines + invoices + patient_payments',
        'source_field' => 'family_planning, diagnoses, lab result payloads, prescription/dispense status, billing fields',
        'transform_rule' => 'Fallback other-services mapping from available clinical/lab/pharmacy/finance structured sources pending final row-level registry.',
      ];
    }

    if ($fieldNo === 184) {
      return [
        'source_table' => 'referrals',
        'source_field' => 'referral_date, status, outcome',
        'transform_rule' => 'Fallback referral count mapping from referrals register.',
      ];
    }

    if ($fieldNo === 185) {
      return [
        'source_table' => 'facilities + facility_services',
        'source_field' => 'service configuration/administrative flags',
        'transform_rule' => 'Fallback EmONC availability flag pending dedicated structured indicator table.',
      ];
    }

    return [
      'source_table' => 'n/a',
      'source_field' => 'n/a',
      'transform_rule' => 'Fallback metadata placeholder for unmapped field: ' . ($label !== '' ? $label : 'unnamed'),
    ];
  }

  private function resolveSummaryKeyMeta(array $summaryKeys): array
  {
    if (count($summaryKeys) === 0) {
      return [
        'source_table' => '',
        'source_field' => '',
        'transform_rule' => 'No summary key marker in template; defaults to 0 unless mapped later.',
        'status' => 'missing',
      ];
    }

    $catalog = $this->summaryKeyCatalog();
    $known = [];
    $unknown = [];

    foreach ($summaryKeys as $key) {
      if (isset($catalog[$key])) {
        $known[$key] = $catalog[$key];
      } else {
        $unknown[] = $key;
      }
    }

    $sourceTables = collect($known)->pluck('source_table')->filter()->unique()->values()->toArray();
    $sourceFields = collect($known)->pluck('source_field')->filter()->unique()->values()->toArray();
    $rules = collect($known)->map(fn($meta, $key) => $key . ': ' . $meta['rule'])->values()->toArray();

    if (count($unknown) > 0) {
      $rules[] = 'Unknown keys: ' . implode(', ', $unknown) . ' (requires mapping).';
    }

    return [
      'source_table' => implode(' | ', $sourceTables),
      'source_field' => implode(' | ', $sourceFields),
      'transform_rule' => implode(' ; ', $rules),
      'status' => count($unknown) === 0 ? 'structured' : 'fallback',
    ];
  }

  private function summaryKeyCatalog(): array
  {
    return [
      'immunization.bcg' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'bcg_date | vaccination_dates.bcg',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.opv0' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'opv0_date | vaccination_dates.opv0',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.opv1' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'opv1_date | vaccination_dates.opv1',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.opv2' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'opv2_date | vaccination_dates.opv2',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.opv3' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'opv3_date | vaccination_dates.opv3',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.penta1' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'penta1_date | vaccination_dates.penta1',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.penta2' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'penta2_date | vaccination_dates.penta2',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.penta3' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'penta3_date | vaccination_dates.penta3',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.pcv1' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'pcv1_date | vaccination_dates.pcv1',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.pcv2' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'pcv2_date | vaccination_dates.pcv2',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.pcv3' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'pcv3_date | vaccination_dates.pcv3',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.ipv1' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'ipv1_date | vaccination_dates.ipv1',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.ipv2' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'ipv2_date | vaccination_dates.ipv2',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.mcv1' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'mr1_date | vaccination_dates.measles1',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.mcv2' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'mr2_date | vaccination_dates.measles2',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.yf' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'yf_date | vaccination_dates.yfever',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.hepb0' => [
        'source_table' => 'immunization_records + child_health_activity_records',
        'source_field' => 'hepb0_date | vaccination_dates.hepbo',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'immunization.hpv' => [
        'source_table' => 'immunization_records',
        'source_field' => 'hpv_date',
        'rule' => 'Unique child/patient + dose date count within report window.',
      ],
      'child_health.exclusive_breastfeeding' => [
        'source_table' => 'nutrition_records + child_health_activity_records',
        'source_field' => 'infant_feeding | breastfeeding_entries',
        'rule' => 'Union distinct child/patient where Exclusive BF or E code appears in period.',
      ],
      'child_health.muac_screened' => [
        'source_table' => 'nutrition_records',
        'source_field' => 'muac_value_mm',
        'rule' => 'Count rows with non-null MUAC within period.',
      ],
      'child_health.sam_new_cases' => [
        'source_table' => 'nutrition_records',
        'source_field' => 'muac_class',
        'rule' => 'Count rows with muac_class=Red within period.',
      ],
      'child_health.mam_new_cases' => [
        'source_table' => 'nutrition_records',
        'source_field' => 'muac_class',
        'rule' => 'Count rows with muac_class=Yellow within period.',
      ],
      'child_health.sam_admissions' => [
        'source_table' => 'nutrition_records',
        'source_field' => 'admission_status',
        'rule' => 'Count rows with admission_status=Admitted HP OTP within period.',
      ],
      'pharmacy.prescriptions_total' => [
        'source_table' => 'prescriptions',
        'source_field' => 'id',
        'rule' => 'Count prescriptions within period.',
      ],
      'pharmacy.prescriptions_dispensed' => [
        'source_table' => 'prescriptions',
        'source_field' => 'status',
        'rule' => 'Count prescriptions with status=dispensed within period.',
      ],
      'pharmacy.prescriptions_pending' => [
        'source_table' => 'prescriptions',
        'source_field' => 'status',
        'rule' => 'Count prescriptions with status=pending within period.',
      ],
      'pharmacy.prescriptions_partial' => [
        'source_table' => 'prescriptions',
        'source_field' => 'status',
        'rule' => 'Count prescriptions with status=partial within period.',
      ],
      'pharmacy.dispense_lines' => [
        'source_table' => 'drug_dispense_lines',
        'source_field' => 'id',
        'rule' => 'Count dispensed lines within period.',
      ],
      'pharmacy.dispensed_quantity_total' => [
        'source_table' => 'drug_dispense_lines',
        'source_field' => 'quantity',
        'rule' => 'Sum dispensed quantity within period.',
      ],
    ];
  }

  private function resolveMatrixPath(?string $matrixPath = null): string
  {
    $relative = $matrixPath ?: self::DEFAULT_MATRIX_PATH;
    return base_path($relative);
  }
}
