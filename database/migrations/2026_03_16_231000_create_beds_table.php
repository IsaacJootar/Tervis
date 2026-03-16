<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('beds', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->string('bed_code', 60);
      $table->string('ward_section', 120);
      $table->string('room_label', 120)->nullable();
      $table->string('bed_type', 40)->default('general');
      $table->string('status', 20)->default('available');
      $table->boolean('is_active')->default(true);

      // Reserved for inpatient linkage in the next module.
      $table->foreignId('occupied_by_patient_id')->nullable()->constrained('patients')->nullOnDelete();
      $table->timestamp('occupied_since')->nullable();
      $table->timestamp('last_status_changed_at')->nullable();
      $table->text('notes')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->unique(['facility_id', 'bed_code']);
      $table->index(['facility_id', 'ward_section']);
      $table->index(['facility_id', 'status']);
      $table->index(['facility_id', 'is_active']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('beds');
  }
};

