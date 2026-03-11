<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('drug_catalog_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();

      $table->string('drug_name', 150);
      $table->string('formulation', 120)->nullable();
      $table->string('strength', 120)->nullable();
      $table->string('route', 80)->nullable();
      $table->text('notes')->nullable();
      $table->boolean('is_active')->default(true);

      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'is_active']);
      $table->index(['facility_id', 'drug_name']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('drug_catalog_items');
  }
};

