<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
  {
    Schema::create('health_trends', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('state_id')->nullable()->constrained()->onDelete('set null');
      $table->foreignId('lga_id')->nullable()->constrained()->onDelete('set null');

      // Trend Identification
      $table->string('trend_type', 100); // maternal_mortality, cesarean_rate, high_risk_increase, etc.
      $table->string('metric_name', 100); // specific metric being tracked
      $table->string('trend_category', 50); // clinical, operational, demographic

      // Time Period
      $table->date('period_start');
      $table->date('period_end');
      $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');

      // Trend Data
      $table->decimal('current_value', 10, 2);
      $table->decimal('previous_value', 10, 2)->nullable();
      $table->decimal('percentage_change', 8, 2)->nullable();
      $table->enum('trend_direction', ['increasing', 'decreasing', 'stable', 'fluctuating']);
      $table->enum('trend_severity', ['minimal', 'moderate', 'significant', 'critical']);

      // Statistical Analysis
      $table->integer('sample_size'); // number of records this trend is based on
      $table->decimal('confidence_level', 5, 2)->default(95.00);
      $table->json('trend_data_points')->nullable(); // historical data points
      $table->json('statistical_metadata')->nullable(); // p-values, standard deviation, etc.

      // AI Analysis
      $table->text('ai_interpretation')->nullable(); // AI's explanation of the trend
      $table->json('contributing_factors')->nullable(); // factors identified by AI
      $table->json('predictions')->nullable(); // future trend predictions
      $table->enum('alert_level', ['none', 'watch', 'warning', 'urgent'])->default('none');

      // Geographic Scope
      $table->enum('geographic_scope', ['facility', 'ward', 'lga', 'state', 'national'])->default('facility');
      $table->json('affected_demographics')->nullable(); // age groups, risk categories affected

      // Action Tracking
      $table->boolean('requires_intervention')->default(false);
      $table->json('recommended_actions')->nullable();
      $table->text('intervention_notes')->nullable();
      $table->date('last_reviewed')->nullable();
      $table->string('reviewed_by')->nullable();

      $table->timestamps();

      // Indexes for performance
      $table->index(['facility_id', 'trend_type', 'period_start']);
      $table->index(['trend_severity', 'alert_level']);
      $table->index(['period_start', 'period_end']);
      $table->index(['trend_category', 'trend_direction']);
    });
  }

  public function down()
  {
    Schema::dropIfExists('health_trends');
  }
};
