<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('reminders', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      $table->string('source_module', 80)->nullable();
      $table->unsignedBigInteger('source_record_id')->nullable();

      $table->string('title', 180);
      $table->text('message');
      $table->date('reminder_date');
      $table->time('reminder_time')->nullable();
      $table->enum('status', ['pending', 'queued', 'sent', 'failed', 'canceled'])->default('pending');

      $table->json('channels')->nullable();
      $table->string('recipient_phone', 40)->nullable();
      $table->string('recipient_email', 150)->nullable();

      $table->string('created_by', 120)->nullable();
      $table->string('created_by_role', 60)->nullable();
      $table->json('meta')->nullable();

      $table->timestamp('queued_at')->nullable();
      $table->timestamp('sent_at')->nullable();
      $table->timestamp('failed_at')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['patient_id', 'facility_id']);
      $table->index(['facility_id', 'reminder_date']);
      $table->index(['facility_id', 'status']);
      $table->index(['source_module', 'source_record_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('reminders');
  }
};

