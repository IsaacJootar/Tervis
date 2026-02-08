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
    Schema::table('users', function (Blueprint $table) {
      $table->enum('account_status', ['active', 'disabled'])
        ->default('active')
        ->after('designation');
    });

    // Update existing users to have 'active' status
    DB::table('users')->update(['account_status' => 'active']);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('account_status');
    });
  }
};
