<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('immunization_records', function (Blueprint $table) {
      if (!Schema::hasColumn('immunization_records', 'aefi_period')) {
        $table->string('aefi_period', 50)->nullable()->after('follow_up_phone');
      }

      if (!Schema::hasColumn('immunization_records', 'aefi_type')) {
        $table->string('aefi_type', 50)->nullable()->after('aefi_period');
      }

      if (!Schema::hasColumn('immunization_records', 'aefi_sia_campaign')) {
        $table->string('aefi_sia_campaign')->nullable()->after('aefi_type');
      }

      if (!Schema::hasColumn('immunization_records', 'aefi_cases')) {
        $table->json('aefi_cases')->nullable()->after('hpv_date');
      }
    });
  }

  public function down(): void
  {
    Schema::table('immunization_records', function (Blueprint $table) {
      $columns = [];

      foreach (['aefi_period', 'aefi_type', 'aefi_sia_campaign', 'aefi_cases'] as $column) {
        if (Schema::hasColumn('immunization_records', $column)) {
          $columns[] = $column;
        }
      }

      if (!empty($columns)) {
        $table->dropColumn($columns);
      }
    });
  }
};
