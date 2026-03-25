<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('reminder_dispatch_logs', function (Blueprint $table) {
      if (!Schema::hasColumn('reminder_dispatch_logs', 'provider_message_id')) {
        $table->string('provider_message_id', 120)->nullable()->after('provider_message');
      }

      if (!Schema::hasColumn('reminder_dispatch_logs', 'provider_http_code')) {
        $table->unsignedSmallInteger('provider_http_code')->nullable()->after('provider_message_id');
      }
    });
  }

  public function down(): void
  {
    Schema::table('reminder_dispatch_logs', function (Blueprint $table) {
      $dropColumns = [];

      if (Schema::hasColumn('reminder_dispatch_logs', 'provider_http_code')) {
        $dropColumns[] = 'provider_http_code';
      }

      if (Schema::hasColumn('reminder_dispatch_logs', 'provider_message_id')) {
        $dropColumns[] = 'provider_message_id';
      }

      if ($dropColumns !== []) {
        $table->dropColumn($dropColumns);
      }
    });
  }
};

