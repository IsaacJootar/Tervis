<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('child_health_activity_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('linked_child_id')->constrained('linked_children')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('visit_date');

      $table->json('vaccination_dates')->nullable();
      $table->json('vaccination_notes')->nullable();
      $table->json('weight_entries')->nullable();
      $table->json('breastfeeding_entries')->nullable();

      $table->text('comments')->nullable();
      $table->json('summary_map')->nullable();

      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->unique(['linked_child_id', 'visit_date', 'facility_id'], 'child_health_activity_child_visit_unique');
      $table->index(['facility_id', 'month_year']);
      $table->index(['facility_id', 'visit_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('child_health_activity_records');
  }
};
