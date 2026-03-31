<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      if (!Schema::hasColumn('users', 'patient_id')) {
        $table->foreignId('patient_id')
          ->nullable()
          ->after('state_id')
          ->constrained('patients')
          ->nullOnDelete();
      }
    });

    $this->backfillPatientLinks();

    Schema::table('users', function (Blueprint $table) {
      $indexes = Schema::getIndexes('users');
      if (!array_key_exists('users_patient_id_unique', $indexes)) {
        $table->unique('patient_id');
      }
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $indexes = Schema::getIndexes('users');
      if (array_key_exists('users_patient_id_unique', $indexes)) {
        $table->dropUnique('users_patient_id_unique');
      }

      if (Schema::hasColumn('users', 'patient_id')) {
        $table->dropConstrainedForeignId('patient_id');
      }
    });
  }

  private function backfillPatientLinks(): void
  {
    $users = DB::table('users')
      ->where('role', 'Patient')
      ->orderBy('id')
      ->get(['id', 'username', 'first_name', 'last_name', 'facility_id']);

    $patients = DB::table('patients')
      ->orderBy('id')
      ->get(['id', 'din', 'first_name', 'last_name', 'facility_id']);

    $patientsByDin = [];
    foreach ($patients as $patient) {
      if (!empty($patient->din)) {
        $patientsByDin[(string) $patient->din] = $patient;
      }
    }

    $userGroups = [];
    foreach ($users as $user) {
      $key = $this->buildIdentityKey($user->first_name, $user->last_name, $user->facility_id);
      $userGroups[$key][] = $user;
    }

    $patientGroups = [];
    foreach ($patients as $patient) {
      $key = $this->buildIdentityKey($patient->first_name, $patient->last_name, $patient->facility_id);
      $patientGroups[$key][] = $patient;
    }

    $claimedPatientIds = [];

    foreach ($users as $user) {
      $linkedPatientId = null;

      // Preferred path: patient portal username already equals DIN.
      if (preg_match('/^\d{8}$/', (string) $user->username)) {
        $linkedPatientId = $patientsByDin[(string) $user->username]->id ?? null;
      }

      // Fallback for older seeded accounts created before DIN usernames.
      if (!$linkedPatientId) {
        $key = $this->buildIdentityKey($user->first_name, $user->last_name, $user->facility_id);
        $groupPatients = $patientGroups[$key] ?? [];
        foreach ($groupPatients as $index => $patient) {
          if (!isset($claimedPatientIds[$patient->id])) {
            $linkedPatientId = $patient->id;
            $claimedPatientIds[$patient->id] = true;
            unset($patientGroups[$key][$index]);
            break;
          }
        }
      }

      if ($linkedPatientId) {
        DB::table('users')
          ->where('id', $user->id)
          ->update([
            'patient_id' => $linkedPatientId,
            'updated_at' => now(),
          ]);
      }
    }
  }

  private function buildIdentityKey(?string $firstName, ?string $lastName, $facilityId): string
  {
    return strtolower(trim((string) $firstName) . '|' . trim((string) $lastName) . '|' . (string) $facilityId);
  }
};
