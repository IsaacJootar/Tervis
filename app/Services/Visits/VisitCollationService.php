<?php

namespace App\Services\Visits;

use App\Models\Activity;
use App\Models\Registrations\DinActivation;
use App\Models\Visit;
use App\Models\VisitEvent;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VisitCollationService
{
  public function syncPatientFacility(
    int $patientId,
    int $facilityId,
    ?string $fromDate = null,
    ?string $toDate = null,
    ?string $recordedBy = null
  ): array {
    return DB::transaction(function () use ($patientId, $facilityId, $fromDate, $toDate, $recordedBy) {
      $activations = $this->loadActivations($patientId, $facilityId, $fromDate, $toDate);
      $activities = $this->loadActivities($patientId, $facilityId, $fromDate, $toDate);
      $existingVisits = $this->loadVisits($patientId, $facilityId, $fromDate, $toDate);

      $activationsByDate = $activations->groupBy(
        fn(DinActivation $activation) => Carbon::parse($activation->visit_date)->toDateString()
      );

      $activitiesByDate = $activities->groupBy(
        fn(Activity $activity) => ($activity->created_at ?? now())->toDateString()
      );

      $existingVisitsByDate = $existingVisits->groupBy(
        fn(Visit $visit) => Carbon::parse($visit->visit_date)->toDateString()
      );

      $allDates = $activationsByDate->keys()
        ->merge($activitiesByDate->keys())
        ->merge($existingVisitsByDate->keys())
        ->unique()
        ->sort()
        ->values();

      $touchCount = 0;
      $deleteCount = 0;
      $eventUpsertCount = 0;
      $eventDeleteCount = 0;

      foreach ($allDates as $date) {
        $activation = $activationsByDate->get($date, collect())
          ->sortBy('check_in_time')
          ->first();

        $activityItems = $activitiesByDate->get($date, collect())->sortBy('created_at')->values();
        $existingVisit = $existingVisitsByDate->get($date, collect())->first();

        if (!$activation && $activityItems->isEmpty()) {
          if ($existingVisit) {
            VisitEvent::query()->where('visit_id', $existingVisit->id)->delete();
            $existingVisit->delete();
            $touchCount++;
            $deleteCount++;
          }
          continue;
        }

        $visit = $this->upsertVisit(
          $patientId,
          $facilityId,
          $date,
          $activation,
          $recordedBy
        );

        if ($visit->wasRecentlyCreated || $visit->wasChanged()) {
          $touchCount++;
        }

        $syncResult = $this->syncEventsForVisit($visit, $activityItems, $patientId, $facilityId);
        $eventUpsertCount += $syncResult['upserted'];
        $eventDeleteCount += $syncResult['deleted'];

        $this->refreshVisitSummary($visit);
      }

      return [
        'visit_dates' => $allDates->count(),
        'visits_touched' => $touchCount,
        'visits_deleted' => $deleteCount,
        'events_upserted' => $eventUpsertCount,
        'events_deleted' => $eventDeleteCount,
      ];
    });
  }

  private function loadActivations(int $patientId, int $facilityId, ?string $fromDate, ?string $toDate): Collection
  {
    $query = DinActivation::query()
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->orderByDesc('visit_date')
      ->orderByDesc('check_in_time');

    $this->applyDateWindow($query, 'visit_date', $fromDate, $toDate);

    return $query->get();
  }

  private function loadActivities(int $patientId, int $facilityId, ?string $fromDate, ?string $toDate): Collection
  {
    $query = Activity::query()
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->orderByDesc('created_at');

    $this->applyDateWindow($query, 'created_at', $fromDate, $toDate);

    return $query->get();
  }

  private function loadVisits(int $patientId, int $facilityId, ?string $fromDate, ?string $toDate): Collection
  {
    $query = Visit::query()
      ->where('patient_id', $patientId)
      ->where('facility_id', $facilityId)
      ->orderByDesc('visit_date');

    $this->applyDateWindow($query, 'visit_date', $fromDate, $toDate);

    return $query->get();
  }

  private function applyDateWindow(Builder $query, string $column, ?string $fromDate, ?string $toDate): void
  {
    if ($fromDate) {
      $query->whereDate($column, '>=', $fromDate);
    }

    if ($toDate) {
      $query->whereDate($column, '<=', $toDate);
    }
  }

  private function upsertVisit(
    int $patientId,
    int $facilityId,
    string $visitDate,
    ?DinActivation $activation,
    ?string $recordedBy
  ): Visit {
    $checkInTime = $this->formatCheckInTime($activation?->check_in_time);
    $isToday = Carbon::parse($visitDate)->isToday();

    return Visit::query()->updateOrCreate(
      [
        'patient_id' => $patientId,
        'facility_id' => $facilityId,
        'visit_date' => $visitDate,
      ],
      [
        'activation_id' => $activation?->id,
        'check_in_time' => $checkInTime,
        'status' => $isToday ? 'open' : 'closed',
        'recorded_by' => $recordedBy,
      ]
    );
  }

  private function formatCheckInTime(mixed $value): ?string
  {
    if (!$value) {
      return null;
    }

    if ($value instanceof CarbonInterface) {
      return $value->format('H:i:s');
    }

    return (string) $value;
  }

  private function syncEventsForVisit(
    Visit $visit,
    Collection $activities,
    int $patientId,
    int $facilityId
  ): array {
    $upserted = 0;
    $activityIds = [];

    foreach ($activities as $activity) {
      $event = VisitEvent::query()->updateOrCreate(
        ['activity_id' => $activity->id],
        [
          'visit_id' => $visit->id,
          'patient_id' => $patientId,
          'facility_id' => $facilityId,
          'event_time' => $activity->created_at ?? now(),
          'module' => $activity->module,
          'action' => $activity->action,
          'description' => $activity->description,
          'performed_by' => $activity->performed_by,
          'source_type' => 'activity',
          'source_id' => $activity->id,
          'meta' => $activity->meta,
        ]
      );

      if ($event->wasRecentlyCreated || $event->wasChanged()) {
        $upserted++;
      }

      $activityIds[] = $activity->id;
    }

    $deleteQuery = VisitEvent::query()
      ->where('visit_id', $visit->id)
      ->where('source_type', 'activity');

    if (count($activityIds) > 0) {
      $deleteQuery->whereNotIn('activity_id', $activityIds);
    }

    $deleted = $deleteQuery->delete();

    return [
      'upserted' => $upserted,
      'deleted' => (int) $deleted,
    ];
  }

  private function refreshVisitSummary(Visit $visit): void
  {
    $events = VisitEvent::query()
      ->where('visit_id', $visit->id)
      ->orderBy('event_time')
      ->get(['event_time', 'module']);

    $moduleBreakdown = $events
      ->pluck('module')
      ->filter()
      ->countBy()
      ->toArray();

    $isToday = optional($visit->visit_date)->isToday();
    $lastEventAt = $events->last()?->event_time;

    $visit->fill([
      'total_events' => $events->count(),
      'modules_summary' => ['by_module' => $moduleBreakdown],
      'status' => $isToday ? 'open' : 'closed',
      'check_out_time' => $isToday ? null : $lastEventAt,
    ])->save();
  }
}
