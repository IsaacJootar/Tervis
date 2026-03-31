<?php

use App\Models\Activity;
use App\Models\Patient;
use App\Models\Registrations\DinActivation;
use App\Services\Communication\ReminderDispatchService;
use App\Services\Patients\PatientPortalAccountService;
use App\Services\Reports\NhmisFieldRegistry;
use App\Services\Seeding\RichFacilityDataGenerator;
use App\Services\Visits\VisitCollationService;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

    $result = $service->queueDueGlobal($facilityId, $patientId);
    $this->info("Reminders queued. Total {$result['total']}, queued {$result['queued']}.");
})->purpose('Queue due reminders for worker-based SMS/Email dispatch.');

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

Artisan::command('seed:rich-facility-data
  {--facilityId= : Existing facility id to seed into}
  {--patients=350 : Number of patients to generate}
  {--months=18 : Date spread in past months}
  {--staff=50 : Additional users/staff to generate}
  {--catalog=130 : Target drug catalog count}
  {--beds=90 : Target bed count}', function () {
  /** @var ClosureCommand $this */
  $options = [
    'facility_id' => $this->option('facilityId') !== null ? (int) $this->option('facilityId') : null,
    'patients' => (int) $this->option('patients'),
    'months' => (int) $this->option('months'),
    'staff' => (int) $this->option('staff'),
    'catalog' => (int) $this->option('catalog'),
    'beds' => (int) $this->option('beds'),
  ];

  $this->warn('Starting high-volume rich data seeding. This appends records into your existing facility data.');

  /** @var RichFacilityDataGenerator $generator */
  $generator = app(RichFacilityDataGenerator::class);
  $summary = $generator->run($options);

  $this->info('Rich facility seeding completed.');
  $this->line('Facility: ' . ($summary['facility_name'] ?? '-') . ' (ID: ' . ($summary['facility_id'] ?? '-') . ')');
  $this->table(
    ['Metric', 'Value'],
    collect($summary)->map(fn($v, $k) => [$k, (string) $v])->values()->all()
  );
})->purpose('Seed realistic high-volume records across modules into an existing active facility for serious workflow/report testing.');

Artisan::command('patients:backfill-portal-accounts {--patientId=} {--dry-run}', function () {
    /** @var ClosureCommand $this */
    $patientId = $this->option('patientId') ? (int) $this->option('patientId') : null;
    $dryRun = (bool) $this->option('dry-run');

    /** @var PatientPortalAccountService $service */
    $service = app(PatientPortalAccountService::class);

    $query = Patient::query()
        ->when($patientId, fn($q) => $q->where('id', $patientId))
        ->with('portalAccount')
        ->orderBy('id');

    $totalPatients = (clone $query)->count();
    if ($totalPatients === 0) {
        $this->warn('No patients found for portal-account backfill.');
        return;
    }

    $processed = 0;
    $alreadyLinked = 0;
    $createdOrLinked = 0;

    $query->chunkById(200, function ($patients) use (&$processed, &$alreadyLinked, &$createdOrLinked, $service, $dryRun) {
        foreach ($patients as $patient) {
            $processed++;

            if ($patient->portalAccount) {
                $alreadyLinked++;
                continue;
            }

            if (!$dryRun) {
                $service->ensureForPatient($patient);
            }

            $createdOrLinked++;
        }
    });

    $mode = $dryRun ? 'Dry run complete.' : 'Patient portal account backfill complete.';
    $this->info($mode);
    $this->table(
        ['Metric', 'Value'],
        [
            ['patients_scanned', (string) $processed],
            ['already_linked', (string) $alreadyLinked],
            ['created_or_linked', (string) $createdOrLinked],
        ]
    );
})->purpose('Create or link missing patient portal accounts for existing patients using DIN credentials.');

if ((bool) config('termii.auto_dispatch_enabled', false)) {
    $command = 'reminders:dispatch-due';
    if ((bool) config('termii.auto_dispatch_with_sync', true)) {
        $command .= ' --sync';
    }

    Schedule::command($command)
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}
