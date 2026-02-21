<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('nutrition_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
      $table->foreignId('linked_child_id')->constrained('linked_children')->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('lga_id')->nullable()->constrained()->nullOnDelete();
      $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();

      $table->date('month_year')->nullable();
      $table->date('visit_date');
      $table->enum('age_group', ['0-5 months', '6-23 months', '24-59 months']);

      $table->enum('infant_feeding', ['Exclusive BF', 'BF + Water', 'BF with other foods', 'Not BF'])->nullable();
      $table->enum('complementary_feeding', ['BF + Other foods', 'Other foods only', 'Not started CF'])->nullable();
      $table->json('counselling_topics')->nullable();
      $table->boolean('support_group_referred')->default(false);

      $table->decimal('height_cm', 5, 1)->nullable();
      $table->decimal('weight_kg', 5, 2)->nullable();
      $table->enum('oedema', ['0', '+', '++', '+++'])->nullable();
      $table->unsignedSmallInteger('muac_value_mm')->nullable();
      $table->enum('muac_class', ['Red', 'Yellow', 'Green'])->nullable();
      $table->enum('growth_status', ['Growing Well', 'Not Growing Well'])->nullable();

      $table->json('supplementary_feeding_groups')->nullable();
      $table->boolean('mnp_given')->default(false);

      $table->enum('otp_provider', ['Self', 'HH', 'Not Providing OTP', 'Community Volunteer/CHIPS'])->nullable();
      $table->enum('admission_status', [
        'Admitted HP OTP',
        'Transferred in from another OTP/SC',
        'Referred to SC',
        'Does not meet OTP Admission Criteria',
      ])->nullable();
      $table->enum('outcome_status', [
        'Transferred out to another OTP/SC',
        'Recovered',
        'Defaulted',
        'Died',
        'Non-recovered',
      ])->nullable();

      $table->text('remarks')->nullable();

      // Prepared contribution keys for monthly NHMIS summary auto-aggregation.
      $table->json('summary_map')->nullable();

      $table->string('officer_name')->nullable();
      $table->string('officer_role')->nullable();
      $table->string('officer_designation')->nullable();

      $table->timestamps();
      $table->softDeletes();

      $table->unique(['linked_child_id', 'visit_date', 'facility_id'], 'nutrition_child_visit_unique');
      $table->index(['facility_id', 'month_year']);
      $table->index(['facility_id', 'visit_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('nutrition_records');
  }
};
