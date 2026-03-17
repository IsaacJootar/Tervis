<?php

use App\Models\Activity;
use App\Models\Registrations\DinActivation;
use App\Services\Communication\ReminderDispatchService;
use App\Services\Reports\NhmisFieldRegistry;
use App\Services\Visits\VisitCollationService;
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

Artisan::command('visits:backfill {--facilityId=} {--patientId=} {--from=} {--to=}', function () {
    /** @var ClosureCommand $this */
    $facilityId = $this->option('facilityId') ? (int) $this->option('facilityId') : null;
    $patientId = $this->option('patientId') ? (int) $this->option('patientId') : null;
    $fromDate = $this->option('from') ?: null;
    $toDate = $this->option('to') ?: null;

    $activationPairs = DinActivation::query()
        ->select('patient_id', 'facility_id')
        ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
        ->when($patientId, fn($q) => $q->where('patient_id', $patientId));

    $activityPairs = Activity::query()
        ->select('patient_id', 'facility_id')
        ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
        ->when($patientId, fn($q) => $q->where('patient_id', $patientId));

    $pairs = $activationPairs->union($activityPairs)->get();

    if ($pairs->isEmpty()) {
        $this->warn('No patient/facility pairs found for backfill.');
        return;
    }

    /** @var VisitCollationService $service */
    $service = app(VisitCollationService::class);

    $totalDates = 0;
    $totalVisitsTouched = 0;
    $totalEventsUpserted = 0;
    $totalEventsDeleted = 0;

    foreach ($pairs as $pair) {
        $result = $service->syncPatientFacility(
            (int) $pair->patient_id,
            (int) $pair->facility_id,
            $fromDate,
            $toDate,
            'console-backfill'
        );

        $totalDates += $result['visit_dates'];
        $totalVisitsTouched += $result['visits_touched'];
        $totalEventsUpserted += $result['events_upserted'];
        $totalEventsDeleted += $result['events_deleted'];
    }

    $this->info(
        "Visits backfill complete. pairs={$pairs->count()}, dates={$totalDates}, visits_touched={$totalVisitsTouched}, events_upserted={$totalEventsUpserted}, events_deleted={$totalEventsDeleted}."
    );
})->purpose('Build or refresh visits and visit events from DIN activations + activity timeline.');

Artisan::command('nhmis:sync-matrix {--path=docs/nhmis-field-matrix.json}', function () {
    /** @var ClosureCommand $this */
    $path = (string) $this->option('path');

    /** @var NhmisFieldRegistry $registry */
    $registry = app(NhmisFieldRegistry::class);
    $matrix = $registry->syncMatrix($path !== '' ? $path : null);

    $statusCounts = collect($matrix)->countBy('status')->toArray();
    $this->info('NHMIS matrix synced: ' . count($matrix) . ' fields.');
    $this->line('Status counts: ' . json_encode($statusCounts));
    $this->line('Path: ' . base_path($path !== '' ? $path : NhmisFieldRegistry::DEFAULT_MATRIX_PATH));
})->purpose('Generate canonical 187-field NHMIS matrix JSON from template + key registry metadata.');
