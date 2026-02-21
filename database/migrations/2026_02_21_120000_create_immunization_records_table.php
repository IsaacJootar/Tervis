<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('immunization_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('linked_child_id')->constrained('linked_children')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('visit_date');
      $table->string('immunization_card_no')->nullable();
      $table->string('follow_up_address')->nullable();
      $table->string('follow_up_phone', 20)->nullable();

      $table->date('hepb0_date')->nullable();
      $table->date('opv0_date')->nullable();
      $table->date('bcg_date')->nullable();
      $table->date('opv1_date')->nullable();
      $table->date('penta1_date')->nullable();
      $table->date('pcv1_date')->nullable();
      $table->date('rota1_date')->nullable();
      $table->date('opv2_date')->nullable();
      $table->date('penta2_date')->nullable();
      $table->date('pcv2_date')->nullable();
      $table->date('rota2_date')->nullable();
      $table->date('ipv1_date')->nullable();
      $table->date('opv3_date')->nullable();
      $table->date('penta3_date')->nullable();
      $table->date('pcv3_date')->nullable();
      $table->date('mr1_date')->nullable();
      $table->date('yf_date')->nullable();
      $table->date('mr2_date')->nullable();
      $table->date('mena_date')->nullable();
      $table->date('yf2_date')->nullable();
      $table->date('slea_date')->nullable();
      $table->date('vita1_date')->nullable();
      $table->date('vita2_date')->nullable();
      $table->date('ipv2_date')->nullable();

      $table->text('comments')->nullable();
      $table->json('summary_map')->nullable();

      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->unique(['linked_child_id', 'visit_date', 'facility_id'], 'immunization_child_visit_unique');
      $table->index(['facility_id', 'month_year']);
      $table->index(['facility_id', 'visit_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('immunization_records');
  }
};
