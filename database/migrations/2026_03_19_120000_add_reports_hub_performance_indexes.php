<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    $this->addIndexIfMissing('antenatal_follow_up_assessments', 'idx_afua_facility_visit_date', ['facility_id', 'visit_date']);
    $this->addIndexIfMissing('family_planning_registrations', 'idx_fp_reg_facility_registration_date', ['facility_id', 'registration_date']);
    $this->addIndexIfMissing('invoices', 'idx_invoices_facility_invoice_date', ['facility_id', 'invoice_date']);
    $this->addIndexIfMissing('tetanus_vaccinations', 'idx_tetanus_facility_dose_date', ['facility_id', 'dose_date']);
  }

  public function down(): void
  {
    $this->dropIndexIfExists('antenatal_follow_up_assessments', 'idx_afua_facility_visit_date');
    $this->dropIndexIfExists('family_planning_registrations', 'idx_fp_reg_facility_registration_date');
    $this->dropIndexIfExists('invoices', 'idx_invoices_facility_invoice_date');
    $this->dropIndexIfExists('tetanus_vaccinations', 'idx_tetanus_facility_dose_date');
  }

  private function addIndexIfMissing(string $table, string $indexName, array $columns): void
  {
    if (!Schema::hasTable($table) || count($columns) === 0) {
      return;
    }

    foreach ($columns as $column) {
      if (!Schema::hasColumn($table, $column)) {
        return;
      }
    }

    if ($this->indexExists($table, $indexName)) {
      return;
    }

    Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
      $blueprint->index($columns, $indexName);
    });
  }

  private function dropIndexIfExists(string $table, string $indexName): void
  {
    if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
      return;
    }

    Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
      $blueprint->dropIndex($indexName);
    });
  }

  private function indexExists(string $table, string $indexName): bool
  {
    $driver = DB::getDriverName();

    return match ($driver) {
      'mysql' => DB::table('information_schema.statistics')
        ->where('table_schema', DB::getDatabaseName())
        ->where('table_name', $table)
        ->where('index_name', $indexName)
        ->exists(),
      'pgsql' => DB::table('pg_indexes')
        ->where('schemaname', 'public')
        ->where('tablename', $table)
        ->where('indexname', $indexName)
        ->exists(),
      default => false,
    };
  }
};

