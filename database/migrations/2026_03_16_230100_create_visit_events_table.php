<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('visit_events', function (Blueprint $table) {
      $table->id();
      $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade');
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();

      $table->dateTime('event_time');
      $table->string('module', 64)->nullable();
      $table->string('action', 32)->nullable();
      $table->string('description', 255)->nullable();
      $table->string('performed_by', 150)->nullable();
      $table->string('source_type', 40)->default('activity');
      $table->unsignedBigInteger('source_id')->nullable();
      $table->json('meta')->nullable();
      $table->timestamps();

      $table->unique('activity_id');
      $table->index(['visit_id', 'event_time']);
      $table->index(['patient_id', 'facility_id', 'event_time']);
      $table->index(['module', 'action']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('visit_events');
  }
};

