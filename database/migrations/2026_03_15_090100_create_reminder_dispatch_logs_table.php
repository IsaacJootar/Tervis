<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('reminder_dispatch_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('reminder_id')->constrained('reminders')->onDelete('cascade');
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');

      $table->enum('channel', ['sms', 'email']);
      $table->enum('status', ['queued', 'sent', 'failed', 'skipped'])->default('queued');
      $table->string('provider', 80)->default('placeholder');
      $table->string('recipient', 150)->nullable();
      $table->string('subject', 180)->nullable();
      $table->text('message')->nullable();
      $table->text('provider_message')->nullable();
      $table->json('provider_payload')->nullable();

      $table->timestamp('sent_at')->nullable();
      $table->timestamp('failed_at')->nullable();

      $table->timestamps();

      $table->index(['patient_id', 'facility_id']);
      $table->index(['facility_id', 'status']);
      $table->index(['channel', 'status']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('reminder_dispatch_logs');
  }
};

