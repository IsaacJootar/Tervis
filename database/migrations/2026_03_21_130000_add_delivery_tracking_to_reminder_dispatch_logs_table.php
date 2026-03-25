<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('reminder_dispatch_logs', function (Blueprint $table) {
      if (!Schema::hasColumn('reminder_dispatch_logs', 'delivery_status')) {
        $table->string('delivery_status', 50)->nullable()->after('provider_http_code');
      }

      if (!Schema::hasColumn('reminder_dispatch_logs', 'delivery_message')) {
        $table->text('delivery_message')->nullable()->after('delivery_status');
      }

      if (!Schema::hasColumn('reminder_dispatch_logs', 'delivery_payload')) {
        $table->json('delivery_payload')->nullable()->after('delivery_message');
      }

      if (!Schema::hasColumn('reminder_dispatch_logs', 'delivery_updated_at')) {
        $table->timestamp('delivery_updated_at')->nullable()->after('delivery_payload');
      }
    });
  }

  public function down(): void
  {
    Schema::table('reminder_dispatch_logs', function (Blueprint $table) {
      $dropColumns = [];

      if (Schema::hasColumn('reminder_dispatch_logs', 'delivery_updated_at')) {
        $dropColumns[] = 'delivery_updated_at';
      }

      if (Schema::hasColumn('reminder_dispatch_logs', 'delivery_payload')) {
        $dropColumns[] = 'delivery_payload';
      }

      if (Schema::hasColumn('reminder_dispatch_logs', 'delivery_message')) {
        $dropColumns[] = 'delivery_message';
      }

      if (Schema::hasColumn('reminder_dispatch_logs', 'delivery_status')) {
        $dropColumns[] = 'delivery_status';
      }

      if ($dropColumns !== []) {
        $table->dropColumn($dropColumns);
      }
    });
  }
};

