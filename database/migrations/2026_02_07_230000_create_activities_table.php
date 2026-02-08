<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('activities', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients');
      $table->foreignId('facility_id')->constrained('facilities');
      $table->string('module', 64);
      $table->string('action', 32);
      $table->string('description', 255)->nullable();
      $table->string('performed_by', 150)->nullable();
      $table->json('meta')->nullable();
      $table->timestamps();

      $table->index(['patient_id', 'facility_id']);
      $table->index(['module', 'action']);
      $table->index('created_at');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('activities');
  }
};
