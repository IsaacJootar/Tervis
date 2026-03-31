<?php

namespace Tests\Feature;

use App\Livewire\Patient\Portal;
use App\Models\Activity;
use App\Livewire\Registers\AntenatalRegister;
use App\Livewire\Registers\FamilyPlanningRegister;
use App\Livewire\Registers\GeneralPatientsRegister;
use App\Models\Delivery;
use App\Models\DoctorAssessment;
use App\Models\FamilyPlanningFollowUp;
use App\Models\Invoice;
use App\Models\LabTestOrder;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\Registrations\AntenatalRegistration;
use App\Models\Registrations\FamilyPlanningRegistration;
use App\Models\Registrations\GeneralPatientsRegistration;
use App\Models\Reminder;
use App\Models\TetanusVaccination;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PatientRegistrationCreatesPortalAccountTest extends TestCase
{
  use DatabaseTransactions;

  private array $geo;
  private User $dataOfficer;

  protected function setUp(): void
  {
    parent::setUp();
    Livewire::withoutLazyLoading();

    $requiredTables = [
      'users',
      'patients',
      'states',
      'lgas',
      'wards',
      'facilities',
      'general_patients_registrations',
      'antenatal_registrations',
      'family_planning_registrations',
      'deliveries',
      'postnatal_records',
      'tetanus_vaccinations',
    ];

    foreach ($requiredTables as $table) {
      if (!Schema::hasTable($table)) {
        $this->markTestSkipped("Required table '{$table}' is not available.");
      }
    }

    $this->geo = $this->createGeoContext();
    $this->dataOfficer = $this->createDataOfficer();
  }

  public function test_general_registration_creates_linked_patient_portal_account(): void
  {
    $this->actingAs($this->dataOfficer);

    Livewire::test(GeneralPatientsRegister::class)
      ->set('first_name', 'General')
      ->set('last_name', 'Patient')
      ->set('gender', 'Female')
      ->set('date_of_birth', now()->subYears(26)->toDateString())
      ->set('phone', '08030001111')
      ->set('email', 'general.patient@example.com')
      ->set('state_id', $this->geo['state_id'])
      ->set('lga_id', $this->geo['lga_id'])
      ->set('ward_id', $this->geo['ward_id'])
      ->call('store');

    $patient = Patient::query()->where('first_name', 'General')->where('last_name', 'Patient')->firstOrFail();
    $user = User::query()->where('patient_id', $patient->id)->firstOrFail();

    $this->assertSame($patient->din, $user->username);
    $this->assertTrue(Hash::check($patient->din, $user->password));
    $this->assertSame('Patient', $user->role);
    $this->assertDatabaseHas('general_patients_registrations', ['patient_id' => $patient->id]);
  }

  public function test_antenatal_registration_creates_linked_patient_portal_account(): void
  {
    $this->actingAs($this->dataOfficer);

    Livewire::test(AntenatalRegister::class)
      ->set('first_name', 'Antenatal')
      ->set('last_name', 'Patient')
      ->set('patient_gender', 'Female')
      ->set('patient_dob', now()->subYears(29)->toDateString())
      ->set('patient_phone', '08030002222')
      ->set('patient_email', 'antenatal.patient@example.com')
      ->set('state_id', $this->geo['state_id'])
      ->set('lga_id', $this->geo['lga_id'])
      ->set('ward_id', $this->geo['ward_id'])
      ->set('address', '15 Antenatal Street')
      ->set('lmp', now()->subMonths(3)->toDateString())
      ->set('date_of_booking', now()->toDateString())
      ->set('gravida', 1)
      ->set('parity', 0)
      ->call('store');

    $patient = Patient::query()->where('first_name', 'Antenatal')->where('last_name', 'Patient')->firstOrFail();
    $user = User::query()->where('patient_id', $patient->id)->firstOrFail();

    $this->assertSame($patient->din, $user->username);
    $this->assertTrue(Hash::check($patient->din, $user->password));
    $this->assertDatabaseHas('antenatal_registrations', ['patient_id' => $patient->id]);
  }

  public function test_family_planning_registration_creates_linked_patient_portal_account(): void
  {
    $this->actingAs($this->dataOfficer);

    Livewire::test(FamilyPlanningRegister::class)
      ->set('first_name', 'Family')
      ->set('last_name', 'Planning')
      ->set('patient_gender', 'Female')
      ->set('patient_dob', now()->subYears(31)->toDateString())
      ->set('patient_phone', '08030003333')
      ->set('patient_email', 'family.planning@example.com')
      ->set('state_id', $this->geo['state_id'])
      ->set('lga_id', $this->geo['lga_id'])
      ->set('ward_id', $this->geo['ward_id'])
      ->set('address', '30 Family Planning Road')
      ->set('registration_date', now()->toDateString())
      ->set('last_menstrual_period', now()->subMonth()->toDateString())
      ->set('contraceptive_selected', 'Injectable')
      ->set('weight', 64)
      ->set('blood_pressure', '120/80')
      ->call('store');

    $patient = Patient::query()->where('first_name', 'Family')->where('last_name', 'Planning')->firstOrFail();
    $user = User::query()->where('patient_id', $patient->id)->firstOrFail();

    $this->assertSame($patient->din, $user->username);
    $this->assertTrue(Hash::check($patient->din, $user->password));
    $this->assertDatabaseHas('family_planning_registrations', ['patient_id' => $patient->id]);
  }

  public function test_patient_portal_routes_resolve_linked_patient_records(): void
  {
    $patient = Patient::query()->create([
      'din' => '24681357',
      'first_name' => 'Portal',
      'last_name' => 'Records',
      'gender' => 'Female',
      'date_of_birth' => now()->subYears(28)->toDateString(),
      'phone' => '08039998888',
      'email' => 'portal.records@example.com',
      'state_id' => $this->geo['state_id'],
      'lga_id' => $this->geo['lga_id'],
      'ward_id' => $this->geo['ward_id'],
      'facility_id' => $this->geo['facility_id'],
      'registration_date' => now()->toDateString(),
      'is_active' => true,
    ]);

    $user = User::query()->create([
      'first_name' => 'Portal',
      'last_name' => 'Records',
      'username' => '24681357',
      'email' => 'portal.user.records@example.com',
      'phone' => '08037776666',
      'password' => Hash::make('24681357'),
      'role' => 'Patient',
      'designation' => 'Patient',
      'facility_id' => $this->geo['facility_id'],
      'lga_id' => $this->geo['lga_id'],
      'state_id' => $this->geo['state_id'],
      'patient_id' => $patient->id,
      'is_active' => true,
      'account_status' => 'active',
    ]);

    $antenatalRegistration = AntenatalRegistration::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'pregnancy_number' => 1,
      'is_active' => true,
      'registration_date' => now()->subDays(10)->toDateString(),
      'date_of_booking' => now()->subDays(10)->toDateString(),
      'lmp' => now()->subMonths(2)->toDateString(),
      'edd' => now()->addMonths(7)->toDateString(),
      'gravida' => 1,
      'parity' => 0,
      'unit_no' => 'ANC-100',
      'address' => '15 Portal Lane',
      'officer_name' => 'Portal Officer',
    ]);

    Delivery::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'dodel' => now()->subDays(5)->toDateString(),
      'mod' => 'SVD',
      'baby_sex' => 'Female',
      'officer_name' => 'Portal Officer',
      'officer_role' => 'Data Officer',
      'officer_designation' => 'Nurse',
    ]);

    PostnatalRecord::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'month_year' => now()->startOfMonth()->toDateString(),
      'visit_date' => now()->subDays(2)->toDateString(),
      'delivery_date' => now()->subDays(5)->toDateString(),
      'attendance' => '1st Visit',
      'visit_outcome' => 'Stable',
      'officer_name' => 'Portal Officer',
      'officer_role' => 'Data Officer',
      'officer_designation' => 'Nurse',
    ]);

    TetanusVaccination::query()->create([
      'patient_id' => $patient->id,
      'antenatal_registration_id' => $antenatalRegistration->id,
      'facility_id' => $this->geo['facility_id'],
      'visit_date' => now()->subDays(1)->toDateString(),
      'current_tt_dose' => 'TT1',
      'dose_date' => now()->subDays(1)->toDateString(),
      'dose_number' => 1,
      'protection_status' => 'Partially Protected',
      'vaccination_site' => 'Left Upper Arm',
      'patient_din' => $patient->din,
      'patient_first_name' => $patient->first_name,
      'patient_last_name' => $patient->last_name,
      'patient_phone' => $patient->phone,
      'patient_age' => $patient->date_of_birth->age,
      'patient_gender' => $patient->gender,
      'officer_name' => 'Portal Officer',
      'officer_role' => 'Data Officer',
      'officer_designation' => 'Nurse',
    ]);

    DoctorAssessment::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'month_year' => now()->startOfMonth()->toDateString(),
      'visit_date' => now()->subDays(3)->toDateString(),
      'next_appointment_date' => now()->addDays(14)->toDateString(),
      'final_diagnosis' => 'Routine Review',
      'requires_lab_tests' => true,
      'requires_drugs' => true,
      'officer_name' => 'Portal Officer',
      'officer_role' => 'Doctor',
      'officer_designation' => 'Medical Officer',
    ]);

    FamilyPlanningRegistration::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'registration_date' => now()->subDays(20)->toDateString(),
      'contraceptive_selected' => 'Injectable',
      'next_appointment' => now()->addDays(21)->toDateString(),
      'officer_name' => 'Portal Officer',
      'officer_role' => 'Data Officer',
      'officer_designation' => 'Nurse',
    ]);

    FamilyPlanningFollowUp::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'month_year' => now()->startOfMonth()->toDateString(),
      'visit_date' => now()->subDays(4)->toDateString(),
      'next_appointment_date' => now()->addDays(30)->toDateString(),
      'method_supplied' => 'Injectable',
      'officer_name' => 'Portal Officer',
      'officer_role' => 'Data Officer',
      'officer_designation' => 'Nurse',
    ]);

    Activity::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'module' => 'health_insurance',
      'action' => 'update',
      'description' => 'Updated NHIS coverage details.',
      'performed_by' => 'Portal Officer',
    ]);

    Reminder::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'title' => 'Review Reminder',
      'message' => 'Return for review',
      'reminder_date' => now()->addDays(7)->toDateString(),
      'status' => 'pending',
      'channels' => ['sms'],
      'created_by' => 'Portal Officer',
      'created_by_role' => 'Data Officer',
    ]);

    LabTestOrder::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'month_year' => now()->startOfMonth()->toDateString(),
      'visit_date' => now()->subDays(3)->toDateString(),
      'test_name' => 'Full Blood Count',
      'priority' => 'routine',
      'status' => 'pending',
      'requested_by' => 'Portal Officer',
    ]);

    Prescription::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'month_year' => now()->startOfMonth()->toDateString(),
      'prescribed_date' => now()->subDays(3)->toDateString(),
      'drug_name' => 'Paracetamol',
      'dosage' => '500mg',
      'frequency' => 'BID',
      'duration' => '5 days',
      'status' => 'pending',
      'prescribed_by' => 'Portal Officer',
    ]);

    Invoice::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'month_year' => now()->startOfMonth()->toDateString(),
      'invoice_code' => 'INV-001',
      'invoice_date' => now()->subDays(2)->toDateString(),
      'total_amount' => 5000,
      'amount_paid' => 1500,
      'outstanding_amount' => 3500,
      'status' => 'partial',
      'created_by' => 'Portal Officer',
    ]);

    Referral::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'month_year' => now()->startOfMonth()->toDateString(),
      'referral_date' => now()->subDays(1)->toDateString(),
      'referred_from' => 'ANC',
      'referred_to' => 'Laboratory',
      'follow_up_needed' => true,
      'officer_name' => 'Portal Officer',
      'officer_role' => 'Data Officer',
      'officer_designation' => 'Nurse',
    ]);

    Visit::query()->create([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'visit_date' => now()->subDay()->toDateString(),
      'check_in_time' => '08:00:00',
      'status' => 'open',
      'total_events' => 4,
      'recorded_by' => 'Portal Officer',
    ]);

    DB::table('din_activations')->insert([
      'patient_id' => $patient->id,
      'facility_id' => $this->geo['facility_id'],
      'visit_date' => now()->toDateString(),
      'check_in_time' => '08:00:00',
      'patient_din' => $patient->din,
      'patient_first_name' => $patient->first_name,
      'patient_last_name' => $patient->last_name,
      'patient_phone' => $patient->phone,
      'patient_age' => $patient->date_of_birth->age,
      'patient_gender' => $patient->gender,
      'officer_name' => 'Portal Officer',
      'officer_role' => 'Verification Officer',
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    $this->actingAs($user);

    $this->get(route('patient-dashboard'))
      ->assertOk()
      ->assertSee('24681357')
      ->assertSee($this->geo['facility_name']);

    $this->get(route('patient-antenatal'))
      ->assertOk()
      ->assertSee('ANC-100');

    Livewire::test(Portal::class, ['section' => 'tetanus'])
      ->assertSet('section', 'tetanus')
      ->call('viewRecord', TetanusVaccination::query()->where('patient_id', $patient->id)->value('id'))
      ->assertSet('selected_record.id', TetanusVaccination::query()->where('patient_id', $patient->id)->value('id'));

    $routeExpectations = [
      'patient-attendance' => 'Attendance Timeline',
      'patient-activities' => 'Activity Log',
      'patient-appointments' => 'Appointment Planner',
      'patient-visits' => 'Visit History',
      'patient-assessments' => 'Clinical Assessments',
      'patient-reminders' => 'Reminder Schedule',
      'patient-laboratory' => 'Laboratory Requests',
      'patient-prescriptions' => 'Prescription History',
      'patient-invoices' => 'Invoice Summary',
      'patient-referrals' => 'Referral Trail',
      'patient-family-planning' => 'Family Planning Records',
      'patient-health-insurance' => 'Coverage Summary',
    ];

    foreach ($routeExpectations as $routeName => $expectedText) {
      $this->get(route($routeName))
        ->assertOk()
        ->assertSee($expectedText);
    }
  }

  private function createGeoContext(): array
  {
    $token = Str::upper(Str::random(5));
    $now = now();

    $stateId = DB::table('states')->insertGetId([
      'name' => 'Portal State ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $lgaId = DB::table('lgas')->insertGetId([
      'state_id' => $stateId,
      'name' => 'Portal LGA ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $wardId = DB::table('wards')->insertGetId([
      'lga_id' => $lgaId,
      'name' => 'Portal Ward ' . $token,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    $facilityName = 'Portal Facility ' . $token;
    $facilityId = DB::table('facilities')->insertGetId([
      'name' => $facilityName,
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'state' => 'Portal State ' . $token,
      'lga' => 'Portal LGA ' . $token,
      'ward' => 'Portal Ward ' . $token,
      'address' => 'No. 12 Portal Street',
      'phone' => '08000000000',
      'email' => 'portal_' . strtolower($token) . '@example.com',
      'type' => 'Primary',
      'is_active' => 1,
      'created_at' => $now,
      'updated_at' => $now,
    ]);

    return [
      'state_id' => $stateId,
      'lga_id' => $lgaId,
      'ward_id' => $wardId,
      'facility_id' => $facilityId,
      'facility_name' => $facilityName,
    ];
  }

  private function createDataOfficer(): User
  {
    $token = strtolower(Str::random(8));

    return User::query()->create([
      'first_name' => 'Portal',
      'last_name' => 'Officer',
      'username' => 'portal_officer_' . $token,
      'email' => 'portal_officer_' . $token . '@example.com',
      'phone' => '08031112222',
      'password' => bcrypt('password'),
      'role' => 'Data Officer',
      'designation' => 'Nurse',
      'facility_id' => $this->geo['facility_id'],
      'state_id' => $this->geo['state_id'],
      'lga_id' => $this->geo['lga_id'],
      'is_active' => true,
      'account_status' => 'active',
    ]);
  }
}
