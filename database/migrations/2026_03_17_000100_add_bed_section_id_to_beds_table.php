<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('beds', function (Blueprint $table) {
      if (!Schema::hasColumn('beds', 'bed_section_id')) {
        $table->foreignId('bed_section_id')->nullable()->after('facility_id')
          ->constrained('bed_sections')
          ->nullOnDelete();
      }
    });
  }

  public function down(): void
  {
    Schema::table('beds', function (Blueprint $table) {
      if (Schema::hasColumn('beds', 'bed_section_id')) {
        $table->dropConstrainedForeignId('bed_section_id');
      }
    });
  }
};

