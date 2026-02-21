<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddStateAndLgaIdsToFacilitiesTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    if (Schema::hasColumn('facilities', 'state_id') && Schema::hasColumn('facilities', 'lga_id')) {
      return;
    }

    Schema::table('facilities', function (Blueprint $table) {
      if (!Schema::hasColumn('facilities', 'state_id')) {
        $table->unsignedBigInteger('state_id')->nullable()->after('name');
      }
      if (!Schema::hasColumn('facilities', 'lga_id')) {
        $table->unsignedBigInteger('lga_id')->nullable()->after('state_id');
      }
    });

    // Add constraints/index only if they don't already exist.
    $constraints = collect(DB::select("
      SELECT CONSTRAINT_NAME
      FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'facilities'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    "))->pluck('CONSTRAINT_NAME')->all();

    Schema::table('facilities', function (Blueprint $table) use ($constraints) {
      if (!in_array('facilities_state_id_foreign', $constraints, true)) {
        $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
      }
      if (!in_array('facilities_lga_id_foreign', $constraints, true)) {
        $table->foreign('lga_id')->references('id')->on('lgas')->onDelete('set null');
      }
    });

    $indexes = collect(DB::select("
      SELECT INDEX_NAME
      FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'facilities'
    "))->pluck('INDEX_NAME')->all();

    if (!in_array('idx_facility_location', $indexes, true)) {
      Schema::table('facilities', function (Blueprint $table) {
        $table->index(['state_id', 'lga_id'], 'idx_facility_location');
      });
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::table('facilities', function (Blueprint $table) {
      $table->dropForeign(['state_id']);
      $table->dropForeign(['lga_id']);
      $table->dropIndex('idx_facility_location');
      $table->dropColumn(['state_id', 'lga_id']);
    });
  }
}
