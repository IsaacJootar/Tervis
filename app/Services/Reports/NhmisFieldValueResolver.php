<?php

namespace App\Services\Reports;

use App\Models\AntenatalFollowUpAssessment;
use App\Models\ChildHealthActivityRecord;
use App\Models\DailyAttendance;
use App\Models\Delivery;
use App\Models\DoctorAssessment;
use App\Models\DrugDispenseLine;
use App\Models\FamilyPlanningFollowUp;
use App\Models\ImmunizationRecord;
use App\Models\InpatientAdmission;
use App\Models\LabTest;
use App\Models\NutritionRecord;
use App\Models\PostnatalRecord;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\Registrations\AntenatalRegistration;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\TetanusVaccination;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class NhmisFieldValueResolver
{
  public function structuredFieldNumbers(): array
  {
    // All report indicator rows are now computed via explicit key-based or row-level resolver rules.
    return range(1, 185);
  }

  public function resolveValues(array $facilityIds, string $from, string $to, array $keyValues, array $matrix): array
  {
    $fromDate = Carbon::parse($from)->startOfDay();
    $toDate = Carbon::parse($to)->endOfDay();

    $ctx = $this->loadContext($facilityIds, $fromDate, $toDate);
    $values = [];

    foreach ($matrix as $row) {
      $fieldNo = (int) ($row['field_no'] ?? 0);
      if ($fieldNo <= 0) {
        continue;
      }
      $summaryKeys = (array) ($row['summary_keys'] ?? []);
      if (count($summaryKeys) > 0) {
        continue;
      }
      $label = (string) ($row['field_label'] ?? '');
      $values[$fieldNo] = $this->resolveSingle($fieldNo, $label, $ctx, $keyValues);
    }

    return $values;
  }

  private function loadContext(array $facilityIds, Carbon $fromDate, Carbon $toDate): array
  {
    return [
      'attendance' => DailyAttendance::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'inpatient_admissions' => InpatientAdmission::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('admitted_at', [$fromDate, $toDate])
        ->get(),
      'inpatient_discharges' => InpatientAdmission::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('discharged_at', [$fromDate, $toDate])
        ->get(),
      'antenatal' => AntenatalRegistration::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('registration_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'anc_followups' => AntenatalFollowUpAssessment::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'deliveries' => Delivery::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('dodel', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'postnatal' => PostnatalRecord::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'tetanus' => TetanusVaccination::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'immunization' => ImmunizationRecord::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'activity' => ChildHealthActivityRecord::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'nutrition' => NutritionRecord::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'lab' => LabTest::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'doctor' => DoctorAssessment::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'fp_regs' => FamilyPlanningRegistration::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('registration_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'fp_followups' => FamilyPlanningFollowUp::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('visit_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'referrals' => Referral::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('referral_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'prescriptions' => Prescription::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('prescribed_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
      'dispense_lines' => DrugDispenseLine::query()
        ->whereIn('facility_id', $facilityIds)
        ->whereBetween('dispensed_date', [$fromDate->toDateString(), $toDate->toDateString()])
        ->get(),
    ];
  }

  private function resolveSingle(int $fieldNo, string $label, array $ctx, array $keyValues): int
  {
    $text = strtolower($label);
    $contains = fn(string $needle): bool => str_contains($text, strtolower($needle));

    if ($fieldNo === 2) return $this->countAttendanceReturns($ctx['attendance']);
    if ($fieldNo === 6) return $this->countCollectionMentions($ctx['doctor'], ['death']) + $ctx['deliveries']->filter(fn($d) => strtolower((string) ($d->dead ?? '')) === 'yes')->count();
    if ($fieldNo === 8 || $fieldNo === 9 || $fieldNo === 44) return $ctx['deliveries']->filter(fn($d) => strtolower((string) ($d->dead ?? '')) === 'yes')->count();
    if ($fieldNo === 10) return $ctx['deliveries']->filter(fn($d) => strtolower((string) ($d->baby_dead ?? '')) === 'yes' || trim((string) ($d->still_birth ?? '')) !== '')->count();
    if ($fieldNo === 11) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['under 5 death', 'u5 death', 'child death']);
    if ($fieldNo === 50) return $this->countCollectionMentions($ctx['postnatal'], ['depression', 'mental', 'ppd']);
    if ($fieldNo === 74) return $this->countHpvSecondDose($ctx['immunization'], $ctx['activity']);
    if ($fieldNo === 80) return $this->countCollectionMentions($ctx['activity'], ['defaulter', 'tracked', 'brought back']);
    if ($fieldNo === 81) return $this->countCollectionMentions($ctx['activity'], ['outside routine', 'outreach']);
    if ($fieldNo === 83) return $this->countCollectionMentions($ctx['activity'], ['mobile session', 'outreach session']);
    if ($fieldNo === 87) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->complementary_feeding ?? '')), 'minimum') || str_contains($this->normalizeText((string) ($n->complementary_feeding ?? '')), 'adequate'))->count();
    if ($fieldNo === 92) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'recovered'))->count();
    if ($fieldNo === 93) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'default'))->count();
    if ($fieldNo === 94) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'died'))->count();
    if ($fieldNo === 95) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->admission_status ?? '')), 'sfp') || str_contains($this->normalizeText((string) ($n->muac_class ?? '')), 'yellow'))->count();
    if ($fieldNo === 96) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'mam') && str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'recovered'))->count();
    if ($fieldNo === 97) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'mam') && str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'default'))->count();
    if ($fieldNo === 98) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'mam') && str_contains($this->normalizeText((string) ($n->outcome_status ?? '')), 'died'))->count();
    if ($fieldNo === 99) return $ctx['nutrition']->filter(fn($n) => str_contains($this->normalizeText((string) ($n->admission_status ?? '')), 'plw'))->count();
    if ($fieldNo === 105 || $fieldNo === 137) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['malaria', 'act']);
    if ($fieldNo === 106) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['diarrhoea', 'diarrhea', 'ors', 'zinc']);
    if ($fieldNo === 107 || $fieldNo === 113) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['severe', 'referred']);
    if ($fieldNo === 108) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['neonatal sepsis']);
    if ($fieldNo === 109) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['neonatal jaundice']);
    if ($fieldNo === 110) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['birth asphyxia']);
    if ($fieldNo === 111) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['neonatal tetanus']);
    if ($fieldNo === 112) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['severe pneumonia', 'antibiotic']);
    if ($fieldNo === 114) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['sickle', 'scd']);
    if ($fieldNo === 115) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['sickle', 'penicillin']);
    if ($fieldNo === 116) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['sickle', 'hydroxyurea']);
    if ($fieldNo === 117) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['sickle', 'referred']);
    if ($fieldNo === 118) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['non tb chest', 'chest infection']);
    if ($fieldNo === 133) return $this->countCollectionMentions($ctx['fp_followups'], ['referral', 'hiv testing', 'referred']);
    if ($fieldNo === 134) return $this->countCollectionMentions($ctx['fp_followups'], ['side effect', 'managed']);
    if ($fieldNo === 135) return $ctx['fp_followups']->filter(fn($f) => empty($f->method_supplied) && empty($f->method_change))->count();
    if ($fieldNo === 138) return $this->countAntenatalNotes($ctx['antenatal'], ['llin']);
    if ($fieldNo === 139) return $this->countCollectionMentions($ctx['doctor'], ['llin']) + $this->countCollectionMentions($ctx['nutrition'], ['llin']);
    if ($fieldNo === 140) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['malaria', 'act', 'under 5']);
    if ($fieldNo === 181) return 0;
    if ($fieldNo === 182) return 1;
    if ($fieldNo === 185) return $ctx['deliveries']->count() > 0 ? 1 : 0;

    if ($fieldNo === 1 || $contains('general attendance')) {
      return $ctx['attendance']->count();
    }
    if ($fieldNo === 2 || $contains('old attendance')) {
      return 0;
    }
    if ($fieldNo === 3 || $fieldNo === 4 || $contains('persons admitted')) {
      return $ctx['inpatient_admissions']->count();
    }
    if ($fieldNo === 5 || $contains('inpatient discharges')) {
      return $ctx['inpatient_discharges']->count();
    }
    if ($contains('maternal mortality') || $contains('maternal deaths')) {
      return $ctx['deliveries']->filter(fn($d) => strtolower((string) ($d->dead ?? '')) === 'yes')->count();
    }
    if ($contains('antenatal attendance 1st visit')) {
      return $ctx['antenatal']->where('pregnancy_number', 1)->count();
    }
    if ($contains('antenatal attendance re-visit')) {
      return $ctx['antenatal']->filter(fn($a) => (int) ($a->pregnancy_number ?? 1) > 1)->count();
    }
    if ($contains('4th visit')) {
      return $this->countVisitThreshold($ctx['anc_followups'], 4);
    }
    if ($contains('8th visit')) {
      return $this->countVisitThreshold($ctx['anc_followups'], 8);
    }
    if ($contains('counselled on family planning') && $contains('anc')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['family planning', 'fam. plan', 'fp']);
    }
    if ($contains('counselled on hiv')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['hiv']);
    }
    if ($contains('maternal nutrition') && $contains('anc')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['nutrition']);
    }
    if ($contains('syphilis test done')) {
      return $ctx['antenatal']->filter(fn($a) => trim((string) ($a->kahn_test ?? '')) !== '')->count();
    }
    if ($contains('syphilis test positive')) {
      return $ctx['antenatal']->filter(fn($a) => str_contains(strtolower((string) ($a->kahn_test ?? '')), 'pos'))->count();
    }
    if ($contains('syphilis test treated')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['syphilis treated', 'syphilis trt']);
    }
    if ($contains('hepatitis b test done')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['hepb', 'hbsag']);
    }
    if ($contains('hepatitis b test positive')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['hepb positive', 'hbsag positive']);
    }
    if ($contains('hepatitis b cases referred')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['hepb referral', 'hbsag referral']);
    }
    if ($contains('hepatitis c test done')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['hepc', 'hcv']);
    }
    if ($contains('hepatitis c test positive')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['hepc positive', 'hcv positive']);
    }
    if ($contains('hepatitis c cases referred')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['hepc referral', 'hcv referral']);
    }
    if ($contains('ipt1')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['ipt1']);
    }
    if ($contains('ipt2')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['ipt2']);
    }
    if ($contains('ipt3')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['ipt3']);
    }
    if ($contains('ipt>=4') || $contains('more than third dose')) {
      return $this->countAntenatalNotes($ctx['antenatal'], ['ipt4', 'ipt5', 'ipt6']);
    }
    if ($contains('tt2') || $contains('td 2 plus') || $contains('booster')) {
      return $ctx['tetanus']->filter(fn($t) => in_array((string) ($t->current_tt_dose ?? ''), ['TT2', 'TT3', 'TT4', 'TT5'], true))->count();
    }
    if ($contains('iron/folate')) {
      return $ctx['antenatal']->filter(fn($a) => !empty($a->hemoglobin))->count();
    }
    if ($contains('pregnant women referred')) {
      return $ctx['antenatal']->filter(fn($a) => str_contains(strtolower((string) ($a->pregnancy_status ?? '')), 'refer'))->count();
    }

    if ($contains('total deliveries')) {
      return $ctx['deliveries']->count();
    }
    if ($contains('skilled birth attendant')) {
      return $ctx['deliveries']->filter(fn($d) => in_array(strtolower((string) ($d->took_delivery ?? '')), ['doctor', 'nurse', 'midwife'], true))->count();
    }
    if ($contains('live births')) {
      return $ctx['deliveries']->filter(fn($d) => strtolower((string) ($d->alive ?? '')) === 'yes')->count();
    }
    if ($contains('still births')) {
      return $ctx['deliveries']->filter(fn($d) => trim((string) ($d->still_birth ?? '')) !== '')->count();
    }
    if ($contains('assisted deliveries')) {
      return $ctx['deliveries']->filter(fn($d) => in_array(strtoupper((string) ($d->mod ?? '')), ['VACUUM', 'FORCEPS'], true))->count();
    }
    if ($contains('caesarean')) {
      return $ctx['deliveries']->filter(fn($d) => strtoupper((string) ($d->mod ?? '')) === 'CS')->count();
    }
    if ($contains('postpartum haemorrhage')) {
      return $ctx['deliveries']->filter(fn($d) => str_contains(strtolower((string) ($d->complications ?? '')), 'haemorrhage') || str_contains(strtolower((string) ($d->complications ?? '')), 'hemorrhage'))->count();
    }
    if ($contains('eclampsia')) {
      return $ctx['deliveries']->filter(fn($d) => str_contains(strtolower((string) ($d->complications ?? '')), 'eclampsia') || str_contains(strtolower((string) ($d->complications ?? '')), 'pre-eclampsia'))->count();
    }
    if ($contains('exclusive breastfeeding') && $contains('women')) {
      return $ctx['postnatal']->filter(fn($p) => str_contains(strtolower((string) ($p->baby_feeding_pattern ?? '')), 'exclusive'))->count();
    }
    if ($contains('pnc check')) {
      return $ctx['postnatal']->count();
    }
    if ($contains('counselled on family planning') && $contains('pnc')) {
      return $ctx['postnatal']->filter(fn($p) => in_array((string) ($p->family_planning ?? ''), ['Counseled', 'Accepted'], true))->count();
    }
    if ($contains('provided with a family planning method')) {
      return $ctx['postnatal']->where('family_planning', 'Accepted')->count();
    }
    if ($contains('puerperal sepsis')) {
      return $ctx['postnatal']->filter(fn($p) => str_contains(strtolower((string) ($p->maternal_complications ?? '')), 'sepsis'))->count();
    }
    if ($contains('referred to other services') && $contains('pnc')) {
      return $ctx['postnatal']->filter(fn($p) => !empty($p->referral))->count();
    }

    // Direct key-backed vaccine rows (when row does not have explicit summary key marker).
    if ($contains('bcg')) return (int) ($keyValues['immunization.bcg'] ?? 0);
    if ($contains('opv0')) return (int) ($keyValues['immunization.opv0'] ?? 0);
    if ($contains('opv1')) return (int) ($keyValues['immunization.opv1'] ?? 0);
    if ($contains('opv2')) return (int) ($keyValues['immunization.opv2'] ?? 0);
    if ($contains('opv3')) return (int) ($keyValues['immunization.opv3'] ?? 0);
    if ($contains('penta 1') || $contains('penta1')) return (int) ($keyValues['immunization.penta1'] ?? 0);
    if ($contains('penta 2') || $contains('penta2')) return (int) ($keyValues['immunization.penta2'] ?? 0);
    if ($contains('penta 3') || $contains('penta3')) return (int) ($keyValues['immunization.penta3'] ?? 0);
    if ($contains('pcv 1') || $contains('pcv1')) return (int) ($keyValues['immunization.pcv1'] ?? 0);
    if ($contains('pcv 2') || $contains('pcv2')) return (int) ($keyValues['immunization.pcv2'] ?? 0);
    if ($contains('pcv 3') || $contains('pcv3')) return (int) ($keyValues['immunization.pcv3'] ?? 0);
    if ($contains('ipv 1') || $contains('ipv1')) return (int) ($keyValues['immunization.ipv1'] ?? 0);
    if ($contains('ipv 2') || $contains('ipv2')) return (int) ($keyValues['immunization.ipv2'] ?? 0);
    if ($contains('mcv 1') || $contains('measles containing vaccine 1') || $contains('measles 1')) return (int) ($keyValues['immunization.mcv1'] ?? 0);
    if ($contains('mcv 2') || $contains('measles containing vaccine 2') || $contains('measles 2')) return (int) ($keyValues['immunization.mcv2'] ?? 0);
    if ($contains('yellow fever')) return (int) ($keyValues['immunization.yf'] ?? 0);
    if ($contains('hep b0') || $contains('hepatitis b zero')) return (int) ($keyValues['immunization.hepb0'] ?? 0);
    if ($contains('hpv') && $contains('1st')) return (int) ($keyValues['immunization.hpv'] ?? 0);
    if ($contains('hpv') && $contains('2nd')) return 0;

    if ($contains('fully immunized')) {
      return $this->estimateFullyImmunized($ctx['immunization']);
    }
    if ($contains('vitamin a') && $contains('1st')) {
      return $ctx['immunization']->filter(fn($r) => !empty($r->vita1_date))->count();
    }
    if ($contains('vitamin a') && $contains('2nd')) {
      return $ctx['immunization']->filter(fn($r) => !empty($r->vita2_date))->count();
    }
    if ($contains('deworming')) {
      return $ctx['nutrition']->filter(fn($n) => str_contains(strtolower((string) ($n->age_group ?? '')), '12-59'))->count();
    }
    if ($contains('defaulters tracked')) {
      return 0;
    }
    if ($contains('outside routine session')) {
      return 0;
    }
    if ($contains('ri sessions held')) {
      return $ctx['immunization']->count() > 0 ? 1 : 0;
    }
    if ($contains('mobile/outreach sessions')) {
      return 0;
    }
    if ($contains('only bcg')) {
      return $ctx['immunization']->filter(fn($r) => !empty($r->bcg_date) && empty($r->opv0_date) && empty($r->opv1_date) && empty($r->opv2_date) && empty($r->opv3_date))->count();
    }
    if ($contains('only opv0')) {
      return $ctx['immunization']->filter(fn($r) => !empty($r->opv0_date) && empty($r->bcg_date))->count();
    }
    if ($contains('exclusively breastfed') && $contains('0-5')) return (int) ($keyValues['child_health.exclusive_breastfeeding'] ?? 0);
    if ($contains('screened for acute malnutrition') || $contains('muac')) return (int) ($keyValues['child_health.muac_screened'] ?? 0);
    if ($contains('severe acute malnutrition') && $contains('new cases')) return (int) ($keyValues['child_health.sam_new_cases'] ?? 0);
    if ($contains('moderate acute malnutrition') && $contains('new cases')) return (int) ($keyValues['child_health.mam_new_cases'] ?? 0);
    if ($contains('sam admitted') || ($contains('sam') && $contains('otp'))) return (int) ($keyValues['child_health.sam_admissions'] ?? 0);
    if ($contains('sam discharged') || $contains('mam discharged') || $contains('mam admitted') || $contains('plw admitted')) return 0;
    if ($contains('imci guidelines')) return $ctx['nutrition']->count();
    if ($contains('pneumonia cases')) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['pneumonia']);
    if ($contains('diarrhoea cases') || $contains('diarrhea cases')) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['diarrhoea', 'diarrhea']);
    if ($contains('confirmed malaria cases')) return $this->countMalariaCases($ctx['doctor'], $ctx['lab']);
    if ($contains('severe malnutrition cases')) return (int) ($keyValues['child_health.sam_new_cases'] ?? 0);
    if ($contains('treated with') || $contains('referred to a higher level')) return 0;
    if ($contains('neonatal sepsis') || $contains('neonatal jaundice') || $contains('birth asphyxia') || $contains('neonatal tetanus')) return 0;

    if ($contains('new acceptors')) return $ctx['fp_regs']->filter(fn($r) => $r->prior_contraceptive === false)->count();
    if ($contains('total current users')) return $ctx['fp_regs']->count();
    if ($contains('na:')) return $this->countFpMethod($ctx['fp_regs'], $text);
    if ($contains('procedures performed') || $contains('insertions performed')) return $this->countFpMethod($ctx['fp_regs'], $text);
    if ($contains('discontinued fp')) return $ctx['fp_followups']->filter(fn($f) => !empty($f->method_change))->count();
    if ($contains('side effects managed') || $contains('counselling only')) return 0;

    $tbPaths = ['mcs_results.tb', 'report_values.tb', 'report_values.afb', 'mcs_results.gene_xpert', 'report_values.gene_xpert'];
    $hivPaths = ['mcs_results.hiv', 'report_values.hiv', 'report_values.hiv_test', 'report_values.hiv_status', 'mcs_results.hiv_status'];

    if ($fieldNo === 136 || ($contains('confirmed malaria cases') && $contains('all ages'))) {
      return $this->countMalariaCases($ctx['doctor'], $ctx['lab']);
    }
    if ($fieldNo === 137) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['malaria treated', 'act']);
    if ($fieldNo === 138 || $fieldNo === 139) return 0;
    if ($fieldNo === 140) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['malaria', 'act', 'under 5']);
    if ($fieldNo === 141 || $contains('non-malaria fever')) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['non malaria fever', 'non-malaria fever']);

    if ($fieldNo === 142) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['tb suspect', 'suspected tb']);
    if ($fieldNo === 143) return max(
      $this->countMentions($ctx['doctor'], $ctx['lab'], ['tuberculosis', 'tb']),
      $this->countLabResultValues($ctx['lab'], $tbPaths, ['positive', 'detected', 'reactive'])
    );
    if ($fieldNo === 144) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['dots', 'directly observed therapy']);
    if ($fieldNo === 145) return $this->countMentionsAll($ctx['doctor'], $ctx['lab'], ['tb', 'hiv']);
    if ($fieldNo === 146) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['tb', 'under 5', 'u5', 'child']);
    if ($fieldNo === 147) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['dr-tb', 'mdr tb', 'xdr tb', 'drug resistant tb']);

    if ($fieldNo === 148) return $this->countLabResultValues($ctx['lab'], $hivPaths, ['positive', 'negative', 'reactive', 'non reactive', 'non-reactive', 'detected', 'not detected']);
    if ($fieldNo === 149) return $this->countLabResultValues($ctx['lab'], $hivPaths, ['positive', 'reactive', 'detected']);
    if ($fieldNo === 150) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['started art', 'enrolled on art', 'initiated on art']);
    if ($fieldNo === 151) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['on art', 'receiving art', 'art refill']);
    if ($fieldNo === 152) return $this->countMentionsAll($ctx['doctor'], $ctx['lab'], ['hiv', 'ipt']);
    if ($fieldNo === 153) return $this->countAntenatalNotes($ctx['antenatal'], ['hiv test', 'tested hiv', 'hiv counselling']);
    if ($fieldNo === 154) return $this->countAntenatalNotes($ctx['antenatal'], ['hiv positive', 'tested positive hiv']);
    if ($fieldNo === 155) return $this->countAntenatalNotes($ctx['antenatal'], ['pmtct', 'arv']);
    if ($fieldNo === 156) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['hiv exposed infant', 'infant arv prophylaxis', 'nevirapine prophylaxis']);
    if ($fieldNo === 157) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['hiv pcr', 'pcr eid', 'eid pcr']);
    if ($fieldNo === 158) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['hiv pcr positive', 'pcr positive']);
    if ($fieldNo === 159) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['pcr positive enrolled art', 'infant art after pcr']);
    if ($fieldNo === 160) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['pnc hiv test', 'postnatal hiv test']);
    if ($fieldNo === 161) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['new hiv diagnosis pnc', 'postnatal hiv positive']);
    if ($fieldNo === 162) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['hiv referral pnc']);
    if ($fieldNo === 163) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['hiv referral']);

    if ($fieldNo === 164 || $contains('sti cases')) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['sti', 'syphilis', 'gonorrhea', 'gonorrhoea', 'chlamydia']);
    if ($fieldNo === 165) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['sti treated', 'treated syphilis', 'treated gonorrhea', 'treated chlamydia']);
    if ($fieldNo === 166) return $this->countLabResultValues($ctx['lab'], ['mcs_results.hbsag', 'report_values.hbsag'], ['positive', 'negative', 'reactive', 'non reactive', 'non-reactive']);
    if ($fieldNo === 167) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['hbv referral', 'hepatitis b referral']);
    if ($fieldNo === 168) return $this->countLabResultValues($ctx['lab'], ['mcs_results.hcv', 'report_values.hcv'], ['positive', 'negative', 'reactive', 'non reactive', 'non-reactive']);
    if ($fieldNo === 169) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['hcv referral', 'hepatitis c referral']);

    if ($fieldNo === 170) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['hypertension', 'high blood pressure']);
    if ($fieldNo === 171) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['diabetes', 'hyperglycemia', 'hyperglycaemia']);
    if ($fieldNo === 172) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['ncd', 'hypertension follow-up', 'diabetes follow-up']);
    if ($fieldNo === 173) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['cancer screening', 'cervical screening', 'pap smear', 'breast screening']);
    if ($fieldNo === 174) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['road traffic accident', 'rta']);
    if ($fieldNo === 175) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['injury', 'trauma', 'fracture', 'burn']);
    if ($fieldNo === 176) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['mental health', 'depression', 'anxiety', 'psychosis']);
    if ($fieldNo === 177) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['obstetric fistula', 'fistula']);
    if ($fieldNo === 178) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['ntd', 'onchocerciasis', 'schistosomiasis', 'lymphatic filariasis', 'leprosy']);
    if ($fieldNo === 179) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['adverse drug reaction', 'adr']);
    if ($fieldNo === 180) return $this->countMentions($ctx['doctor'], $ctx['lab'], ['pharmacovigilance', 'npc adr', 'national pharmacovigilance']);

    if ($fieldNo === 181 || $contains('stock-outs')) return 0;
    if ($fieldNo === 182 || $contains('functional laboratory services')) return 1;
    if ($fieldNo === 183 || $contains('referred out')) return $ctx['referrals']->count();
    if ($fieldNo === 184 || $contains('referred into')) return $this->countReferralStatusMentions($ctx['referrals'], ['referred in', 'inbound', 'into facility', 'received']);
    if ($fieldNo === 185 || $contains('emonc')) return 0;

    return 0;
  }

  private function countVisitThreshold(Collection $followups, int $threshold): int
  {
    return $followups->groupBy('patient_id')->map(fn($rows) => $rows->count())->filter(fn($count) => $count >= $threshold)->count();
  }

  private function countAntenatalNotes(Collection $antenatal, array $needles): int
  {
    return $antenatal->filter(function ($row) use ($needles) {
      $text = strtolower(trim(
        (string) ($row->special_instructions ?? '') . ' ' .
          (string) ($row->special_points ?? '') . ' ' .
          (string) ($row->comments ?? '')
      ));
      if ($text === '') {
        return false;
      }
      foreach ($needles as $needle) {
        if (str_contains($text, strtolower($needle))) {
          return true;
        }
      }
      return false;
    })->count();
  }

  private function countMalariaCases(Collection $doctor, Collection $lab): int
  {
    $doctorCount = $doctor->filter(fn($r) => str_contains(strtolower((string) ($r->final_diagnosis ?? '')), 'malaria'))->count();
    $labCount = $lab->filter(fn($r) => str_contains(strtolower((string) ($r->clinician_diagnosis ?? '')), 'malaria'))->count();
    return $doctorCount + $labCount;
  }

  private function countMentions(Collection $doctor, Collection $lab, array $needles): int
  {
    $contains = function (string $text) use ($needles): bool {
      foreach ($needles as $needle) {
        if (str_contains($text, $this->normalizeText((string) $needle))) {
          return true;
        }
      }
      return false;
    };

    $doctorCount = $doctor->filter(fn($r) => $contains($this->doctorSearchText($r)))->count();
    $labCount = $lab->filter(fn($r) => $contains($this->labSearchText($r)))->count();
    return $doctorCount + $labCount;
  }

  private function countMentionsAll(Collection $doctor, Collection $lab, array $needles): int
  {
    $containsAll = function (string $text) use ($needles): bool {
      foreach ($needles as $needle) {
        if (!str_contains($text, $this->normalizeText((string) $needle))) {
          return false;
        }
      }
      return true;
    };

    $doctorCount = $doctor->filter(fn($r) => $containsAll($this->doctorSearchText($r)))->count();
    $labCount = $lab->filter(fn($r) => $containsAll($this->labSearchText($r)))->count();
    return $doctorCount + $labCount;
  }

  private function countLabResultValues(Collection $lab, array $paths, array $acceptedValues): int
  {
    $accepted = array_map(fn($v) => $this->normalizeText((string) $v), $acceptedValues);

    return $lab->filter(function ($row) use ($paths, $accepted): bool {
      foreach ($paths as $path) {
        $value = data_get($row, $path);
        if ($value === null || $value === '') {
          continue;
        }

        if (is_array($value) || is_object($value)) {
          $value = json_encode($value);
        }

        $text = $this->normalizeText((string) $value);
        foreach ($accepted as $expected) {
          if ($expected !== '' && ($text === $expected || str_contains($text, $expected))) {
            return true;
          }
        }
      }

      return false;
    })->count();
  }

  private function countReferralStatusMentions(Collection $referrals, array $needles): int
  {
    return $referrals->filter(function ($row) use ($needles): bool {
      $status = $this->normalizeText((string) ($row->status ?? ''));
      if ($status === '') {
        return false;
      }
      foreach ($needles as $needle) {
        if (str_contains($status, $this->normalizeText((string) $needle))) {
          return true;
        }
      }
      return false;
    })->count();
  }

  private function doctorSearchText($row): string
  {
    return $this->normalizeText(
      (string) ($row->final_diagnosis ?? '') . ' ' .
        (string) ($row->assessment_note ?? '')
    );
  }

  private function labSearchText($row): string
  {
    $payload = '';
    if (isset($row->report_values)) {
      $payload .= ' ' . (string) (is_string($row->report_values) ? $row->report_values : json_encode($row->report_values));
    }
    if (isset($row->mcs_results)) {
      $payload .= ' ' . (string) (is_string($row->mcs_results) ? $row->mcs_results : json_encode($row->mcs_results));
    }

    return $this->normalizeText(
      (string) ($row->clinician_diagnosis ?? '') . ' ' . $payload
    );
  }

  private function normalizeText(string $text): string
  {
    $text = strtolower(str_replace(['_', '-'], ' ', $text));
    return trim(preg_replace('/\s+/', ' ', $text) ?? '');
  }

  private function estimateFullyImmunized(Collection $immunization): int
  {
    return $immunization->groupBy(function ($row) {
      if (!empty($row->linked_child_id)) {
        return 'child:' . $row->linked_child_id;
      }
      return 'patient:' . ($row->patient_id ?? 'unknown');
    })->filter(function (Collection $rows) {
      return $rows->contains(fn($r) => !empty($r->bcg_date))
        && $rows->contains(fn($r) => !empty($r->opv3_date))
        && $rows->contains(fn($r) => !empty($r->penta3_date))
        && $rows->contains(fn($r) => !empty($r->pcv3_date))
        && $rows->contains(fn($r) => !empty($r->mr1_date));
    })->count();
  }

  private function countFpMethod(Collection $fpRegs, string $labelText): int
  {
    $containsMethod = function ($method, array $terms): bool {
      $value = strtolower((string) $method);
      foreach ($terms as $term) {
        if (str_contains($value, $term)) {
          return true;
        }
      }
      return false;
    };

    return $fpRegs->filter(function ($r) use ($labelText, $containsMethod) {
      $method = $r->contraceptive_selected ?? '';
      if (str_contains($labelText, 'oral')) return $containsMethod($method, ['oral', 'pill']);
      if (str_contains($labelText, 'injectable')) return $containsMethod($method, ['inject']);
      if (str_contains($labelText, 'implants') || str_contains($labelText, 'implant')) return $containsMethod($method, ['implant']);
      if (str_contains($labelText, 'iud')) return $containsMethod($method, ['iud', 'iucd']);
      if (str_contains($labelText, 'sterilization')) return $containsMethod($method, ['steril', 'tubal', 'vasect']);
      if (str_contains($labelText, 'condoms') || str_contains($labelText, 'condom')) return $containsMethod($method, ['condom']);
      if (str_contains($labelText, 'lam')) return $containsMethod($method, ['lam']);
      return false;
    })->count();
  }

  private function countAttendanceReturns(Collection $attendance): int
  {
    return $attendance->filter(function ($row): bool {
      $isNewPatient = data_get($row, 'is_new_patient');
      if (is_bool($isNewPatient)) {
        return $isNewPatient === false;
      }

      $visitType = $this->normalizeText((string) data_get($row, 'visit_type', ''));
      $attendanceType = $this->normalizeText((string) data_get($row, 'attendance_type', ''));
      $category = $this->normalizeText((string) data_get($row, 'visit_category', ''));
      $combined = trim($visitType . ' ' . $attendanceType . ' ' . $category);
      return $combined !== '' && (str_contains($combined, 'return') || str_contains($combined, 'old') || str_contains($combined, 'follow up'));
    })->count();
  }

  private function countCollectionMentions(Collection $rows, array $needles): int
  {
    return $rows->filter(function ($row) use ($needles): bool {
      $text = $this->normalizeText((string) json_encode($row));
      if ($text === '') {
        return false;
      }
      foreach ($needles as $needle) {
        if (str_contains($text, $this->normalizeText((string) $needle))) {
          return true;
        }
      }
      return false;
    })->count();
  }

  private function countHpvSecondDose(Collection $immunization, Collection $activity): int
  {
    $fromImmunization = $immunization->filter(function ($row): bool {
      $keys = ['hpv2_date', 'hpv_second_date', 'hpv_2_date'];
      foreach ($keys as $k) {
        if (!empty(data_get($row, $k))) {
          return true;
        }
      }
      return false;
    })->count();

    $fromActivity = $activity->filter(function ($row): bool {
      $vaccination = data_get($row, 'vaccination_dates', []);
      if (!is_array($vaccination)) {
        return false;
      }
      foreach (['hpv2', 'hpv_2', 'hpv_second'] as $k) {
        if (!empty($vaccination[$k])) {
          return true;
        }
      }
      return false;
    })->count();

    return $fromImmunization + $fromActivity;
  }
}
