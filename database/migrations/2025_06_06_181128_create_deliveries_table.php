<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('deliveries', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->onDelete('set null');
      $table->foreignId('lga_id')->nullable()->constrained()->onDelete('set null');
      $table->foreignId('ward_id')->nullable()->constrained()->onDelete('set null');
      $table->date('month_year')->nullable();
      $table->date('cl_date')->nullable();
      $table->string('cl_sex')->nullable();
      $table->string('toc')->nullable();
      $table->string('seeking_care')->nullable();
      $table->string('transportation')->nullable();
      $table->string('parity')->nullable();
      $table->date('dodel')->nullable();
      $table->string('cl_phone')->nullable();
      $table->string('mod')->nullable();
      $table->string('partograph')->nullable();
      $table->string('oxytocin')->nullable();
      $table->string('misoprostol')->nullable();
      $table->string('alive')->nullable();
      $table->string('admitted')->nullable();
      $table->string('discharged')->nullable();
      $table->string('referred_out')->nullable();
      $table->string('pac')->nullable();
      $table->string('mother_transportation')->nullable();
      $table->string('dead')->nullable();
      $table->string('MDA_conducted')->nullable();
      $table->string('MDA_not_conducted')->nullable();
      $table->string('abortion')->nullable();
      $table->time('time_of_delivery')->nullable();
      $table->string('pre_term')->nullable();
      $table->string('breathing')->nullable();
      $table->decimal('weight', 5, 1)->nullable();
      $table->string('still_birth')->nullable();
      $table->string('baby_dead')->nullable();
      $table->string('live_births')->nullable();
      $table->string('baby_sex')->nullable();
      $table->string('took_delivery')->nullable();
      $table->string('doctor')->nullable();
      $table->string('newborn_care')->nullable();
      $table->string('clamped')->nullable();
      $table->string('CKX_gel')->nullable();
      $table->string('breast')->nullable();
      $table->decimal('temperature', 4, 1)->nullable();
      $table->string('breastfeeding')->nullable();
      $table->string('postpartum')->nullable();
      $table->string('took_del')->nullable();
      $table->string('officer_name');
      $table->string('officer_role');
      $table->string('officer_designation');
      $table->decimal('blood_loss', 6, 1)->nullable()->comment('mL');
      $table->unsignedTinyInteger('gestational_age')->nullable()->comment('Weeks');
      $table->text('complications')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('deliveries');
  }
};
