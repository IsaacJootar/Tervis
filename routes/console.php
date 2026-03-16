<?php

use App\Models\Registrations\DinActivation;
use App\Services\Communication\ReminderDispatchService;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminders:dispatch-due {--facilityId=} {--patientId=} {--sync}', function () {
    /** @var ClosureCommand $this */
    $facilityId = $this->option('facilityId') ? (int) $this->option('facilityId') : null;
    $patientId = $this->option('patientId') ? (int) $this->option('patientId') : null;
    $sync = (bool) $this->option('sync');

    /** @var ReminderDispatchService $service */
    $service = app(ReminderDispatchService::class);

    if ($sync) {
        if ($facilityId && $patientId) {
            $service->syncFromModuleDates($patientId, $facilityId, 'scheduler', 'system');
        } else {
            $activationQuery = DinActivation::query()
                ->whereDate('visit_date', today())
                ->select('patient_id', 'facility_id')
                ->distinct();

            if ($facilityId) {
                $activationQuery->where('facility_id', $facilityId);
            }

            if ($patientId) {
                $activationQuery->where('patient_id', $patientId);
            }

            $pairs = $activationQuery->get();
            foreach ($pairs as $pair) {
                $service->syncFromModuleDates((int) $pair->patient_id, (int) $pair->facility_id, 'scheduler', 'system');
            }
        }
    }

    $result = $service->dispatchDueGlobal($facilityId, $patientId);
    $this->info("Reminders dispatch complete. Total {$result['total']}, sent {$result['sent']}, failed {$result['failed']}, skipped {$result['skipped']}.");
})->purpose('Dispatch due reminders using placeholder SMS/Email channels.');
