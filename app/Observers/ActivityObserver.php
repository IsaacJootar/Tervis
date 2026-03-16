<?php

namespace App\Observers;

use App\Models\Activity;
use App\Services\Visits\VisitCollationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityObserver
{
  public function created(Activity $activity): void
  {
    $date = $this->resolveDate($activity->created_at);
    $this->sync($activity->patient_id, $activity->facility_id, $date, $date);
  }

  public function updated(Activity $activity): void
  {
    $oldDate = $this->resolveDate($activity->getOriginal('created_at'));
    $newDate = $this->resolveDate($activity->created_at);

    $pairs = [
      [
        'patient_id' => (int) ($activity->getOriginal('patient_id') ?? $activity->patient_id),
        'facility_id' => (int) ($activity->getOriginal('facility_id') ?? $activity->facility_id),
        'from' => $oldDate,
        'to' => $oldDate,
      ],
      [
        'patient_id' => (int) $activity->patient_id,
        'facility_id' => (int) $activity->facility_id,
        'from' => $newDate,
        'to' => $newDate,
      ],
    ];

    foreach ($pairs as $pair) {
      $this->sync($pair['patient_id'], $pair['facility_id'], $pair['from'], $pair['to']);
    }
  }

  public function deleted(Activity $activity): void
  {
    $date = $this->resolveDate($activity->getOriginal('created_at') ?? $activity->created_at);
    $patientId = (int) ($activity->getOriginal('patient_id') ?? $activity->patient_id);
    $facilityId = (int) ($activity->getOriginal('facility_id') ?? $activity->facility_id);

    $this->sync($patientId, $facilityId, $date, $date);
  }

  private function sync(int $patientId, int $facilityId, ?string $fromDate, ?string $toDate): void
  {
    if ($patientId <= 0 || $facilityId <= 0) {
      return;
    }

    try {
      /** @var VisitCollationService $service */
      $service = app(VisitCollationService::class);
      $service->syncPatientFacility($patientId, $facilityId, $fromDate, $toDate, 'system-auto-sync');
    } catch (\Throwable $e) {
      Log::warning('Visit auto-sync failed on Activity observer.', [
        'patient_id' => $patientId,
        'facility_id' => $facilityId,
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'error' => $e->getMessage(),
      ]);
    }
  }

  private function resolveDate(mixed $value): ?string
  {
    if (!$value) {
      return null;
    }

    return Carbon::parse($value)->toDateString();
  }
}

