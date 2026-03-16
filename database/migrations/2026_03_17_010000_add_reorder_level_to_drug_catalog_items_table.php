<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('drug_catalog_items', function (Blueprint $table) {
      if (!Schema::hasColumn('drug_catalog_items', 'reorder_level')) {
        $table->unsignedInteger('reorder_level')->default(10)->after('notes');
      }
    });
  }

  public function down(): void
  {
    Schema::table('drug_catalog_items', function (Blueprint $table) {
      if (Schema::hasColumn('drug_catalog_items', 'reorder_level')) {
        $table->dropColumn('reorder_level');
      }
    });
  }
};

