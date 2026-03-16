<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('visits', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->foreignId('activation_id')->nullable()->constrained('din_activations')->nullOnDelete();

      $table->date('visit_date');
      $table->time('check_in_time')->nullable();
      $table->dateTime('check_out_time')->nullable();
      $table->string('status', 20)->default('open');

      $table->unsignedInteger('total_events')->default(0);
      $table->json('modules_summary')->nullable();
      $table->text('notes')->nullable();
      $table->string('recorded_by', 150)->nullable();
      $table->timestamps();

      $table->unique(['patient_id', 'facility_id', 'visit_date']);
      $table->index(['facility_id', 'visit_date']);
      $table->index(['patient_id', 'visit_date']);
      $table->index('status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('visits');
  }
};

