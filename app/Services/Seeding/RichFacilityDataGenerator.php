<?php

namespace App\Services\Seeding;

use App\Models\Facility;
use App\Services\Billing\BillingService;
use App\Services\Pharmacy\DrugInventoryService;
use App\Services\Visits\VisitCollationService;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RichFacilityDataGenerator
{
  private FakerGenerator $faker;
  private BillingService $billing;
  private DrugInventoryService $inventory;
  private VisitCollationService $visits;

  private array $ctx = [];
  private array $summary = [];
  private array $usedDins = [];
  private array $officers = [];
  private array $catalog = [];
  private array $patientIds = [];
  private array $bedIds = [];
  private array $batchIds = [];

  public function __construct(BillingService $billing, DrugInventoryService $inventory, VisitCollationService $visits)
  {
    $this->faker = FakerFactory::create('en_NG');
    $this->billing = $billing;
    $this->inventory = $inventory;
    $this->visits = $visits;
  }

  public function run(array $options = []): array
  {
    $patients = max(120, (int)($options['patients'] ?? 350));
    $months = max(6, (int)($options['months'] ?? 18));
    $staff = max(15, (int)($options['staff'] ?? 50));
    $catalogTarget = max(60, (int)($options['catalog'] ?? 130));
    $beds = max(30, (int)($options['beds'] ?? 90));
    $facilityId = isset($options['facility_id']) ? (int)$options['facility_id'] : null;

    $facility = $this->resolveFacility($facilityId);
    $this->bootContext($facility, $months);

    $this->seedStaff($staff);
    $this->seedLegacyTables();
    $this->seedDrugCatalog($catalogTarget);
    $this->seedBedAndLabInfrastructure($beds);

    for ($i = 0; $i < $patients; $i++) {
      $this->seedPatientJourney();
    }

    $this->syncVisits();

    return $this->summary + [
      'facility_id' => $this->ctx['facility_id'],
      'facility_name' => $this->ctx['facility_name'],
      'patients_requested' => $patients,
      'months_back' => $months,
    ];
  }

  private function resolveFacility(?int $facilityId): Facility
  {
    $q = Facility::query()->where('is_active', true);
    if ($facilityId) {
      $q->where('id', $facilityId);
    }
    $facility = $q->orderBy('id')->first();
    if (!$facility) {
      throw new \RuntimeException('No active facility found.');
    }
    return $facility;
  }

  private function bootContext(Facility $facility, int $months): void
  {
    $stateId = (int)($facility->state_id ?: DB::table('states')->value('id'));
    $lgaId = (int)($facility->lga_id ?: DB::table('lgas')->value('id'));
    $wardId = (int)($facility->ward_id ?: DB::table('wards')->value('id'));

    if (!$stateId || !$lgaId) {
      throw new \RuntimeException('Facility geo context missing (state/lga).');
    }

    $this->ctx = [
      'facility_id' => (int)$facility->id,
      'facility_name' => (string)$facility->name,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId ?: null,
      'start' => now()->subMonths($months)->startOfDay(),
      'end' => now()->endOfDay(),
    ];

    $this->usedDins = DB::table('patients')->pluck('din')->map(fn($v) => (string)$v)->all();

    $keys = [
      'users_created', 'patients_created', 'general_regs_created', 'anc_regs_created', 'anc_followups_created',
      'tt_created', 'deliveries_created', 'postnatal_created', 'fp_regs_created', 'fp_followups_created',
      'din_activations_created', 'daily_attendance_created', 'doctor_assessments_created', 'lab_orders_created',
      'lab_tests_created', 'prescriptions_created', 'dispense_lines_created', 'invoices_created', 'invoice_lines_created',
      'payments_created', 'payment_allocations_created', 'children_created', 'immunizations_created',
      'nutrition_created', 'child_activity_created', 'referrals_created', 'reminders_created', 'dispatch_logs_created',
      'activities_created', 'drug_catalog_created', 'stock_batches_created', 'stock_movements_created',
      'bed_sections_created', 'beds_created', 'admissions_created', 'lab_batches_created', 'lab_samples_created',
      'lab_qc_created', 'lab_reagent_created', 'lab_reagent_moves_created', 'lab_equipment_logs_created',
      'visits_touched', 'visit_events_upserted',
    ];

    foreach ($keys as $k) {
      $this->summary[$k] = 0;
    }
  }

  private function seedStaff(int $count): void
  {
    $roleBuckets = [
      'Facility Administrator' => 2,
      'Data Officer' => (int)floor($count * 0.5),
      'Verification Officer' => max(5, (int)floor($count * 0.2)),
      'LGA Officer' => max(2, (int)floor($count * 0.1)),
      'State Data Administrator' => max(2, (int)floor($count * 0.08)),
      'LGA Data Administrator' => max(2, (int)floor($count * 0.08)),
    ];

    foreach ($roleBuckets as $role => $num) {
      for ($i = 0; $i < $num; $i++) {
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();
        $designation = $this->designationForRole($role);

        $id = $this->insertUser([
          'first_name' => $first,
          'last_name' => $last,
          'username' => $this->uniqueUsername($first, $last),
          'email' => $this->uniqueEmail($first, $last),
          'phone' => $this->faker->numerify('080########'),
          'password' => Hash::make('Pass@1234'),
          'role' => $role,
          'designation' => $designation,
          'facility_id' => in_array($role, ['LGA Officer', 'State Data Administrator', 'LGA Data Administrator'], true) ? null : $this->ctx['facility_id'],
          'state_id' => $this->ctx['state_id'],
          'lga_id' => $role === 'State Data Administrator' ? null : $this->ctx['lga_id'],
          'is_active' => true,
        ]);

        if ($id) {
          $this->officers[] = [
            'id' => $id,
            'name' => trim($first . ' ' . $last),
            'role' => $role,
            'designation' => $designation,
          ];
        }
      }
    }

    if (empty($this->officers)) {
      throw new \RuntimeException('No officers available for seeding metadata.');
    }
  }

  private function seedLegacyTables(): void
  {
    $fa = DB::table('users')
      ->where('facility_id', $this->ctx['facility_id'])
      ->where('role', 'Facility Administrator')
      ->orderBy('id')
      ->first();

    if ($fa && !DB::table('administrators')->where('user_id', $fa->id)->exists()) {
      DB::table('administrators')->insert([
        'user_id' => $fa->id,
        'first_name' => $fa->first_name,
        'last_name' => $fa->last_name,
        'email' => $fa->email,
        'password' => $fa->password,
        'role' => 'Facility Administrator',
        'designation' => 'Facility Data Administrator',
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    $staff = DB::table('users')
      ->where('facility_id', $this->ctx['facility_id'])
      ->whereIn('role', ['Data Officer', 'Verification Officer'])
      ->get(['id', 'first_name', 'last_name', 'email', 'role', 'designation']);

    foreach ($staff as $s) {
      if (DB::table('data_officers')->where('user_id', $s->id)->exists()) {
        continue;
      }
      DB::table('data_officers')->insert([
        'user_id' => $s->id,
        'first_name' => $s->first_name,
        'last_name' => $s->last_name,
        'email' => $s->email,
        'role' => $s->role,
        'designation' => $s->designation ?: 'Data Officer',
        'facility_id' => $this->ctx['facility_id'],
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }
  }

  private function seedDrugCatalog(int $target): void
  {
    $existing = DB::table('drug_catalog_items')
      ->where('facility_id', $this->ctx['facility_id'])
      ->get(['id', 'drug_name']);

    $base = [
      ['Paracetamol', 'Tablet', '500mg', 'Oral'],
      ['Amoxicillin', 'Capsule', '500mg', 'Oral'],
      ['Artemether-Lumefantrine', 'Tablet', '20/120mg', 'Oral'],
      ['Metronidazole', 'Tablet', '400mg', 'Oral'],
      ['Folic Acid', 'Tablet', '5mg', 'Oral'],
      ['Ferrous Sulphate', 'Tablet', '200mg', 'Oral'],
      ['Ceftriaxone', 'Injection', '1g', 'IV/IM'],
      ['Ibuprofen', 'Tablet', '400mg', 'Oral'],
      ['Azithromycin', 'Tablet', '500mg', 'Oral'],
      ['ORS', 'Sachet', 'WHO', 'Oral'],
    ];

    $toCreate = max(0, $target - $existing->count());
    for ($i = 0; $i < $toCreate; $i++) {
      $t = $base[$i % count($base)];
      $name = $t[0] . ' ' . ($existing->count() + $i + 1);
      $id = DB::table('drug_catalog_items')->insertGetId([
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'drug_name' => $name,
        'formulation' => $t[1],
        'strength' => $t[2],
        'route' => $t[3],
        'notes' => $this->faker->sentence(5),
        'reorder_level' => random_int(10, 40),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $this->summary['drug_catalog_created']++;
      $existing->push((object)['id' => $id, 'drug_name' => $name]);
    }

    $this->catalog = $existing->map(fn($r) => ['id' => (int)$r->id, 'name' => (string)$r->drug_name])->values()->all();

    $stockSubset = collect($this->catalog)->random(min(90, count($this->catalog)));
    foreach ($stockSubset as $d) {
      $received = $this->randDate()->toDateString();
      $this->inventory->stockIn([
        'facility_id' => $this->ctx['facility_id'],
        'drug_catalog_item_id' => $d['id'],
        'batch_number' => 'B' . strtoupper($this->faker->bothify('##??##')),
        'received_date' => $received,
        'expiry_date' => Carbon::parse($received)->addMonths(random_int(9, 28))->toDateString(),
        'quantity_received' => random_int(150, 800),
        'unit_cost' => random_int(50, 1000),
        'supplier_name' => $this->faker->company(),
        'notes' => $this->faker->sentence(4),
        'moved_by' => $this->pickOfficer()['name'],
      ]);
      $this->summary['stock_batches_created']++;
      $this->summary['stock_movements_created']++;
    }
  }

  private function seedBedAndLabInfrastructure(int $beds): void
  {
    $sections = ['Maternity Ward', 'Pediatrics', 'General Ward', 'Emergency', 'Observation'];
    $sectionMap = DB::table('bed_sections')
      ->where('facility_id', $this->ctx['facility_id'])
      ->pluck('id', 'name')
      ->toArray();

    foreach ($sections as $s) {
      if (!isset($sectionMap[$s])) {
        $sectionMap[$s] = DB::table('bed_sections')->insertGetId([
          'facility_id' => $this->ctx['facility_id'],
          'name' => $s,
          'details' => $this->faker->sentence(4),
          'is_active' => true,
          'created_at' => now(),
          'updated_at' => now(),
        ]);
        $this->summary['bed_sections_created']++;
      }
    }

    $existingBeds = DB::table('beds')->where('facility_id', $this->ctx['facility_id'])->count();
    $toCreate = max(0, $beds - $existingBeds);
    for ($i = 0; $i < $toCreate; $i++) {
      $sec = $this->faker->randomElement($sections);
      $code = 'BED-' . strtoupper($this->faker->bothify('##??##'));
      if (DB::table('beds')->where('facility_id', $this->ctx['facility_id'])->where('bed_code', $code)->exists()) {
        continue;
      }
      $id = DB::table('beds')->insertGetId([
        'facility_id' => $this->ctx['facility_id'],
        'bed_section_id' => $sectionMap[$sec],
        'bed_code' => $code,
        'ward_section' => $sec,
        'room_label' => 'Room ' . random_int(1, 25),
        'bed_type' => $this->faker->randomElement(['general', 'private', 'observation']),
        'status' => 'available',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $this->summary['beds_created']++;
      $this->bedIds[] = (int)$id;
    }

    if (empty($this->bedIds)) {
      $this->bedIds = DB::table('beds')->where('facility_id', $this->ctx['facility_id'])->pluck('id')->map(fn($v) => (int)$v)->all();
    }

    for ($i = 0; $i < 36; $i++) {
      $code = 'LB-' . strtoupper($this->faker->bothify('##??##??'));
      if (DB::table('lab_processing_batches')->where('batch_code', $code)->exists()) {
        continue;
      }
      $id = DB::table('lab_processing_batches')->insertGetId([
        'facility_id' => $this->ctx['facility_id'],
        'batch_code' => $code,
        'test_profile' => $this->faker->randomElement(['Hematology', 'Chemistry', 'Serology', 'Microbiology']),
        'analyzer_name' => $this->faker->randomElement(['Mindray', 'Sysmex', 'Abbott', 'Roche']),
        'run_date' => $this->randDate()->toDateString(),
        'status' => $this->faker->randomElement(['scheduled', 'running', 'completed']),
        'sample_count' => random_int(4, 35),
        'notes' => $this->faker->sentence(5),
        'created_by' => $this->pickOfficer()['name'],
        'completed_by' => $this->pickOfficer()['name'],
        'completed_at' => now()->subDays(random_int(1, 90)),
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $this->batchIds[] = (int)$id;
      $this->summary['lab_batches_created']++;
    }

    for ($i = 0; $i < 40; $i++) {
      DB::table('lab_qc_logs')->insert([
        'facility_id' => $this->ctx['facility_id'],
        'qc_date' => $this->randDate()->toDateString(),
        'qc_type' => $this->faker->randomElement(['internal', 'external']),
        'test_profile' => $this->faker->randomElement(['Hematology', 'Chemistry', 'Serology']),
        'control_level' => $this->faker->randomElement(['Low', 'Normal', 'High']),
        'expected_range' => random_int(8, 12) . '-' . random_int(13, 24),
        'observed_value' => (string)random_int(8, 24),
        'status' => $this->faker->randomElement(['pass', 'pass', 'warning', 'fail']),
        'reviewed_by' => $this->pickOfficer()['name'],
        'remarks' => $this->faker->sentence(4),
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $this->summary['lab_qc_created']++;
    }

    $reagents = ['Malaria RDT Kit', 'HBsAg Kit', 'HCV Kit', 'Urinalysis Strip', 'Widal Kit'];
    foreach ($reagents as $name) {
      $rid = DB::table('lab_reagent_stocks')->insertGetId([
        'facility_id' => $this->ctx['facility_id'],
        'reagent_name' => $name,
        'lot_number' => strtoupper($this->faker->bothify('LOT-###??')),
        'unit' => 'units',
        'quantity_available' => random_int(80, 450),
        'reorder_level' => random_int(20, 70),
        'expiry_date' => now()->addMonths(random_int(8, 24))->toDateString(),
        'manufacturer' => $this->faker->company(),
        'is_active' => true,
        'notes' => $this->faker->sentence(4),
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $this->summary['lab_reagent_created']++;

      DB::table('lab_reagent_movements')->insert([
        'facility_id' => $this->ctx['facility_id'],
        'lab_reagent_stock_id' => $rid,
        'movement_type' => 'stock_in',
        'quantity' => random_int(80, 450),
        'balance_after' => DB::table('lab_reagent_stocks')->where('id', $rid)->value('quantity_available'),
        'moved_at' => now()->subDays(random_int(1, 200)),
        'moved_by' => $this->pickOfficer()['name'],
        'reference_code' => strtoupper($this->faker->bothify('LRM-###??')),
        'notes' => $this->faker->sentence(4),
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $this->summary['lab_reagent_moves_created']++;
    }

    $eq = ['Hematology Analyzer', 'Chemistry Analyzer', 'Microscope', 'Centrifuge'];
    foreach ($eq as $e) {
      for ($j = 0; $j < 6; $j++) {
        DB::table('lab_equipment_logs')->insert([
          'facility_id' => $this->ctx['facility_id'],
          'equipment_name' => $e,
          'equipment_code' => strtoupper($this->faker->bothify('EQ-###??')),
          'log_type' => $this->faker->randomElement(['calibration', 'maintenance', 'validation']),
          'performed_date' => $this->randDate()->toDateString(),
          'next_due_date' => now()->addMonths(random_int(1, 6))->toDateString(),
          'result_status' => $this->faker->randomElement(['completed', 'pending']),
          'performed_by' => $this->pickOfficer()['name'],
          'notes' => $this->faker->sentence(4),
          'created_at' => now(),
          'updated_at' => now(),
        ]);
        $this->summary['lab_equipment_logs_created']++;
      }
    }
  }

  private function seedPatientJourney(): void
  {
    $gender = $this->faker->randomElement(['Male', 'Female']);
    $dob = $this->randomDob();
    $age = Carbon::parse($dob)->age;
    $regDate = $this->randDate();
    $din = $this->nextDin();

    $first = $this->faker->firstName();
    $last = $this->faker->lastName();
    $middle = $this->faker->boolean(35) ? $this->faker->firstName() : null;
    $off = $this->pickOfficer();

    $patientId = DB::table('patients')->insertGetId([
      'din' => $din,
      'first_name' => $first,
      'middle_name' => $middle,
      'last_name' => $last,
      'gender' => $gender,
      'date_of_birth' => $dob,
      'phone' => $this->faker->numerify('080########'),
      'email' => $this->uniqueEmail($first, $last),
      'state_id' => $this->ctx['state_id'],
      'lga_id' => $this->ctx['lga_id'],
      'ward_id' => $this->ctx['ward_id'],
      'facility_id' => $this->ctx['facility_id'],
      'registration_date' => $regDate->toDateString(),
      'is_active' => true,
      'is_nhis_subscriber' => $this->faker->boolean(40),
      'nhis_number' => $this->faker->boolean(40) ? strtoupper($this->faker->bothify('NHIS-#####')) : null,
      'nhis_provider' => $this->faker->boolean(40) ? $this->faker->randomElement(['NHIA', 'AXA', 'Hygeia']) : null,
      'nhis_expiry_date' => $this->faker->boolean(30) ? now()->addMonths(random_int(2, 24))->toDateString() : null,
      'nhis_plan_type' => $this->faker->boolean(40) ? $this->faker->randomElement(['Individual', 'Family', 'Corporate']) : null,
      'nhis_principal_name' => $this->faker->boolean(25) ? $this->faker->name() : null,
      'nhis_principal_number' => $this->faker->boolean(25) ? strtoupper($this->faker->bothify('PN-#####')) : null,
      'created_at' => $regDate,
      'updated_at' => now(),
    ]);
    $this->summary['patients_created']++;
    $this->patientIds[] = (int)$patientId;

    $patientUserId = $this->insertUser([
      'first_name' => $first,
      'last_name' => $last,
      'username' => $this->uniqueUsername($first, $last),
      'email' => $this->uniqueEmail($first, $last),
      'phone' => $this->faker->numerify('080########'),
      'password' => Hash::make('Pass@1234'),
      'role' => 'Patient',
      'designation' => 'Patient',
      'facility_id' => $this->ctx['facility_id'],
      'state_id' => $this->ctx['state_id'],
      'lga_id' => $this->ctx['lga_id'],
      'is_active' => true,
    ]);

    DB::table('general_patients_registrations')->insert([
      'patient_id' => $patientId,
      'facility_id' => $this->ctx['facility_id'],
      'marital_status' => $this->faker->randomElement(['Single', 'Married', 'Widowed', 'Divorced']),
      'occupation' => $this->faker->jobTitle(),
      'religion' => $this->faker->randomElement(['Christianity', 'Islam', 'Other']),
      'place_of_origin' => $this->faker->city(),
      'tribe' => $this->faker->randomElement(['Hausa', 'Yoruba', 'Igbo', 'Tiv', 'Fulani']),
      'home_address' => $this->faker->address(),
      'town' => $this->faker->city(),
      'landmark' => $this->faker->streetName(),
      'nok_name' => $this->faker->name(),
      'nok_relationship' => $this->faker->randomElement(['Spouse', 'Parent', 'Sibling', 'Guardian']),
      'nok_phone' => $this->faker->numerify('080########'),
      'officer_name' => $off['name'],
      'officer_role' => $off['role'],
      'officer_designation' => $off['designation'],
      'registered_by' => $off['id'],
      'registration_date' => $regDate,
      'created_at' => $regDate,
      'updated_at' => now(),
    ]);
    $this->summary['general_regs_created']++;

    $this->logActivity($patientId, 'registration', 'create', 'Patient registration completed', $off['name'], ['din' => $din], $regDate);

    $visitDates = $this->seedAttendance($patientId, $patientUserId, $din, $first, $middle, $last, $gender, $dob, $age, $off);
    $this->seedClinical($patientId, $visitDates, $off, $age, $gender);

    if ($gender === 'Female' && $age >= 16 && $age <= 49) {
      $this->seedAntenatal($patientId, $off, $regDate, $first, $middle, $last, $din, $age);
      $this->seedFamilyPlanning($patientId, $off, $regDate);
      if ($this->faker->boolean(70)) {
        $this->seedChildHealth($patientId, $off);
      }
    }

    if ($this->faker->boolean(30)) {
      $this->seedReferrals($patientId, $off);
    }
    if ($this->faker->boolean(45)) {
      $this->seedReminders($patientId, $off);
    }
    if (!empty($this->bedIds) && $this->faker->boolean(8)) {
      $this->seedAdmission($patientId, $off);
    }
  }

  private function seedAttendance(int $patientId, ?int $patientUserId, string $din, string $first, ?string $middle, string $last, string $gender, string $dob, int $age, array $off): array
  {
    $dates = [];
    for ($i = 0; $i < random_int(4, 14); $i++) {
      $d = $this->randDate()->startOfDay();
      $dates[$d->toDateString()] = $d;
    }
    if ($this->faker->boolean(35)) {
      $today = now()->startOfDay();
      $dates[$today->toDateString()] = $today;
    }
    ksort($dates);

    foreach ($dates as $d) {
      $checkIn = $d->copy()->setTime(random_int(7, 11), random_int(0, 59), 0);
      DB::table('din_activations')->insert([
        'patient_id' => $patientId,
        'facility_id' => $this->ctx['facility_id'],
        'visit_date' => $d->toDateString(),
        'check_in_time' => $checkIn->format('H:i:s'),
        'patient_din' => $din,
        'patient_first_name' => $first,
        'patient_middle_name' => $middle,
        'patient_last_name' => $last,
        'patient_phone' => $this->faker->numerify('080########'),
        'patient_age' => $age,
        'patient_gender' => $gender,
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $checkIn,
        'updated_at' => now(),
      ]);
      $this->summary['din_activations_created']++;

      if ($patientUserId && $this->faker->boolean(80)) {
        DB::table('daily_attendances')->insert([
          'user_id' => $patientUserId,
          'facility_id' => $this->ctx['facility_id'],
          'state_id' => $this->ctx['state_id'],
          'lga_id' => $this->ctx['lga_id'],
          'ward_id' => $this->ctx['ward_id'],
          'month_year' => $d->copy()->startOfMonth()->toDateString(),
          'visit_date' => $d->toDateString(),
          'date_of_birth' => $dob,
          'gender' => $gender,
          'age_group' => $this->ageGroup($age),
          'address' => $this->faker->address(),
          'state_of_origin_id' => $this->ctx['state_id'],
          'phone' => $this->faker->numerify('080########'),
          'first_contact' => $this->faker->boolean(25),
          'next_of_kin_name' => $this->faker->name(),
          'next_of_kin_relation' => $this->faker->randomElement(['Parent', 'Sibling', 'Spouse']),
          'next_of_kin_address' => $this->faker->address(),
          'next_of_kin_phone' => $this->faker->numerify('080########'),
          'officer_name' => $off['name'],
          'officer_role' => $off['role'],
          'officer_designation' => $off['designation'],
          'created_at' => $checkIn,
          'updated_at' => now(),
        ]);
        $this->summary['daily_attendance_created']++;
      }

      $this->logActivity($patientId, 'attendance', 'check-in', 'Patient check-in completed', $off['name'], ['visit_date' => $d->toDateString()], $checkIn);
    }

    return array_values($dates);
  }

  private function seedClinical(int $patientId, array $visitDates, array $off, int $age, string $gender): void
  {
    $diagnoses = ['Uncomplicated malaria', 'Upper respiratory infection', 'Hypertension', 'Gastroenteritis', 'UTI', 'General review'];
    $tests = ['Malaria Parasite', 'FBS', 'PCV', 'HBsAg', 'HCV', 'Urinalysis', 'Widal'];

    for ($i = 0; $i < random_int(2, 7); $i++) {
      $visit = !empty($visitDates) ? Carbon::parse($this->faker->randomElement($visitDates)) : $this->randDate();
      $requiresLab = $this->faker->boolean(70);
      $requiresDrugs = $this->faker->boolean(75);
      $dx = $this->faker->randomElement($diagnoses);

      $assessmentId = DB::table('doctor_assessments')->insertGetId([
        'patient_id' => $patientId,
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'doctor_user_id' => $off['id'],
        'month_year' => $visit->copy()->startOfMonth()->toDateString(),
        'visit_date' => $visit->toDateString(),
        'next_appointment_date' => $this->faker->boolean(55) ? $visit->copy()->addDays(random_int(7, 45))->toDateString() : null,
        'assessment_note' => $this->faker->paragraph(2),
        'management_plan' => $this->faker->sentence(10),
        'advice_to_patient' => $this->faker->sentence(8),
        'final_diagnosis' => $dx,
        'requires_lab_tests' => $requiresLab,
        'requires_drugs' => $requiresDrugs,
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $visit->copy()->setTime(random_int(9, 15), random_int(0, 59)),
        'updated_at' => now(),
      ]);
      $this->summary['doctor_assessments_created']++;
      $this->logActivity($patientId, 'doctor_assessment', 'create', 'Doctor assessment recorded', $off['name'], ['assessment_id' => $assessmentId], $visit);

      if ($requiresLab) {
        for ($j = 0; $j < random_int(1, 4); $j++) {
          $status = $this->faker->randomElement(['pending', 'completed', 'completed']);
          $testName = $this->faker->randomElement($tests);
          $orderId = DB::table('lab_test_orders')->insertGetId([
            'doctor_assessment_id' => $assessmentId,
            'patient_id' => $patientId,
            'facility_id' => $this->ctx['facility_id'],
            'state_id' => $this->ctx['state_id'],
            'lga_id' => $this->ctx['lga_id'],
            'ward_id' => $this->ctx['ward_id'],
            'month_year' => $visit->copy()->startOfMonth()->toDateString(),
            'visit_date' => $visit->toDateString(),
            'test_name' => $testName,
            'specimen' => $this->faker->randomElement(['Blood', 'Urine', 'Stool', 'Serum']),
            'priority' => $this->faker->randomElement(['Routine', 'Urgent']),
            'instructions' => $this->faker->sentence(6),
            'status' => $status,
            'requested_by' => $off['name'],
            'requested_at' => $visit,
            'created_at' => $visit,
            'updated_at' => now(),
          ]);
          $this->summary['lab_orders_created']++;

          if ($status === 'completed') {
            $resultDate = $visit->copy()->addDays(random_int(0, 3));
            $labId = DB::table('lab_tests')->insertGetId([
              'patient_id' => $patientId,
              'facility_id' => $this->ctx['facility_id'],
              'state_id' => $this->ctx['state_id'],
              'lga_id' => $this->ctx['lga_id'],
              'ward_id' => $this->ctx['ward_id'],
              'month_year' => $resultDate->copy()->startOfMonth()->toDateString(),
              'visit_date' => $resultDate->toDateString(),
              'lab_no' => strtoupper($this->faker->bothify('LAB-#####')),
              'specimen' => $this->faker->randomElement(['Blood', 'Urine', 'Stool']),
              'clinician_diagnosis' => $dx,
              'age_sex' => $age . 'yrs / ' . strtoupper(substr($gender, 0, 1)),
              'examination' => $testName,
              'report_values' => json_encode(['fbs' => random_int(65, 170), 'pcv' => random_int(22, 45), 'hb' => round($this->faker->randomFloat(1, 7.5, 15.0), 1), 'mp' => $this->faker->randomElement(['Positive', 'Negative'])]),
              'mcs_results' => json_encode(['hbsag' => $this->faker->randomElement(['Positive', 'Negative', 'N/A']), 'hcv' => $this->faker->randomElement(['Positive', 'Negative', 'N/A'])]),
              'urinalysis_results' => json_encode(['protein' => $this->faker->randomElement(['Negative', 'Trace', '+1']), 'glucose' => $this->faker->randomElement(['Negative', 'Trace', '+1'])]),
              'microscopy_results' => json_encode(['pus_cells' => $this->faker->randomElement(['Nil', '+', '++']), 'rbc' => $this->faker->randomElement(['Nil', '+'])]),
              'sensitivity_results' => json_encode(['erythromycin' => $this->faker->randomElement(['S', 'R', 'I'])]),
              'comment' => $this->faker->sentence(5),
              'mlt_sign' => $off['name'],
              'sign_date' => $resultDate->toDateString(),
              'summary_map' => json_encode(['mcs_positive_count' => random_int(0, 2)]),
              'officer_name' => $off['name'],
              'officer_role' => $off['role'],
              'officer_designation' => $off['designation'],
              'created_at' => $resultDate,
              'updated_at' => now(),
            ]);
            $this->summary['lab_tests_created']++;

            DB::table('lab_test_orders')->where('id', $orderId)->update([
              'completed_lab_test_id' => $labId,
              'completed_at' => $resultDate,
              'completed_by' => $off['name'],
              'completion_notes' => 'Completed in lab record ' . $labId,
              'updated_at' => now(),
            ]);

            if (!empty($this->batchIds)) {
              DB::table('lab_samples')->insert([
                'facility_id' => $this->ctx['facility_id'],
                'patient_id' => $patientId,
                'lab_test_order_id' => $orderId,
                'processing_batch_id' => $this->faker->randomElement($this->batchIds),
                'accession_no' => $this->uniqueCode('lab_samples', 'accession_no', 'ACC-######'),
                'test_name' => $testName,
                'specimen_type' => $this->faker->randomElement(['Blood', 'Urine', 'Stool']),
                'sample_status' => $this->faker->randomElement(['received', 'processing', 'completed']),
                'collected_at' => $resultDate->copy()->setTime(random_int(8, 10), random_int(0, 59)),
                'received_at' => $resultDate->copy()->setTime(random_int(10, 12), random_int(0, 59)),
                'received_by' => $off['name'],
                'remarks' => $this->faker->sentence(4),
                'created_at' => now(),
                'updated_at' => now(),
              ]);
              $this->summary['lab_samples_created']++;
            }
          }
        }
      }

      if ($requiresDrugs && !empty($this->catalog)) {
        $dispenseCode = preg_replace('/\D/', '', now()->format('YmdHis')) . random_int(1000, 9999);
        $dispensedRows = [];
        for ($m = 0; $m < random_int(1, 4); $m++) {
          $drug = $this->faker->randomElement($this->catalog);
          $qty = (float)random_int(1, 4);
          $status = $this->faker->randomElement(['pending', 'dispensed', 'dispensed', 'cancelled']);
          $dispensedDate = $status === 'dispensed' ? $visit->copy()->addDays(random_int(0, 2)) : null;
          $pid = DB::table('prescriptions')->insertGetId([
            'doctor_assessment_id' => $assessmentId,
            'patient_id' => $patientId,
            'facility_id' => $this->ctx['facility_id'],
            'state_id' => $this->ctx['state_id'],
            'lga_id' => $this->ctx['lga_id'],
            'ward_id' => $this->ctx['ward_id'],
            'month_year' => $visit->copy()->startOfMonth()->toDateString(),
            'prescribed_date' => $visit->toDateString(),
            'drug_name' => $drug['name'],
            'dosage' => $this->faker->randomElement(['1 tablet', '2 tablets', '5ml']),
            'frequency' => $this->faker->randomElement(['OD', 'BID', 'TID']),
            'duration' => $this->faker->randomElement(['3 days', '5 days', '7 days']),
            'route' => $this->faker->randomElement(['Oral', 'IV', 'IM']),
            'instructions' => $this->faker->sentence(5),
            'quantity_prescribed' => $qty,
            'quantity_dispensed' => $status === 'dispensed' ? $qty : null,
            'status' => $status,
            'prescribed_by' => $off['name'],
            'dispensed_by' => $status === 'dispensed' ? $off['name'] : null,
            'dispensed_date' => $dispensedDate ? $dispensedDate->toDateString() : null,
            'dispense_notes' => $status === 'dispensed' ? $this->faker->sentence(4) : null,
            'created_at' => $visit,
            'updated_at' => now(),
          ]);
          $this->summary['prescriptions_created']++;

          if ($status === 'dispensed' && $dispensedDate) {
            $dispensedRows[] = ['prescription_id' => (int)$pid, 'drug' => $drug, 'qty' => $qty, 'date' => $dispensedDate->copy()];
          }
        }

        if (!empty($dispensedRows)) {
          $charge = 0.0;
          foreach ($dispensedRows as $dr) {
            $available = $this->inventory->getAvailableStock($this->ctx['facility_id'], (int)$dr['drug']['id']);
            if ($available < (float)$dr['qty']) {
              $restockDate = now()->toDateString();
              $this->inventory->stockIn([
                'facility_id' => $this->ctx['facility_id'],
                'drug_catalog_item_id' => (int)$dr['drug']['id'],
                'batch_number' => 'RST-' . strtoupper($this->faker->bothify('##??##')),
                'received_date' => $restockDate,
                'expiry_date' => now()->addMonths(24)->toDateString(),
                'quantity_received' => max(100, (int)$dr['qty'] * 10),
                'unit_cost' => random_int(50, 300),
                'supplier_name' => $this->faker->company(),
                'notes' => 'Auto restock before dispensing',
                'moved_by' => $off['name'],
              ]);
              $this->summary['stock_batches_created']++;
              $this->summary['stock_movements_created']++;
            }

            $lineId = DB::table('drug_dispense_lines')->insertGetId([
              'patient_id' => $patientId,
              'facility_id' => $this->ctx['facility_id'],
              'state_id' => $this->ctx['state_id'],
              'lga_id' => $this->ctx['lga_id'],
              'ward_id' => $this->ctx['ward_id'],
              'drug_catalog_item_id' => (int)$dr['drug']['id'],
              'prescription_id' => (int)$dr['prescription_id'],
              'month_year' => $dr['date']->copy()->startOfMonth()->toDateString(),
              'dispensed_date' => $dr['date']->toDateString(),
              'dispense_code' => $dispenseCode,
              'drug_name' => $dr['drug']['name'],
              'quantity' => (float)$dr['qty'],
              'dispense_notes' => $this->faker->sentence(4),
              'dispensed_by' => $off['name'],
              'created_at' => $dr['date'],
              'updated_at' => now(),
            ]);
            $this->summary['dispense_lines_created']++;

            try {
              $this->inventory->issueStock([
                'facility_id' => $this->ctx['facility_id'],
                'drug_catalog_item_id' => (int)$dr['drug']['id'],
                'quantity' => (float)$dr['qty'],
                'patient_id' => $patientId,
                'moved_by' => $off['name'],
                'reference_type' => 'seed_dispense',
                'reference_id' => $lineId,
                'reference_code' => $dispenseCode,
              ]);
            } catch (\RuntimeException $e) {
              // One retry with guaranteed non-expired restock avoids aborting long seed runs.
              $this->inventory->stockIn([
                'facility_id' => $this->ctx['facility_id'],
                'drug_catalog_item_id' => (int)$dr['drug']['id'],
                'batch_number' => 'RTRY-' . strtoupper($this->faker->bothify('##??##')),
                'received_date' => now()->toDateString(),
                'expiry_date' => now()->addMonths(24)->toDateString(),
                'quantity_received' => max(120, (int)$dr['qty'] * 12),
                'unit_cost' => random_int(50, 350),
                'supplier_name' => $this->faker->company(),
                'notes' => 'Retry restock for seeded dispensing.',
                'moved_by' => $off['name'],
              ]);
              $this->summary['stock_batches_created']++;
              $this->summary['stock_movements_created']++;

              $this->inventory->issueStock([
                'facility_id' => $this->ctx['facility_id'],
                'drug_catalog_item_id' => (int)$dr['drug']['id'],
                'quantity' => (float)$dr['qty'],
                'patient_id' => $patientId,
                'moved_by' => $off['name'],
                'reference_type' => 'seed_dispense',
                'reference_id' => $lineId,
                'reference_code' => $dispenseCode,
              ]);
            }
            $this->summary['stock_movements_created']++;
            $charge += random_int(500, 5500);
          }

          $invoice = $this->billing->findOrCreateOpenInvoice([
            'patient_id' => $patientId,
            'facility_id' => $this->ctx['facility_id'],
            'state_id' => $this->ctx['state_id'],
            'lga_id' => $this->ctx['lga_id'],
            'ward_id' => $this->ctx['ward_id'],
            'created_by' => $off['name'],
          ], $dispensedRows[0]['date']->toDateString(), 'Generated from seeded dispensing.');
          $this->summary['invoices_created']++;

          $this->billing->addInvoiceLine($invoice, [
            'module' => 'prescriptions',
            'reference_type' => 'seed_dispense',
            'reference_code' => $dispenseCode,
            'description' => 'Drug issuance (' . count($dispensedRows) . ' item(s), code: ' . $dispenseCode . ')',
            'quantity' => 1,
            'unit_price' => $charge,
            'line_amount' => $charge,
            'service_date' => $dispensedRows[0]['date']->toDateString(),
            'created_by' => $off['name'],
          ]);
          $this->summary['invoice_lines_created']++;

          $invoice = $this->billing->refreshInvoiceTotals($invoice);
          if ($this->faker->boolean(70)) {
            $amount = $this->faker->boolean(35)
              ? (float)$invoice->outstanding_amount
              : round((float)$invoice->outstanding_amount * $this->faker->randomFloat(2, 0.3, 0.8), 2);

            $payment = $this->billing->createPaymentAndAllocate($invoice, [
              'payment_date' => $dispensedRows[0]['date']->copy()->addDays(random_int(0, 12))->toDateString(),
              'amount_received' => $amount,
              'payment_method' => $this->faker->randomElement(['Cash', 'POS', 'Transfer']),
              'notes' => 'Seeded payment',
              'received_by' => $off['name'],
              'state_id' => $this->ctx['state_id'],
              'lga_id' => $this->ctx['lga_id'],
              'ward_id' => $this->ctx['ward_id'],
            ]);
            if ($payment) {
              $this->summary['payments_created']++;
              $this->summary['payment_allocations_created']++;
            }
          }

          $this->logActivity($patientId, 'prescriptions', 'dispense', 'Medication dispensed and billed', $off['name'], ['dispense_code' => $dispenseCode], $dispensedRows[0]['date']);
        }
      }
    }
  }

  private function seedAntenatal(int $patientId, array $off, Carbon $regDate, string $first, ?string $middle, string $last, string $din, int $age): void
  {
    $lmp = $regDate->copy()->subWeeks(random_int(8, 28));
    $edd = $lmp->copy()->addDays(280);

    $ancId = DB::table('antenatal_registrations')->insertGetId([
      'patient_id' => $patientId,
      'facility_id' => $this->ctx['facility_id'],
      'pregnancy_number' => random_int(1, 5),
      'is_active' => true,
      'pregnancy_status' => $this->faker->randomElement(['active', 'delivered', 'ongoing']),
      'registration_date' => $regDate->toDateString(),
      'date_of_booking' => $regDate->toDateString(),
      'lmp' => $lmp->toDateString(),
      'edd' => $edd->toDateString(),
      'gestational_age_weeks' => random_int(8, 34),
      'gestational_age_days' => random_int(0, 6),
      'booking_trimester' => $this->faker->randomElement(['First', 'Second', 'Third']),
      'gravida' => random_int(1, 7),
      'parity' => random_int(0, 5),
      'total_births' => random_int(0, 5),
      'living_children' => random_int(0, 5),
      'abortions' => random_int(0, 2),
      'height' => random_int(145, 180),
      'weight' => random_int(50, 95),
      'blood_pressure' => random_int(100, 150) . '/' . random_int(60, 100),
      'hemoglobin' => round($this->faker->randomFloat(1, 7.0, 14.5), 1),
      'genotype' => $this->faker->randomElement(['AA', 'AS', 'AC', 'SS']),
      'blood_group_rhesus' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'O+']),
      'kahn_test' => $this->faker->randomElement(['Positive', 'Negative']),
      'urine_analysis' => $this->faker->randomElement(['Normal', 'Protein +', 'Sugar +']),
      'comments' => $this->faker->sentence(8),
      'officer_name' => $off['name'],
      'officer_role' => $off['role'],
      'officer_designation' => $off['designation'],
      'created_at' => $regDate,
      'updated_at' => now(),
    ]);
    $this->summary['anc_regs_created']++;

    for ($i = 0; $i < random_int(1, 4); $i++) {
      $d = $regDate->copy()->addWeeks(($i + 1) * random_int(2, 4));
      DB::table('antenatal_follow_up_assessments')->insert([
        'patient_id' => $patientId,
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'month_year' => $d->copy()->startOfMonth()->toDateString(),
        'visit_date' => $d->toDateString(),
        'bp' => random_int(100, 155) . '/' . random_int(60, 95),
        'pcv' => round($this->faker->randomFloat(1, 20, 42), 1),
        'weight' => round($this->faker->randomFloat(1, 52, 95), 1),
        'fundal_height' => round($this->faker->randomFloat(1, 15, 38), 1),
        'presentation_position' => $this->faker->randomElement(['Cephalic', 'Breech', 'Transverse']),
        'relation_to_brim' => $this->faker->randomElement(['Engaged', 'Not engaged']),
        'fetal_heart_rate' => random_int(120, 168),
        'urine_test' => $this->faker->randomElement(['Nil', 'Alb+', 'Sug+', 'Alb+/Sug+']),
        'oedema' => $this->faker->randomElement(['Nil', '+', '++']),
        'clinical_remarks' => $this->faker->sentence(8),
        'special_delivery_instructions' => $this->faker->sentence(6),
        'next_return_date' => $d->copy()->addWeeks(random_int(2, 4))->toDateString(),
        'xray_pelvimetry' => $this->faker->boolean(8),
        'pelvic_inlet' => $this->faker->randomElement(['Adequate', 'Borderline']),
        'pelvic_cavity' => $this->faker->randomElement(['Adequate', 'Borderline']),
        'pelvic_outlet' => $this->faker->randomElement(['Adequate', 'Borderline']),
        'hb_genotype' => $this->faker->randomElement(['AA', 'AS', 'SS']),
        'rhesus' => $this->faker->randomElement(['Positive', 'Negative']),
        'kahn_vdrl' => $this->faker->randomElement(['Reactive', 'Non-reactive']),
        'antimalarials_therapy' => $this->faker->sentence(4),
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $d,
        'updated_at' => now(),
      ]);
      $this->summary['anc_followups_created']++;
    }

    $doseLabels = ['TT1', 'TT2', 'TT3', 'TT4', 'TT5'];
    for ($dose = 1; $dose <= random_int(1, 4); $dose++) {
      $d = $regDate->copy()->addWeeks($dose * 4);
      DB::table('tetanus_vaccinations')->insert([
        'patient_id' => $patientId,
        'antenatal_registration_id' => $ancId,
        'facility_id' => $this->ctx['facility_id'],
        'visit_date' => $d->toDateString(),
        'current_tt_dose' => $doseLabels[$dose - 1],
        'dose_date' => $d->toDateString(),
        'dose_number' => $dose,
        'protection_status' => $dose >= 4 ? 'Protected' : ($dose >= 2 ? 'Partially Protected' : 'Not Protected'),
        'dose_interval' => $dose === 1 ? null : random_int(25, 40),
        'next_appointment_date' => $dose < 5 ? $d->copy()->addWeeks(4)->toDateString() : null,
        'vaccination_site' => $this->faker->randomElement(['Left Upper Arm', 'Right Upper Arm']),
        'batch_number' => strtoupper($this->faker->bothify('TT-####')),
        'expiry_date' => $d->copy()->addMonths(24)->toDateString(),
        'adverse_event' => $this->faker->randomElement(['None', 'Mild Pain', 'Swelling', 'Fever']),
        'notes' => $this->faker->sentence(4),
        'patient_din' => $din,
        'patient_first_name' => $first,
        'patient_middle_name' => $middle,
        'patient_last_name' => $last,
        'patient_phone' => $this->faker->numerify('080########'),
        'patient_age' => $age,
        'patient_gender' => 'Female',
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $d,
        'updated_at' => now(),
      ]);
      $this->summary['tt_created']++;
    }

    if ($this->faker->boolean(55)) {
      $d = $regDate->copy()->addWeeks(random_int(12, 28));
      DB::table('deliveries')->insert([
        'patient_id' => $patientId,
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'month_year' => $d->copy()->startOfMonth()->toDateString(),
        'cl_date' => $d->copy()->subDay()->toDateString(),
        'cl_sex' => 'Female',
        'toc' => $this->faker->randomElement(['Early', 'Late']),
        'seeking_care' => $this->faker->randomElement(['less24', 'more24']),
        'transportation' => $this->faker->randomElement(['ambulance', 'private', 'others']),
        'parity' => (string)random_int(0, 5),
        'dodel' => $d->toDateString(),
        'cl_phone' => $this->faker->numerify('080########'),
        'mod' => $this->faker->randomElement(['SVD', 'CS']),
        'partograph' => $this->faker->randomElement(['yes', 'no']),
        'oxytocin' => $this->faker->randomElement(['yes', 'no']),
        'misoprostol' => $this->faker->randomElement(['yes', 'no']),
        'alive' => 'yes',
        'admitted' => 'yes',
        'discharged' => $this->faker->randomElement(['yes', 'no']),
        'referred_out' => $this->faker->randomElement(['yes', 'no']),
        'pac' => $this->faker->randomElement(['yes', 'no']),
        'mother_transportation' => $this->faker->randomElement(['ambulance', 'private']),
        'dead' => $this->faker->randomElement(['no', 'no', 'yes']),
        'MDA_conducted' => $this->faker->randomElement(['yes', 'no']),
        'abortion' => $this->faker->randomElement(['yes', 'no']),
        'time_of_delivery' => $d->copy()->setTime(random_int(0, 23), random_int(0, 59))->format('H:i:s'),
        'pre_term' => $this->faker->randomElement(['yes', 'no']),
        'breathing' => $this->faker->randomElement(['yes', 'no']),
        'weight' => round($this->faker->randomFloat(1, 1.8, 4.2), 1),
        'still_birth' => $this->faker->randomElement(['none', 'none', 'fresh', 'macerated']),
        'baby_dead' => $this->faker->randomElement(['no', 'no', 'yes']),
        'live_births' => (string)random_int(1, 2),
        'baby_sex' => $this->faker->randomElement(['Male', 'Female']),
        'took_delivery' => $off['name'],
        'doctor' => $off['name'],
        'newborn_care' => $this->faker->randomElement(['yes', 'no']),
        'clamped' => $this->faker->randomElement(['yes', 'no']),
        'CKX_gel' => $this->faker->randomElement(['yes', 'no']),
        'breast' => $this->faker->randomElement(['yes', 'no']),
        'temperature' => round($this->faker->randomFloat(1, 36.0, 38.5), 1),
        'breastfeeding' => $this->faker->randomElement(['yes', 'no']),
        'postpartum' => $this->faker->randomElement(['stable', 'observe']),
        'took_del' => $off['name'],
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'blood_loss' => random_int(200, 1800),
        'gestational_age' => random_int(28, 41),
        'complications' => $this->faker->randomElement(['None', 'PPH', 'Pre-eclampsia', 'Sepsis suspicion']),
        'created_at' => $d,
        'updated_at' => now(),
      ]);
      $this->summary['deliveries_created']++;

      if ($this->faker->boolean(65)) {
        $p = $d->copy()->addDays(random_int(3, 21));
        DB::table('postnatal_records')->insert([
          'patient_id' => $patientId,
          'facility_id' => $this->ctx['facility_id'],
          'state_id' => $this->ctx['state_id'],
          'lga_id' => $this->ctx['lga_id'],
          'ward_id' => $this->ctx['ward_id'],
          'month_year' => $p->copy()->startOfMonth()->toDateString(),
          'visit_date' => $p->toDateString(),
          'delivery_date' => $d->toDateString(),
          'days_postpartum' => $d->diffInDays($p),
          'age_range' => $this->ageGroup($age),
          'parity_count' => random_int(0, 5),
          'attendance' => $this->faker->randomElement(['1st Visit', '2nd Visit', '3rd Visit']),
          'associated_problems' => $this->faker->sentence(6),
          'mother_days' => random_int(1, 30),
          'child_days' => random_int(1, 30),
          'child_sex' => $this->faker->randomElement(['Male', 'Female']),
          'nutrition_counseling' => $this->faker->randomElement(['Yes', 'No', 'Counseled']),
          'breast_examination' => $this->faker->randomElement(['Normal', 'Abnormal', 'Not Done']),
          'breastfeeding_status' => $this->faker->randomElement(['Exclusive', 'Mixed', 'Not Breastfeeding']),
          'family_planning' => $this->faker->randomElement(['Counseled', 'Accepted', 'Declined']),
          'female_genital_mutilation' => $this->faker->randomElement(['No', 'Suspected', 'Yes']),
          'vaginal_examination' => $this->faker->randomElement(['Normal', 'Abnormal', 'Not Done']),
          'packed_cell_volume' => random_int(20, 42) . '%',
          'urine_test_results' => $this->faker->randomElement(['Normal', 'Protein trace', 'Sugar trace']),
          'newborn_care' => $this->faker->randomElement(['Provided', 'Not Provided', 'Referred']),
          'kangaroo_mother_care' => $this->faker->randomElement(['Yes', 'No', 'Not Applicable']),
          'visit_outcome' => $this->faker->randomElement(['Stable', 'Referred', 'Discharged']),
          'systolic_bp' => random_int(95, 155),
          'diastolic_bp' => random_int(60, 95),
          'newborn_weight' => round($this->faker->randomFloat(1, 1.8, 4.2), 1),
          'officer_name' => $off['name'],
          'officer_role' => $off['role'],
          'officer_designation' => $off['designation'],
          'created_at' => $p,
          'updated_at' => now(),
        ]);
        $this->summary['postnatal_created']++;
      }
    }
  }

  private function seedFamilyPlanning(int $patientId, array $off, Carbon $regDate): void
  {
    if (!$this->faker->boolean(40)) {
      return;
    }

    $d = $regDate->copy()->addDays(random_int(0, 60));
    $fpId = DB::table('family_planning_registrations')->insertGetId([
      'patient_id' => $patientId,
      'facility_id' => $this->ctx['facility_id'],
      'registration_date' => $d->toDateString(),
      'client_reg_number' => strtoupper($this->faker->bothify('FP-#####')),
      'referral_source' => $this->faker->randomElement(['Self', 'PHC', 'Hospital', 'NGO']),
      'children_born_alive' => random_int(0, 6),
      'children_still_living' => random_int(0, 6),
      'miscarriages_stillbirths_abortions' => random_int(0, 3),
      'last_pregnancy_ended' => $d->copy()->subMonths(random_int(3, 48))->toDateString(),
      'last_pregnancy_result' => $this->faker->randomElement(['Live Birth', 'Stillbirth', 'Miscarriage', 'Abortion']),
      'breastfeeding' => $this->faker->boolean(40),
      'want_more_children' => $this->faker->randomElement(['Yes', 'No', 'Undecided']),
      'last_menstrual_period' => $d->copy()->subDays(random_int(3, 32))->toDateString(),
      'menstrual_cycle' => $this->faker->randomElement(['Regular', 'Irregular']),
      'cycle_duration' => random_int(25, 36),
      'medical_conditions' => json_encode($this->faker->randomElements(['HTN', 'DM', 'SCD', 'Asthma'], random_int(0, 2))),
      'smoke' => $this->faker->boolean(12),
      'last_pregnancy_complication' => $this->faker->randomElement(['Normal', 'Complicated']),
      'prior_contraceptive' => $this->faker->boolean(45),
      'prior_method' => $this->faker->randomElement(['Injectable', 'Pill', 'Condom', 'IUD', 'Implant']),
      'contraceptive_selected' => $this->faker->randomElement(['Injectable', 'Pill', 'Condom', 'IUD', 'Implant']),
      'brand_size_model' => strtoupper($this->faker->bothify('MD-####')),
      'source' => $this->faker->randomElement(['Free (Government)', 'Subsidized', 'Full Price']),
      'quality' => $this->faker->randomElement(['Accepted', 'Continuing', 'Switching']),
      'weight' => round($this->faker->randomFloat(1, 45, 95), 1),
      'blood_pressure' => random_int(95, 150) . '/' . random_int(60, 95),
      'breasts' => $this->faker->randomElement(['Normal', 'Abnormal']),
      'uterus_position' => $this->faker->randomElement(['Anteverted', 'Retroverted', 'Midposition']),
      'uterus_size' => $this->faker->randomElement(['Normal', 'Enlarged']),
      'cervix_tears' => $this->faker->boolean(8),
      'cervix_erosion' => $this->faker->boolean(10),
      'vaginal_discharge' => $this->faker->boolean(18),
      'discharge_colour' => $this->faker->randomElement(['Clear', 'Whitish', 'Yellow']),
      'discharge_odor' => $this->faker->randomElement(['None', 'Mild', 'Foul']),
      'cervix_discharge' => $this->faker->boolean(15),
      'liver_enlarged' => $this->faker->boolean(4),
      'laboratory_results' => $this->faker->sentence(6),
      'other_observations' => $this->faker->sentence(4),
      'next_appointment' => $d->copy()->addDays(random_int(30, 90))->toDateString(),
      'officer_name' => $off['name'],
      'officer_role' => $off['role'],
      'officer_designation' => $off['designation'],
      'created_at' => $d,
      'updated_at' => now(),
    ]);
    $this->summary['fp_regs_created']++;

    for ($i = 0; $i < random_int(1, 3); $i++) {
      $v = $d->copy()->addDays(($i + 1) * random_int(25, 55));
      DB::table('family_planning_follow_ups')->insert([
        'patient_id' => $patientId,
        'facility_id' => $this->ctx['facility_id'],
        'family_planning_registration_id' => $fpId,
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'month_year' => $v->copy()->startOfMonth()->toDateString(),
        'visit_date' => $v->toDateString(),
        'next_appointment_date' => $v->copy()->addDays(random_int(30, 90))->toDateString(),
        'method_change' => $this->faker->randomElement(['Y', 'N']),
        'method_supplied' => $this->faker->randomElement(['Injectable', 'Pill', 'Condom', 'Implant']),
        'brand_size_quality' => strtoupper($this->faker->bothify('BS-####')),
        'blood_pressure' => random_int(100, 145) . '/' . random_int(65, 92),
        'weight' => round($this->faker->randomFloat(1, 48, 92), 1),
        'pelvic_exam_performed' => $this->faker->randomElement(['Y', 'N']),
        'observation_notes' => $this->faker->sentence(6),
        'summary_map' => json_encode(['method_change' => $this->faker->randomElement(['Y', 'N'])]),
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $v,
        'updated_at' => now(),
      ]);
      $this->summary['fp_followups_created']++;
    }
  }

  private function seedChildHealth(int $patientId, array $off): void
  {
    for ($i = 0; $i < random_int(1, 3); $i++) {
      $dob = now()->subMonths(random_int(1, 36))->subDays(random_int(0, 25));
      $childId = DB::table('linked_children')->insertGetId([
        'linked_child_id' => $this->uniqueCode('linked_children', 'linked_child_id', 'CH###??##'),
        'parent_patient_id' => $patientId,
        'first_name' => $this->faker->firstName(),
        'last_name' => $this->faker->lastName(),
        'middle_name' => $this->faker->boolean(35) ? $this->faker->firstName() : null,
        'gender' => $this->faker->randomElement(['Male', 'Female']),
        'date_of_birth' => $dob->toDateString(),
        'relationship' => 'Child',
        'birth_weight' => round($this->faker->randomFloat(2, 1.8, 4.5), 2),
        'birth_length' => round($this->faker->randomFloat(1, 44, 56), 1),
        'birth_order' => random_int(1, 4),
        'is_active' => true,
        'facility_id' => $this->ctx['facility_id'],
        'created_by' => $off['id'],
        'updated_by' => $off['id'],
        'notes' => $this->faker->sentence(4),
        'created_at' => $dob->copy()->addDay(),
        'updated_at' => now(),
      ]);
      $this->summary['children_created']++;

      $iv = $dob->copy()->addWeeks(random_int(2, 18));
      DB::table('immunization_records')->insert([
        'patient_id' => $patientId,
        'linked_child_id' => $childId,
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'month_year' => $iv->copy()->startOfMonth()->toDateString(),
        'visit_date' => $iv->toDateString(),
        'immunization_card_no' => strtoupper($this->faker->bothify('IC-######')),
        'follow_up_address' => $this->faker->address(),
        'follow_up_phone' => $this->faker->numerify('080########'),
        'hepb0_date' => $dob->toDateString(),
        'opv0_date' => $dob->toDateString(),
        'bcg_date' => $dob->copy()->addDay()->toDateString(),
        'opv1_date' => $dob->copy()->addWeeks(6)->toDateString(),
        'penta1_date' => $dob->copy()->addWeeks(6)->toDateString(),
        'pcv1_date' => $dob->copy()->addWeeks(6)->toDateString(),
        'rota1_date' => $dob->copy()->addWeeks(6)->toDateString(),
        'opv2_date' => $dob->copy()->addWeeks(10)->toDateString(),
        'penta2_date' => $dob->copy()->addWeeks(10)->toDateString(),
        'pcv2_date' => $dob->copy()->addWeeks(10)->toDateString(),
        'rota2_date' => $dob->copy()->addWeeks(10)->toDateString(),
        'ipv1_date' => $dob->copy()->addWeeks(14)->toDateString(),
        'opv3_date' => $dob->copy()->addWeeks(14)->toDateString(),
        'penta3_date' => $dob->copy()->addWeeks(14)->toDateString(),
        'pcv3_date' => $dob->copy()->addWeeks(14)->toDateString(),
        'rota3_date' => $dob->copy()->addWeeks(14)->toDateString(),
        'mr1_date' => $dob->copy()->addMonths(9)->toDateString(),
        'yf_date' => $dob->copy()->addMonths(9)->toDateString(),
        'mr2_date' => $dob->copy()->addMonths(15)->toDateString(),
        'vita1_date' => $dob->copy()->addMonths(6)->toDateString(),
        'vita2_date' => $dob->copy()->addMonths(24)->toDateString(),
        'comments' => $this->faker->sentence(6),
        'aefi_period' => $this->faker->randomElement(['Routine Immunization', 'SIA']),
        'aefi_type' => $this->faker->randomElement(['Routine', 'Cluster']),
        'aefi_cases' => json_encode($this->faker->boolean(18) ? [[
          'age_y' => 0,
          'age_m' => random_int(2, 15),
          'last_immunization_date' => $iv->toDateString(),
          'reaction_code' => random_int(1, 28),
          'type' => $this->faker->randomElement(['Minor', 'Serious']),
          'outcome_code' => random_int(1, 4),
          'vaccine' => 'PENTA',
          'reported_date' => $iv->copy()->addDay()->toDateString(),
        ]] : []),
        'summary_map' => json_encode(['vaccines_completed' => random_int(8, 20)]),
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $iv,
        'updated_at' => now(),
      ]);
      $this->summary['immunizations_created']++;

      $nv = $dob->copy()->addMonths(random_int(4, 18));
      $muac = random_int(108, 145);
      DB::table('nutrition_records')->insert([
        'patient_id' => $patientId,
        'linked_child_id' => $childId,
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'month_year' => $nv->copy()->startOfMonth()->toDateString(),
        'visit_date' => $nv->toDateString(),
        'age_group' => $this->faker->randomElement(['0-5 months', '6-23 months', '24-59 months']),
        'infant_feeding' => $this->faker->randomElement(['Exclusive BF', 'BF + Water', 'BF with other foods', 'Not BF']),
        'complementary_feeding' => $this->faker->randomElement(['BF + Other foods', 'Other foods only', 'Not started CF']),
        'counselling_topics' => json_encode($this->faker->randomElements(['Hygiene', 'Meal frequency', 'Micronutrients'], random_int(1, 3))),
        'support_group_referred' => $this->faker->boolean(30),
        'height_cm' => round($this->faker->randomFloat(1, 55, 98), 1),
        'weight_kg' => round($this->faker->randomFloat(1, 4.0, 15.5), 1),
        'oedema' => $this->faker->randomElement(['0', '+', '++']),
        'muac_value_mm' => $muac,
        'muac_class' => $muac < 115 ? 'Red' : ($muac < 125 ? 'Yellow' : 'Green'),
        'growth_status' => $this->faker->randomElement(['Growing Well', 'Not Growing Well']),
        'supplementary_feeding_groups' => json_encode($this->faker->randomElements(['Cereals', 'Legumes', 'Animal Protein'], random_int(1, 3))),
        'mnp_given' => $this->faker->boolean(45),
        'otp_provider' => $this->faker->randomElement(['Self', 'HH', 'Not Providing OTP', 'Community Volunteer/CHIPS']),
        'admission_status' => $this->faker->randomElement(['Admitted HP OTP', 'Transferred in from another OTP/SC', 'Referred to SC', 'Does not meet OTP Admission Criteria']),
        'outcome_status' => $this->faker->randomElement(['Transferred out to another OTP/SC', 'Recovered', 'Defaulted', 'Died', 'Non-recovered']),
        'remarks' => $this->faker->sentence(5),
        'summary_map' => json_encode(['muac_screened' => 1, 'sam_new_cases' => $muac < 115 ? 1 : 0, 'mam_new_cases' => $muac >= 115 && $muac < 125 ? 1 : 0]),
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $nv,
        'updated_at' => now(),
      ]);
      $this->summary['nutrition_created']++;

      $av = $dob->copy()->addMonths(random_int(6, 20));
      $weights = [];
      for ($w = 0; $w < random_int(2, 5); $w++) {
        $ed = $av->copy()->subMonths(5 - $w);
        $weights[] = ['date' => $ed->toDateString(), 'age' => max(0, $dob->diffInMonths($ed)), 'kg' => round($this->faker->randomFloat(1, 2.5, 13.0), 1), 'notes' => $this->faker->randomElement(['stable', 'improving'])];
      }
      $bf = [];
      for ($m = 1; $m <= 24; $m++) {
        $bf[(string)$m] = $this->faker->randomElement(['E', 'P', 'BW', 'NO', null]);
      }

      DB::table('child_health_activity_records')->insert([
        'patient_id' => $patientId,
        'linked_child_id' => $childId,
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'month_year' => $av->copy()->startOfMonth()->toDateString(),
        'visit_date' => $av->toDateString(),
        'vaccination_dates' => json_encode(['bcg' => $dob->copy()->addDay()->toDateString(), 'opv1' => $dob->copy()->addWeeks(6)->toDateString(), 'penta1' => $dob->copy()->addWeeks(6)->toDateString(), 'measles1' => $dob->copy()->addMonths(9)->toDateString()]),
        'vaccination_notes' => json_encode(['bcg' => 'Given']),
        'weight_entries' => json_encode($weights),
        'breastfeeding_entries' => json_encode($bf),
        'aefi_period' => $this->faker->randomElement(['Routine Immunization', 'SIA']),
        'aefi_type' => $this->faker->randomElement(['Routine Immunization', 'SIA']),
        'aefi_cases' => json_encode($this->faker->boolean(10) ? [['age_y' => 0, 'age_m' => random_int(2, 15), 'reaction_code' => random_int(1, 28), 'type' => $this->faker->randomElement(['Minor', 'Serious']), 'outcome_code' => random_int(1, 4), 'vaccine' => 'PENTA', 'reported_date' => $av->toDateString()]] : []),
        'comments' => $this->faker->sentence(6),
        'summary_map' => json_encode(['weight_entries_count' => count($weights), 'aefi_reported_cases' => $this->faker->boolean(10) ? 1 : 0]),
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $av,
        'updated_at' => now(),
      ]);
      $this->summary['child_activity_created']++;

      $this->logActivity($patientId, 'child_health', 'update', 'Child health entries updated', $off['name'], ['linked_child_id' => $childId], $av);
    }
  }

  private function seedReferrals(int $patientId, array $off): void
  {
    for ($i = 0; $i < random_int(1, 2); $i++) {
      $d = $this->randDate();
      DB::table('referrals')->insert([
        'patient_id' => $patientId,
        'facility_id' => $this->ctx['facility_id'],
        'state_id' => $this->ctx['state_id'],
        'lga_id' => $this->ctx['lga_id'],
        'ward_id' => $this->ctx['ward_id'],
        'month_year' => $d->copy()->startOfMonth()->toDateString(),
        'referral_date' => $d->toDateString(),
        'serial_no' => strtoupper($this->faker->bothify('RF-#####')),
        'referred_from' => $this->ctx['facility_name'],
        'referred_to' => $this->faker->company() . ' Hospital',
        'requested_service_code' => strtoupper($this->faker->bothify('SRV-##')),
        'services_selected' => json_encode($this->faker->randomElements(['Lab', 'Imaging', 'Surgery', 'Blood'], random_int(1, 3))),
        'service_provided' => $this->faker->randomElement(['Yes', 'No']),
        'date_completed' => $this->faker->boolean(65) ? $d->copy()->addDays(random_int(1, 10))->toDateString() : null,
        'follow_up_needed' => $this->faker->randomElement(['Yes', 'No']),
        'transport_mode' => $this->faker->randomElement(['ambulance', 'ets', 'others']),
        'time_in' => sprintf('%02d:%02d:00', random_int(7, 13), random_int(0, 59)),
        'time_out' => sprintf('%02d:%02d:00', random_int(14, 20), random_int(0, 59)),
        'completed_by' => $off['name'],
        'completed_designation' => $off['designation'],
        'completed_date' => $this->faker->boolean(70) ? $d->copy()->addDays(random_int(1, 12))->toDateString() : null,
        'focal_person' => $this->faker->name(),
        'focal_date' => $this->faker->boolean(70) ? $d->copy()->addDays(random_int(1, 12))->toDateString() : null,
        'summary_map' => json_encode(['follow_up_needed' => $this->faker->randomElement(['Yes', 'No'])]),
        'officer_name' => $off['name'],
        'officer_role' => $off['role'],
        'officer_designation' => $off['designation'],
        'created_at' => $d,
        'updated_at' => now(),
      ]);
      $this->summary['referrals_created']++;
    }
  }

  private function seedReminders(int $patientId, array $off): void
  {
    for ($i = 0; $i < random_int(1, 3); $i++) {
      $d = $this->randDate()->addDays(random_int(0, 14));
      $channels = $this->faker->randomElement([['sms'], ['email'], ['sms', 'email']]);
      $status = $this->faker->randomElement(['pending', 'queued', 'sent', 'failed', 'canceled']);
      $rid = DB::table('reminders')->insertGetId([
        'patient_id' => $patientId,
        'facility_id' => $this->ctx['facility_id'],
        'source_module' => $this->faker->randomElement(['doctor_assessments', 'anc_follow_up', 'family_planning', 'appointments']),
        'source_record_id' => random_int(1, 5000),
        'title' => $this->faker->randomElement(['Appointment Reminder', 'Medication Follow-up', 'Immunization Reminder']),
        'message' => $this->faker->sentence(10),
        'reminder_date' => $d->toDateString(),
        'reminder_time' => sprintf('%02d:%02d:00', random_int(7, 18), random_int(0, 59)),
        'status' => $status,
        'channels' => json_encode($channels),
        'recipient_phone' => $this->faker->numerify('080########'),
        'recipient_email' => $this->uniqueEmail($this->faker->firstName(), $this->faker->lastName()),
        'created_by' => $off['name'],
        'created_by_role' => $off['role'],
        'meta' => json_encode(['priority' => $this->faker->randomElement(['low', 'medium', 'high'])]),
        'queued_at' => in_array($status, ['queued', 'sent', 'failed'], true) ? $d->copy()->setTime(random_int(7, 11), random_int(0, 59)) : null,
        'sent_at' => $status === 'sent' ? $d->copy()->setTime(random_int(7, 20), random_int(0, 59)) : null,
        'failed_at' => $status === 'failed' ? $d->copy()->setTime(random_int(7, 20), random_int(0, 59)) : null,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
      $this->summary['reminders_created']++;

      foreach ($channels as $ch) {
        DB::table('reminder_dispatch_logs')->insert([
          'reminder_id' => $rid,
          'patient_id' => $patientId,
          'facility_id' => $this->ctx['facility_id'],
          'channel' => $ch,
          'status' => $status === 'canceled' ? 'skipped' : $this->faker->randomElement(['queued', 'sent', 'failed', 'skipped']),
          'provider' => 'placeholder',
          'recipient' => $ch === 'sms' ? $this->faker->numerify('080########') : $this->uniqueEmail($this->faker->firstName(), $this->faker->lastName()),
          'subject' => $ch === 'email' ? 'Reminder Notification' : null,
          'message' => $this->faker->sentence(8),
          'provider_message' => $this->faker->sentence(4),
          'provider_payload' => json_encode(['mock' => true]),
          'sent_at' => $status === 'sent' ? now()->subMinutes(random_int(5, 120)) : null,
          'failed_at' => $status === 'failed' ? now()->subMinutes(random_int(5, 120)) : null,
          'created_at' => now(),
          'updated_at' => now(),
        ]);
        $this->summary['dispatch_logs_created']++;
      }
    }
  }

  private function seedAdmission(int $patientId, array $off): void
  {
    $bedId = DB::table('beds')
      ->where('facility_id', $this->ctx['facility_id'])
      ->where('is_active', true)
      ->where('status', 'available')
      ->inRandomOrder()
      ->value('id');

    if (!$bedId) {
      return;
    }

    $d = $this->randDate();
    $status = $this->faker->randomElement(['admitted', 'discharged', 'discharged']);
    $dischargedAt = $status === 'discharged' ? $d->copy()->addDays(random_int(1, 9)) : null;
    $sectionId = DB::table('beds')->where('id', $bedId)->value('bed_section_id');

    DB::table('inpatient_admissions')->insert([
      'facility_id' => $this->ctx['facility_id'],
      'patient_id' => $patientId,
      'bed_section_id' => $sectionId,
      'bed_id' => $bedId,
      'admission_code' => $this->uniqueCode('inpatient_admissions', 'admission_code', 'ADM-######'),
      'admitted_at' => $d->copy()->setTime(random_int(8, 16), random_int(0, 59)),
      'admitted_by' => $off['name'],
      'admission_reason' => $this->faker->sentence(6),
      'status' => $status,
      'is_active' => $status === 'admitted',
      'discharged_at' => $dischargedAt ? $dischargedAt->copy()->setTime(random_int(8, 15), random_int(0, 59)) : null,
      'discharged_by' => $status === 'discharged' ? $off['name'] : null,
      'discharge_note' => $status === 'discharged' ? $this->faker->sentence(4) : null,
      'referral_destination' => $this->faker->boolean(20) ? $this->faker->company() . ' Hospital' : null,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
    $this->summary['admissions_created']++;

    DB::table('beds')->where('id', $bedId)->update([
      'status' => $status === 'admitted' ? 'occupied' : 'available',
      'occupied_by_patient_id' => $status === 'admitted' ? $patientId : null,
      'occupied_since' => $status === 'admitted' ? $d : null,
      'last_status_changed_at' => now(),
      'updated_at' => now(),
    ]);
  }

  private function syncVisits(): void
  {
    foreach ($this->patientIds as $pid) {
      $r = $this->visits->syncPatientFacility((int)$pid, (int)$this->ctx['facility_id'], $this->ctx['start']->toDateString(), $this->ctx['end']->toDateString(), 'rich-seed-command');
      $this->summary['visits_touched'] += (int)($r['visits_touched'] ?? 0);
      $this->summary['visit_events_upserted'] += (int)($r['events_upserted'] ?? 0);
    }
  }

  private function logActivity(int $patientId, string $module, string $action, string $description, string $by, array $meta = [], ?Carbon $createdAt = null): void
  {
    DB::table('activities')->insert([
      'patient_id' => $patientId,
      'facility_id' => $this->ctx['facility_id'],
      'module' => $module,
      'action' => $action,
      'description' => $description,
      'performed_by' => $by,
      'meta' => empty($meta) ? null : json_encode($meta),
      'created_at' => $createdAt ?? now(),
      'updated_at' => now(),
    ]);
    $this->summary['activities_created']++;
  }

  private function insertUser(array $payload): ?int
  {
    $id = DB::table('users')->insertGetId([
      'first_name' => $payload['first_name'],
      'last_name' => $payload['last_name'],
      'username' => $payload['username'],
      'email' => $payload['email'],
      'phone' => $payload['phone'] ?? null,
      'password' => $payload['password'],
      'role' => $payload['role'],
      'designation' => $payload['designation'] ?? null,
      'facility_id' => $payload['facility_id'] ?? null,
      'lga_id' => $payload['lga_id'] ?? null,
      'state_id' => $payload['state_id'] ?? null,
      'is_active' => $payload['is_active'] ?? true,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    if ($id) {
      $this->summary['users_created']++;
    }

    return $id ?: null;
  }

  private function designationForRole(string $role): string
  {
    return match ($role) {
      'Facility Administrator' => 'Facility Data Administrator',
      'Data Officer' => $this->faker->randomElement(['Nurse', 'Doctor', 'Midwife', 'Lab Attendant', 'Volunteer']),
      'Verification Officer' => 'Verification Officer',
      'LGA Officer' => 'LGA Officer',
      'LGA Data Administrator' => 'LGA Data Administrator',
      'State Data Administrator' => 'State Data Administrator',
      default => $role,
    };
  }

  private function pickOfficer(): array
  {
    return $this->faker->randomElement($this->officers);
  }

  private function randDate(): Carbon
  {
    $ts = random_int(Carbon::parse($this->ctx['start'])->timestamp, Carbon::parse($this->ctx['end'])->timestamp);
    return Carbon::createFromTimestamp($ts);
  }

  private function randomDob(): string
  {
    $bucket = random_int(1, 100);
    $age = match (true) {
      $bucket <= 12 => random_int(0, 4),
      $bucket <= 30 => random_int(5, 17),
      $bucket <= 86 => random_int(18, 59),
      default => random_int(60, 86),
    };
    return now()->subYears($age)->subDays(random_int(0, 364))->toDateString();
  }

  private function ageGroup(int $age): string
  {
    return match (true) {
      $age < 1 => '<1',
      $age <= 4 => '1-4',
      $age <= 9 => '5-9',
      $age <= 14 => '10-14',
      $age <= 19 => '15-19',
      $age <= 24 => '20-24',
      $age <= 34 => '25-34',
      $age <= 44 => '35-44',
      $age <= 54 => '45-54',
      $age <= 64 => '55-64',
      default => '65+',
    };
  }

  private function nextDin(): string
  {
    do {
      $din = (string)random_int(10000000, 99999999);
    } while (in_array($din, $this->usedDins, true));

    $this->usedDins[] = $din;
    return $din;
  }

  private function uniqueUsername(string $first, string $last): string
  {
    do {
      $candidate = strtolower(preg_replace('/[^a-z0-9]/i', '', $first . $last)) . random_int(100, 99999);
      $exists = DB::table('users')->where('username', $candidate)->exists();
    } while ($exists);

    return $candidate;
  }

  private function uniqueEmail(string $first, string $last): string
  {
    do {
      $candidate = strtolower(preg_replace('/[^a-z0-9]/i', '', $first . '.' . $last)) . random_int(10, 99999) . '@mail.com';
      $exists = DB::table('users')->where('email', $candidate)->exists() || DB::table('patients')->where('email', $candidate)->exists();
    } while ($exists);

    return $candidate;
  }

  private function uniqueCode(string $table, string $column, string $pattern): string
  {
    do {
      $candidate = strtoupper($this->faker->bothify($pattern));
      $exists = DB::table($table)->where($column, $candidate)->exists();
    } while ($exists);

    return $candidate;
  }
}
