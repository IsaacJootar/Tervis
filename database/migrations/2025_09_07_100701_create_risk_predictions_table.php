<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('risk_predictions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('antenatal_id')->nullable()->constrained()->onDelete('set null');

      // Risk Scoring
      $table->integer('total_risk_score')->default(0);
      $table->enum('risk_level', ['low', 'moderate', 'high', 'critical'])->default('low');
      $table->decimal('risk_percentage', 5, 2)->default(0.00);

      // AI Predictions
      $table->json('identified_risks')->nullable(); // Array of risk factors found
      $table->json('ai_recommendations')->nullable(); // AI-generated recommendations
      $table->json('prediction_confidence')->nullable(); // Confidence scores

      // Clinical Context
      $table->integer('gestational_age_weeks')->nullable();
      $table->date('assessment_date');
      $table->date('next_assessment_due')->nullable();
      $table->text('clinical_notes')->nullable();

      // Prediction Outcomes
      $table->json('predicted_outcomes')->nullable(); // What AI predicts might happen
      $table->json('actual_outcomes')->nullable(); // What actually happened (for ML training)
      $table->boolean('outcome_verified')->default(false);

      // Model Metadata
      $table->string('model_version', 50)->default('1.0');
      $table->timestamp('prediction_timestamp');
      $table->string('assessment_type', 100)->default('routine'); // routine, emergency, follow-up

      // Officer Information
      $table->string('officer_name');
      $table->string('officer_role');
      $table->string('officer_designation');

      $table->timestamps();

      // Indexes for performance
      $table->index(['user_id', 'assessment_date']);
      $table->index(['facility_id', 'risk_level']);
      $table->index(['risk_level', 'assessment_date']);
      $table->index('next_assessment_due');
    });
  }

  public function down()
  {
    Schema::dropIfExists('risk_predictions');
  }
};
