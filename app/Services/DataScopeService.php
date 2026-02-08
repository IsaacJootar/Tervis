<?php

namespace App\Services;

use App\Models\Facility;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DataScopeService
{
  /**
   * Get the data scope for the authenticated user
   */
  public function getUserScope()
  {
    $user = Auth::user();

    return [
      'scope_type' => $this->determineScopeType($user),
      'facility_ids' => $this->getFacilityIds($user),
      'lga_id' => $user->lga_id,
      'state_id' => $user->state_id,
      'user' => $user
    ];
  }

  /**
   * Determine the scope type based on user role
   */
  private function determineScopeType($user)
  {
    if (in_array($user->role, ['State Data Administrator', 'State Administrator'])) {
      return 'state';
    } elseif (in_array($user->role, ['LGA Officer', 'LGA Data Administrator', 'LGA Administrator'])) {
      return 'lga';
    } elseif (in_array($user->role, ['Facility Administrator', 'Data Officer'])) {
      return 'facility';
    }

    return 'facility'; // default
  }

  /**
   * Get all facility IDs within user's scope
   */
  private function getFacilityIds($user)
  {
    $scopeType = $this->determineScopeType($user);
    switch ($scopeType) {
      case 'state':
        if (!$user->state_id) {
          Log::warning('State admin has no state_id', ['user_id' => $user->id]);
          return [];
        }

        $facilityIds = Facility::where('state_id', $user->state_id)
          ->pluck('id')
          ->toArray();

        Log::info('State scope facilities found', [
          'state_id' => $user->state_id,
          'facility_count' => count($facilityIds),
          'facility_ids' => $facilityIds
        ]);

        return $facilityIds;

      case 'lga':
        if (!$user->lga_id) {
          Log::warning('LGA admin has no lga_id', ['user_id' => $user->id]);
          return [];
        }

        $facilityIds = Facility::where('lga_id', $user->lga_id)
          ->pluck('id')
          ->toArray();

        Log::info('LGA scope facilities found', [
          'lga_id' => $user->lga_id,
          'facility_count' => count($facilityIds),
          'facility_ids' => $facilityIds
        ]);

        return $facilityIds;

      case 'facility':
      default:
        if (!$user->facility_id) {
          Log::warning('Facility user has no facility_id', ['user_id' => $user->id]);
          return [];
        }

        return [$user->facility_id];
    }
  }
  /**
   * Apply scope to a query builder
   */
  public function applyScope($query, $facilityColumn = 'facility_id')
  {
    $scope = $this->getUserScope();

    if (empty($scope['facility_ids'])) {
      // Return empty result if no facilities found
      return $query->whereRaw('1 = 0');
    }

    if ($scope['scope_type'] === 'facility' && count($scope['facility_ids']) === 1) {
      return $query->where($facilityColumn, $scope['facility_ids'][0]);
    }

    return $query->whereIn($facilityColumn, $scope['facility_ids']);
  }

  /**
   * Normalize facility IDs input (handles both int and array)
   */
  public function normalizeFacilityIds($facilityIds)
  {
    if (is_null($facilityIds)) {
      return $this->getUserScope()['facility_ids'];
    }

    //if id is one(single facility or more[array of stores facility IDS])
    return is_array($facilityIds) ? $facilityIds : [$facilityIds];
  }
}
