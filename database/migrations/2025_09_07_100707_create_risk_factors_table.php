<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('risk_factors', function (Blueprint $table) {
      $table->id();

      // Risk Factor Definition
      $table->string('factor_code', 50)->unique(); // e.g., 'teen_pregnancy', 'hypertension'
      $table->string('factor_name', 200); // Human readable name
      $table->text('description');
      $table->enum('category', ['demographic', 'medical_history', 'current_pregnancy', 'clinical_measurements', 'obstetric_history', 'family_history']);

      // Scoring Configuration
      $table->integer('base_weight')->default(0); // Base risk score
      $table->json('weight_modifiers')->nullable(); // Conditional weights based on other factors
      $table->enum('severity_impact', ['minimal', 'low', 'moderate', 'high', 'critical'])->default('moderate');

      // Clinical Context
      $table->enum('gestational_relevance', ['any', 'first_trimester', 'second_trimester', 'third_trimester', 'delivery', 'postpartum']);
      $table->json('trigger_conditions')->nullable(); // Conditions that activate this risk factor
      $table->json('exclusion_conditions')->nullable(); // Conditions that negate this factor

      // Evidence Base
      $table->text('clinical_evidence')->nullable(); // Research backing this factor
      $table->decimal('evidence_strength', 3, 2)->default(1.00); // Confidence in this factor (0-1)
      $table->json('reference_studies')->nullable(); // Links to supporting research

      // AI Configuration
      $table->boolean('ai_detectable')->default(true); // Can AI identify this factor?
      $table->json('detection_rules')->nullable(); // How AI should identify this factor
      $table->json('related_factors')->nullable(); // Other factors this interacts with
      $table->decimal('ai_confidence_threshold', 3, 2)->default(0.80); // Minimum confidence to flag

      // Intervention Guidance
      $table->json('recommended_actions')->nullable(); // What to do if this factor is present
      $table->json('monitoring_requirements')->nullable(); // How often to reassess
      $table->text('patient_education')->nullable(); // Information to share with patient

      // System Configuration
      $table->boolean('is_active')->default(true);
      $table->integer('display_order')->default(0);
      $table->enum('auto_detect', ['yes', 'no', 'suggest'])->default('suggest'); // Should system auto-apply this factor?
      $table->date('effective_from')->nullable();
      $table->date('effective_until')->nullable();

      // Tracking & Analytics
      $table->integer('times_detected')->default(0); // How often this factor appears
      $table->decimal('prediction_accuracy', 5, 2)->nullable(); // How accurate this factor is for predictions
      $table->timestamp('last_updated_weights')->nullable(); // When weights were last adjusted

      // Administrative
      $table->string('created_by')->nullable();
      $table->string('updated_by')->nullable();
      $table->text('update_notes')->nullable();

      $table->timestamps();

      // Indexes
      $table->index(['category', 'is_active']);
      $table->index(['severity_impact', 'is_active']);
      $table->index('ai_detectable');
      $table->index('display_order');
    });
  }

  public function down()
  {
    Schema::dropIfExists('risk_factors');
  }
};
