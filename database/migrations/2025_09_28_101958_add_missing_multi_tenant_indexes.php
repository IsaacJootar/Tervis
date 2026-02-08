<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // Helper function to check if index exists
    $indexExists = function ($table, $indexName) {
      $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");
      return count($indexes) > 0;
    };

    // USERS table - add missing indexes
    Schema::table('users', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('users', 'users_role_index')) {
        $table->index('role', 'users_role_index');
      }
      if (!$indexExists('users', 'users_facility_role_index')) {
        $table->index(['facility_id', 'role'], 'users_facility_role_index');
      }
      if (!$indexExists('users', 'users_facility_status_index')) {
        $table->index(['facility_id', 'account_status'], 'users_facility_status_index');
      }
    });

    // FACILITIES table - add location indexes
    Schema::table('facilities', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('facilities', 'facilities_state_index')) {
        $table->index('state', 'facilities_state_index');
      }
      if (!$indexExists('facilities', 'facilities_lga_index')) {
        $table->index('lga', 'facilities_lga_index');
      }
      if (!$indexExists('facilities', 'facilities_state_lga_index')) {
        $table->index(['state', 'lga'], 'facilities_state_lga_index');
      }
    });

    // ANTENATALS table - add facility/date indexes
    Schema::table('antenatals', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('antenatals', 'antenatals_facility_booking_date_index')) {
        $table->index(['registration_facility_id', 'date_of_booking'], 'antenatals_facility_booking_date_index');
      }
      if (!$indexExists('antenatals', 'antenatals_facility_created_index')) {
        $table->index(['registration_facility_id', 'created_at'], 'antenatals_facility_created_index');
      }
    });

    // DELIVERIES table - add facility/date indexes
    Schema::table('deliveries', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('deliveries', 'deliveries_facility_delivery_date_index')) {
        $table->index(['facility_id', 'dodel'], 'deliveries_facility_delivery_date_index');
      }
      if (!$indexExists('deliveries', 'deliveries_facility_month_index')) {
        $table->index(['facility_id', 'month_year'], 'deliveries_facility_month_index');
      }
      if (!$indexExists('deliveries', 'deliveries_facility_created_index')) {
        $table->index(['facility_id', 'created_at'], 'deliveries_facility_created_index');
      }
    });

    // DAILY_ATTENDANCES table - add facility/date indexes
    Schema::table('daily_attendances', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('daily_attendances', 'daily_attendances_facility_visit_index')) {
        $table->index(['facility_id', 'visit_date'], 'daily_attendances_facility_visit_index');
      }
      if (!$indexExists('daily_attendances', 'daily_attendances_facility_month_index')) {
        $table->index(['facility_id', 'month_year'], 'daily_attendances_facility_month_index');
      }
      if (!$indexExists('daily_attendances', 'daily_attendances_facility_gender_index')) {
        $table->index(['facility_id', 'gender'], 'daily_attendances_facility_gender_index');
      }
    });

    // POSTNATAL_RECORDS table - add facility/date indexes
    Schema::table('postnatal_records', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('postnatal_records', 'postnatal_facility_visit_index')) {
        $table->index(['facility_id', 'visit_date'], 'postnatal_facility_visit_index');
      }
      if (!$indexExists('postnatal_records', 'postnatal_facility_month_index')) {
        $table->index(['facility_id', 'month_year'], 'postnatal_facility_month_index');
      }
      if (!$indexExists('postnatal_records', 'postnatal_facility_delivery_index')) {
        $table->index(['facility_id', 'delivery_date'], 'postnatal_facility_delivery_index');
      }
    });

    // ADMINISTRATORS table - add role indexes
    Schema::table('administrators', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('administrators', 'administrators_role_index')) {
        $table->index('role', 'administrators_role_index');
      }
      if (!$indexExists('administrators', 'administrators_facility_role_index')) {
        $table->index(['facility_id', 'role'], 'administrators_facility_role_index');
      }
    });

    // DATA_OFFICERS table - add facility/role index
    Schema::table('data_officers', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('data_officers', 'data_officers_facility_role_index')) {
        $table->index(['facility_id', 'role'], 'data_officers_facility_role_index');
      }
    });

    // LGAs table - add name indexes
    Schema::table('lgas', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('lgas', 'lgas_name_index')) {
        $table->index('name', 'lgas_name_index');
      }
      if (!$indexExists('lgas', 'lgas_state_name_index')) {
        $table->index(['state_id', 'name'], 'lgas_state_name_index');
      }
    });

    // WARDS table - add name indexes
    Schema::table('wards', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('wards', 'wards_name_index')) {
        $table->index('name', 'wards_name_index');
      }
      if (!$indexExists('wards', 'wards_lga_name_index')) {
        $table->index(['lga_id', 'name'], 'wards_lga_name_index');
      }
    });

    // STATES table - add name index
    Schema::table('states', function (Blueprint $table) use ($indexExists) {
      if (!$indexExists('states', 'states_name_index')) {
        $table->index('name', 'states_name_index');
      }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // Drop indexes (only if they exist)
    $dropIndexIfExists = function ($table, $indexName) {
      try {
        DB::statement("DROP INDEX {$indexName} ON {$table}");
      } catch (Exception $e) {
        // Index doesn't exist, ignore
      }
    };

    $dropIndexIfExists('users', 'users_role_index');
    $dropIndexIfExists('users', 'users_facility_role_index');
    $dropIndexIfExists('users', 'users_facility_status_index');
    $dropIndexIfExists('facilities', 'facilities_state_index');
    $dropIndexIfExists('facilities', 'facilities_lga_index');
    $dropIndexIfExists('facilities', 'facilities_state_lga_index');
  }
};
