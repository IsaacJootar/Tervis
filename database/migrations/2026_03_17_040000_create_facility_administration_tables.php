<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('facility_service_catalog_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->string('service_code', 40);
      $table->string('service_name', 150);
      $table->string('service_category', 120)->nullable();
      $table->text('description')->nullable();
      $table->decimal('base_fee', 12, 2)->default(0);
      $table->boolean('is_active')->default(true);
      $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();
      $table->softDeletes();

      $table->unique(['facility_id', 'service_code'], 'facility_service_code_unique');
      $table->index(['facility_id', 'is_active'], 'facility_service_active_index');
      $table->index(['facility_id', 'service_name'], 'facility_service_name_index');
    });

    Schema::create('facility_fee_schedules', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->foreignId('facility_service_catalog_item_id')->constrained('facility_service_catalog_items')->onDelete('cascade');
      $table->decimal('amount', 12, 2);
      $table->date('effective_from');
      $table->date('effective_to')->nullable();
      $table->boolean('is_active')->default(true);
      $table->text('notes')->nullable();
      $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();

      $table->index(['facility_id', 'is_active'], 'facility_fee_active_index');
      $table->index(['facility_id', 'effective_from'], 'facility_fee_effective_from_index');
    });

    Schema::create('facility_module_accesses', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->string('module_key', 80);
      $table->string('module_label', 120);
      $table->boolean('is_enabled')->default(true);
      $table->foreignId('last_changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();

      $table->unique(['facility_id', 'module_key'], 'facility_module_key_unique');
      $table->index(['facility_id', 'is_enabled'], 'facility_module_enabled_index');
    });

    Schema::create('facility_admin_audits', function (Blueprint $table) {
      $table->id();
      $table->foreignId('facility_id')->constrained('facilities')->onDelete('cascade');
      $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->string('changed_by_name', 255)->nullable();
      $table->string('action', 120);
      $table->string('target_type', 120)->nullable();
      $table->unsignedBigInteger('target_id')->nullable();
      $table->json('old_values')->nullable();
      $table->json('new_values')->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();

      $table->index(['facility_id', 'created_at'], 'facility_admin_audit_facility_date_index');
      $table->index(['facility_id', 'action'], 'facility_admin_audit_action_index');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('facility_admin_audits');
    Schema::dropIfExists('facility_module_accesses');
    Schema::dropIfExists('facility_fee_schedules');
    Schema::dropIfExists('facility_service_catalog_items');
  }
};

