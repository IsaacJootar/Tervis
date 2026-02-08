<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('facility_departments', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('facility_id');
      $table->string('name', 100);
      $table->text('details')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      // Foreign key constraint
      $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');

      // Indexes
      $table->index('facility_id');
      $table->index(['facility_id', 'is_active']);

      // Unique constraint to prevent duplicate department names within the same facility
      $table->unique(['facility_id', 'name']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('facility_departments');
  }
};
