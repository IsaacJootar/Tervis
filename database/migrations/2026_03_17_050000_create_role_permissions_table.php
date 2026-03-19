<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('role_permissions', function (Blueprint $table) {
      $table->id();
      $table->string('role_name', 120);
      $table->string('permission_key', 160);
      $table->string('permission_label', 200)->nullable();
      $table->string('permission_group', 80)->nullable();
      $table->boolean('is_allowed')->default(false);
      $table->unsignedBigInteger('last_changed_by_user_id')->nullable();
      $table->timestamps();

      $table->unique(['role_name', 'permission_key'], 'role_permission_unique');
      $table->index(['role_name', 'is_allowed'], 'role_permission_role_enabled_idx');
      $table->index(['permission_key', 'is_allowed'], 'role_permission_key_enabled_idx');

      $table->foreign('last_changed_by_user_id')
        ->references('id')
        ->on('users')
        ->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('role_permissions');
  }
};

