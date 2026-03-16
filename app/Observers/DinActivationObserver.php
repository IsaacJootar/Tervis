<?php

namespace App\Observers;

use App\Models\Registrations\DinActivation;
use App\Services\Visits\VisitCollationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DinActivationObserver
{
  public function created(DinActivation $activation): void
  {
    $date = $this->resolveDate($activation->visit_date);
    $this->sync($activation->patient_id, $activation->facility_id, $date, $date);
  }

  public function updated(DinActivation $activation): void
  {
    $oldDate = $this->resolveDate($activation->getOriginal('visit_date'));
    $newDate = $this->resolveDate($activation->visit_date);

    $pairs = [
      [
        'patient_id' => (int) ($activation->getOriginal('patient_id') ?? $activation->patient_id),
        'facility_id' => (int) ($activation->getOriginal('facility_id') ?? $activation->facility_id),
        'from' => $oldDate,
        'to' => $oldDate,
      ],
      [
        'patient_id' => (int) $activation->patient_id,
        'facility_id' => (int) $activation->facility_id,
        'from' => $newDate,
        'to' => $newDate,
      ],
    ];

    foreach ($pairs as $pair) {
      $this->sync($pair['patient_id'], $pair['facility_id'], $pair['from'], $pair['to']);
    }
  }

  public function deleted(DinActivation $activation): void
  {
    $date = $this->resolveDate($activation->getOriginal('visit_date') ?? $activation->visit_date);
    $patientId = (int) ($activation->getOriginal('patient_id') ?? $activation->patient_id);
    $facilityId = (int) ($activation->getOriginal('facility_id') ?? $activation->facility_id);

    $this->sync($patientId, $facilityId, $date, $date);
  }

  public function restored(DinActivation $activation): void
  {
    $date = $this->resolveDate($activation->visit_date);
    $this->sync($activation->patient_id, $activation->facility_id, $date, $date);
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
      Log::warning('Visit auto-sync failed on DinActivation observer.', [
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

