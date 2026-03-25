<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('role_permissions')) {
      return;
    }

    $oldKey = 'avo.din_activation.manage';
    $newKey = 'activations.din_activation.manage';

    $rows = DB::table('role_permissions')
      ->where('permission_key', $oldKey)
      ->get(['id', 'role_name', 'permission_label', 'permission_group', 'is_allowed', 'last_changed_by_user_id', 'created_at', 'updated_at']);

    foreach ($rows as $row) {
      $existsNew = DB::table('role_permissions')
        ->where('role_name', $row->role_name)
        ->where('permission_key', $newKey)
        ->exists();

      if ($existsNew) {
        DB::table('role_permissions')->where('id', $row->id)->delete();
        continue;
      }

      DB::table('role_permissions')
        ->where('id', $row->id)
        ->update([
          'permission_key' => $newKey,
          'permission_label' => 'Manage DIN Activations',
          'permission_group' => 'verification',
          'updated_at' => now(),
        ]);
    }
  }

  public function down(): void
  {
    if (!Schema::hasTable('role_permissions')) {
      return;
    }

    $oldKey = 'avo.din_activation.manage';
    $newKey = 'activations.din_activation.manage';

    $rows = DB::table('role_permissions')
      ->where('permission_key', $newKey)
      ->get(['id', 'role_name']);

    foreach ($rows as $row) {
      $existsOld = DB::table('role_permissions')
        ->where('role_name', $row->role_name)
        ->where('permission_key', $oldKey)
        ->exists();

      if ($existsOld) {
        continue;
      }

      DB::table('role_permissions')
        ->where('id', $row->id)
        ->update([
          'permission_key' => $oldKey,
          'permission_label' => 'Manage DIN Activations',
          'permission_group' => 'verification',
          'updated_at' => now(),
        ]);
    }
  }
};

