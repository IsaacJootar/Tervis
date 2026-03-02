<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('immunization_records', function (Blueprint $table) {
      if (!Schema::hasColumn('immunization_records', 'rota3_date')) {
        $table->date('rota3_date')->nullable()->after('ipv1_date');
      }

      if (!Schema::hasColumn('immunization_records', 'hpv_date')) {
        $table->date('hpv_date')->nullable()->after('ipv2_date');
      }
    });
  }

  public function down(): void
  {
    Schema::table('immunization_records', function (Blueprint $table) {
      $columns = [];

      if (Schema::hasColumn('immunization_records', 'rota3_date')) {
        $columns[] = 'rota3_date';
      }

      if (Schema::hasColumn('immunization_records', 'hpv_date')) {
        $columns[] = 'hpv_date';
      }

      if (!empty($columns)) {
        $table->dropColumn($columns);
      }
    });
  }
};

