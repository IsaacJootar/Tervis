<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('lgas', function (Blueprint $table) {
      $table->id();
      $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
      $table->string('name', 100);
      $table->timestamps();

      $table->index('state_id');
      $table->index('name');
      $table->unique(['state_id', 'name']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('lgas');
  }
};
