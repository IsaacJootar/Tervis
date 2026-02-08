<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('facilities', function (Blueprint $table) {
      $table->id();
      $table->string('name', 255);
      //$table->string('code', 50)->nullable()->unique();
      $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
      $table->foreignId('lga_id')->constrained('lgas')->onDelete('cascade');
      $table->foreignId('ward_id')->nullable()->constrained('wards')->onDelete('set null');
      $table->string('address', 500)->nullable();
      $table->string('phone', 20)->nullable();
      $table->string('email', 100)->nullable();
      $table->enum('type', ['Primary', 'Secondary', 'Tertiary'])->default('Primary');
      //$table->enum('ownership', ['Government', 'Private', 'NGO'])->default('Government');
      $table->boolean('is_active')->default(true);
      $table->timestamps();
      $table->softDeletes();

      $table->index('state_id');
      $table->index('lga_id');
      $table->index('ward_id');
      $table->index('is_active');
      $table->index('name');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('facilities');
  }
};
