<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('facilities', function (Blueprint $table) {
      if (!Schema::hasColumn('facilities', 'state')) {
        $table->string('state', 100)->nullable()->after('ward_id');
      }
      if (!Schema::hasColumn('facilities', 'lga')) {
        $table->string('lga', 100)->nullable()->after('state');
      }
      if (!Schema::hasColumn('facilities', 'ward')) {
        $table->string('ward', 100)->nullable()->after('lga');
      }
    });
  }

  public function down(): void
  {
    Schema::table('facilities', function (Blueprint $table) {
      $drop = [];
      if (Schema::hasColumn('facilities', 'state')) {
        $drop[] = 'state';
      }
      if (Schema::hasColumn('facilities', 'lga')) {
        $drop[] = 'lga';
      }
      if (Schema::hasColumn('facilities', 'ward')) {
        $drop[] = 'ward';
      }
      if (!empty($drop)) {
        $table->dropColumn($drop);
      }
    });
  }
};
