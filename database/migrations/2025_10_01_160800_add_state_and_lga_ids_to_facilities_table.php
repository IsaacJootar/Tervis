<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStateAndLgaIdsToFacilitiesTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::table('facilities', function (Blueprint $table) {
      $table->unsignedBigInteger('state_id')->nullable()->after('name');
      $table->unsignedBigInteger('lga_id')->nullable()->after('state_id');
      $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
      $table->foreign('lga_id')->references('id')->on('lgas')->onDelete('set null');
      $table->index(['state_id', 'lga_id'], 'idx_facility_location');
    });
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
