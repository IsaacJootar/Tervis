<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('wards', function (Blueprint $table) {
      $table->id();
      $table->foreignId('lga_id')->constrained('lgas')->onDelete('cascade');
      $table->string('name', 100);
      $table->timestamps();

      $table->index('lga_id');
      $table->index('name');
      $table->unique(['lga_id', 'name']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('wards');
  }
};
