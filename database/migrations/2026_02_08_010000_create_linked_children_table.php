<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('linked_children', function (Blueprint $table) {
      $table->id();
      $table->string('linked_child_id')->unique();
      $table->foreignId('parent_patient_id')->constrained('patients')->onDelete('cascade');
      $table->string('first_name')->nullable();
      $table->string('last_name')->nullable();
      $table->string('middle_name')->nullable();
      $table->enum('gender', ['Male', 'Female', 'Unknown'])->default('Unknown');
      $table->date('date_of_birth')->nullable();
      $table->string('relationship')->nullable();
      $table->decimal('birth_weight', 5, 2)->nullable();
      $table->decimal('birth_length', 5, 2)->nullable();
      $table->unsignedTinyInteger('birth_order')->nullable();
      $table->boolean('is_active')->default(true);
      $table->date('deceased_date')->nullable();
      $table->foreignId('graduated_patient_id')->nullable()->constrained('patients')->nullOnDelete();
      $table->dateTime('graduated_at')->nullable();
      $table->foreignId('facility_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
      $table->text('notes')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('linked_children');
  }
};
