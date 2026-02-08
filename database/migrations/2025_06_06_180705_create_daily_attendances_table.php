<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('daily_attendances', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->constrained()->onDelete('cascade');
      $table->foreignId('lga_id')->nullable()->constrained()->onDelete('set null');
      $table->foreignId('ward_id')->nullable()->constrained()->onDelete('set null');
      $table->date('month_year')->nullable();
      $table->date('visit_date')->nullable();
      $table->date('date_of_birth');
      $table->enum('gender', ['Male', 'Female']);
      $table->string('age_group')->nullable();
      $table->string('address')->nullable();
      $table->foreignId('state_of_origin_id')->nullable()->constrained('states')->onDelete('set null');
      $table->string('phone')->nullable();
      $table->boolean('first_contact')->nullable();
      $table->string('next_of_kin_name')->nullable();
      $table->string('next_of_kin_relation')->nullable();
      $table->string('next_of_kin_address')->nullable();
      $table->string('next_of_kin_phone')->nullable();
      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();
      $table->timestamps();
    });
  }

  public function down()
  {
    Schema::dropIfExists('daily_attendances');
  }
};
