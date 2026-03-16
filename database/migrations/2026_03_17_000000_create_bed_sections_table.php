<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('bed_sections', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->string('name', 120);
      $table->text('details')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
      $table->softDeletes();

      $table->unique(['facility_id', 'name']);
      $table->index(['facility_id', 'is_active']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('bed_sections');
  }
};

