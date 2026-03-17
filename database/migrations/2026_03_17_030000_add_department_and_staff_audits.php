<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      if (!Schema::hasColumn('users', 'department_id')) {
        $table->unsignedBigInteger('department_id')->nullable()->after('facility_id');
        $table->index(['facility_id', 'department_id'], 'users_facility_department_index');
        $table->foreign('department_id')->references('id')->on('facility_departments')->nullOnDelete();
      }
    });

    Schema::create('staff_management_audits', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('facility_id');
      $table->unsignedBigInteger('target_user_id')->nullable();
      $table->string('action', 80);
      $table->json('old_values')->nullable();
      $table->json('new_values')->nullable();
      $table->unsignedBigInteger('changed_by_user_id')->nullable();
      $table->string('changed_by_name', 160)->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();

      $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
      $table->foreign('target_user_id')->references('id')->on('users')->nullOnDelete();
      $table->foreign('changed_by_user_id')->references('id')->on('users')->nullOnDelete();

      $table->index(['facility_id', 'created_at'], 'staff_audits_facility_date_index');
      $table->index(['facility_id', 'action'], 'staff_audits_facility_action_index');
      $table->index(['target_user_id', 'created_at'], 'staff_audits_target_date_index');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('staff_management_audits');

    Schema::table('users', function (Blueprint $table) {
      if (Schema::hasColumn('users', 'department_id')) {
        $table->dropForeign(['department_id']);
        $table->dropIndex('users_facility_department_index');
        $table->dropColumn('department_id');
      }
    });
  }
};

