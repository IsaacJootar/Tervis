<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('lab_processing_batches', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->string('batch_code', 80)->unique();
      $table->string('test_profile', 160)->nullable();
      $table->string('analyzer_name', 160)->nullable();
      $table->date('run_date');
      $table->string('status', 30)->default('scheduled');
      $table->unsignedInteger('sample_count')->default(0);
      $table->text('notes')->nullable();
      $table->string('created_by', 160)->nullable();
      $table->string('completed_by', 160)->nullable();
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'run_date']);
      $table->index(['facility_id', 'status']);
    });

    Schema::create('lab_samples', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
      $table->foreignId('lab_test_order_id')->nullable()->constrained('lab_test_orders')->nullOnDelete();
      $table->foreignId('processing_batch_id')->nullable()->constrained('lab_processing_batches')->nullOnDelete();
      $table->string('accession_no', 80)->unique();
      $table->string('test_name', 160);
      $table->string('specimen_type', 120)->nullable();
      $table->string('sample_status', 30)->default('received');
      $table->timestamp('collected_at')->nullable();
      $table->timestamp('received_at')->nullable();
      $table->string('received_by', 160)->nullable();
      $table->text('remarks')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'sample_status']);
      $table->index(['facility_id', 'received_at']);
      $table->index(['lab_test_order_id', 'sample_status']);
    });

    Schema::create('lab_qc_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->date('qc_date');
      $table->string('qc_type', 30)->default('internal');
      $table->string('test_profile', 160);
      $table->string('control_level', 80)->nullable();
      $table->string('expected_range', 120)->nullable();
      $table->string('observed_value', 120)->nullable();
      $table->string('status', 30)->default('pass');
      $table->string('reviewed_by', 160)->nullable();
      $table->text('remarks')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'qc_date']);
      $table->index(['facility_id', 'status']);
    });

    Schema::create('lab_reagent_stocks', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->string('reagent_name', 160);
      $table->string('lot_number', 120)->nullable();
      $table->string('unit', 60)->default('units');
      $table->decimal('quantity_available', 12, 2)->default(0);
      $table->decimal('reorder_level', 12, 2)->default(0);
      $table->date('expiry_date')->nullable();
      $table->string('manufacturer', 160)->nullable();
      $table->boolean('is_active')->default(true);
      $table->text('notes')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'reagent_name']);
      $table->index(['facility_id', 'is_active']);
    });

    Schema::create('lab_reagent_movements', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->foreignId('lab_reagent_stock_id')->constrained('lab_reagent_stocks')->onDelete('cascade');
      $table->string('movement_type', 40);
      $table->decimal('quantity', 12, 2);
      $table->decimal('balance_after', 12, 2);
      $table->timestamp('moved_at')->nullable();
      $table->string('moved_by', 160)->nullable();
      $table->string('reference_code', 120)->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'moved_at']);
      $table->index(['lab_reagent_stock_id', 'movement_type']);
    });

    Schema::create('lab_equipment_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained()->onDelete('cascade');
      $table->string('equipment_name', 160);
      $table->string('equipment_code', 120)->nullable();
      $table->string('log_type', 40)->default('calibration');
      $table->date('performed_date');
      $table->date('next_due_date')->nullable();
      $table->string('result_status', 30)->default('pending');
      $table->string('performed_by', 160)->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['facility_id', 'performed_date']);
      $table->index(['facility_id', 'next_due_date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('lab_equipment_logs');
    Schema::dropIfExists('lab_reagent_movements');
    Schema::dropIfExists('lab_reagent_stocks');
    Schema::dropIfExists('lab_qc_logs');
    Schema::dropIfExists('lab_samples');
    Schema::dropIfExists('lab_processing_batches');
  }
};

