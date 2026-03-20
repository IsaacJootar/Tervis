<?php

namespace App\Services\Ui;

use App\Models\Reminder;
use App\Models\ReminderDispatchLog;
use App\Models\User;
use App\Services\DataScopeService;
use App\Services\Security\RolePermissionService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class NavbarContextService
{
  public function __construct(private readonly DataScopeService $scopeService)
  {
  }

  /**
   * @return array{
   *   quick_links: array<int,array{url:string,name:string,icon:string,meta:string}>,
   *   alert_count: int,
   *   alert_items: array<int,array{title:string,subtitle:string,status:string}>,
   *   failed_count: int
   * }
   */
  public function build(?User $user): array
  {
    if (!$user) {
      return [
        'quick_links' => [],
        'alert_count' => 0,
        'alert_items' => [],
        'failed_count' => 0,
      ];
    }

    $quickLinks = $this->buildQuickLinks($user, 4);
    $alerts = $this->buildAlerts($user, 6);

    return [
      'quick_links' => $quickLinks,
      'alert_count' => $alerts['count'],
      'alert_items' => $alerts['items'],
      'failed_count' => $alerts['failed_count'],
    ];
  }

  /**
   * @return array<int,array{url:string,name:string,icon:string,meta:string}>
   */
  private function buildQuickLinks(User $user, int $limit): array
  {
    $menuFile = $this->menuFileForRole((string) $user->role);
    if ($menuFile === null) {
      return [];
    }

    $menuPath = resource_path('menu/' . $menuFile);
    if (!File::exists($menuPath)) {
      return [];
    }

    $json = json_decode((string) File::get($menuPath), true);
    if (!is_array($json) || !isset($json['menu']) || !is_array($json['menu'])) {
      return [];
    }

    $entries = $this->flattenMenuNodes($json['menu']);

    $entries = array_values(array_filter($entries, function (array $entry) use ($user) {
      return RolePermissionService::canAccessMenuUrl($user, $entry['url']);
    }));

    $entries = array_values(array_filter($entries, fn(array $entry) => $entry['url'] !== '/account/settings'));

    $entries = array_slice($entries, 0, max(1, $limit));

    $palette = ['primary', 'success', 'info', 'warning'];
    foreach ($entries as $index => &$entry) {
      $entry['meta'] = ucfirst($palette[$index % count($palette)]);
    }
    unset($entry);

    return $entries;
  }

  /**
   * @param array<int,mixed> $nodes
   * @return array<int,array{url:string,name:string,icon:string,meta:string}>
   */
  private function flattenMenuNodes(array $nodes): array
  {
    $result = [];

    foreach ($nodes as $node) {
      if (!is_array($node)) {
        continue;
      }

      $url = trim((string) ($node['url'] ?? ''));
      $name = trim((string) ($node['name'] ?? ''));
      $iconRaw = (string) ($node['icon'] ?? '');
      $icon = $this->extractTablerIcon($iconRaw);

      if ($url !== '' && $name !== '') {
        $result[] = [
          'url' => $url,
          'name' => $name,
          'icon' => $icon,
          'meta' => 'Primary',
        ];
      }

      if (isset($node['submenu']) && is_array($node['submenu'])) {
        $result = array_merge($result, $this->flattenMenuNodes($node['submenu']));
      }
    }

    return $result;
  }

  private function extractTablerIcon(string $iconRaw): string
  {
    if (preg_match('/tabler-[a-z0-9-]+/i', $iconRaw, $matches) === 1) {
      return 'icon-base ti ' . $matches[0];
    }

    return 'icon-base ti tabler-link';
  }

  /**
   * @return array{count:int,items:array<int,array{title:string,subtitle:string,status:string}>,failed_count:int}
   */
  private function buildAlerts(User $user, int $limit): array
  {
    if (!Schema::hasTable('reminders')) {
      return ['count' => 0, 'items' => [], 'failed_count' => 0];
    }

    $baseQuery = Reminder::query()
      ->with(['patient:id,first_name,last_name,din', 'facility:id,name'])
      ->select(['id', 'facility_id', 'patient_id', 'title', 'reminder_date', 'reminder_time', 'status'])
      ->whereIn('status', ['pending', 'queued']);

    $this->applyScopeToReminders($baseQuery, $user);

    $count = (clone $baseQuery)->count();

    $rows = (clone $baseQuery)
      ->orderBy('reminder_date')
      ->orderBy('reminder_time')
      ->limit(max(1, $limit))
      ->get();

    $items = [];
    foreach ($rows as $row) {
      $patientName = trim((string) (($row->patient?->first_name ?? '') . ' ' . ($row->patient?->last_name ?? '')));
      $patientPart = $patientName !== '' ? $patientName : ('DIN ' . ((string) ($row->patient?->din ?? 'N/A')));
      $facilityPart = (string) ($row->facility?->name ?? 'N/A Facility');
      $when = $row->reminder_date ? $row->reminder_date->format('d M Y') : 'No date';

      $items[] = [
        'title' => (string) ($row->title ?: 'Reminder Pending'),
        'subtitle' => $patientPart . ' - ' . $facilityPart . ' - ' . $when,
        'status' => (string) ($row->status ?? 'pending'),
      ];
    }

    $failedCount = 0;
    if (Schema::hasTable('reminder_dispatch_logs')) {
      $failedQuery = ReminderDispatchLog::query()
        ->where('status', 'failed')
        ->where('created_at', '>=', now()->subDay());

      $this->applyScopeToDispatchLogs($failedQuery, $user);
      $failedCount = (int) $failedQuery->count();
    }

    return [
      'count' => (int) $count,
      'items' => $items,
      'failed_count' => $failedCount,
    ];
  }

  private function applyScopeToReminders($query, User $user): void
  {
    $role = (string) ($user->role ?? '');

    if (in_array($role, ['Central Admin', 'Central Administrator'], true)) {
      return;
    }

    if (in_array($role, ['State Data Administrator', 'State Administrator', 'LGA Officer', 'LGA Data Administrator', 'LGA Administrator'], true)) {
      $scope = $this->scopeService->getUserScope();
      $facilityIds = array_values(array_filter((array) ($scope['facility_ids'] ?? []), fn($id) => (int) $id > 0));
      if (count($facilityIds) === 0) {
        $query->whereRaw('1 = 0');
        return;
      }
      $query->whereIn('facility_id', $facilityIds);
      return;
    }

    if ($role === 'Patient') {
      $phone = trim((string) ($user->phone ?? ''));
      $email = trim((string) ($user->email ?? ''));

      if ($phone === '' && $email === '') {
        $query->whereRaw('1 = 0');
        return;
      }

      $query->where(function ($q) use ($phone, $email) {
        if ($phone !== '') {
          $q->orWhere('recipient_phone', $phone);
        }
        if ($email !== '') {
          $q->orWhere('recipient_email', $email);
        }
      });

      return;
    }

    $facilityId = (int) ($user->facility_id ?? 0);
    if ($facilityId > 0) {
      $query->where('facility_id', $facilityId);
      return;
    }

    $query->whereRaw('1 = 0');
  }

  private function applyScopeToDispatchLogs($query, User $user): void
  {
    $role = (string) ($user->role ?? '');

    if (in_array($role, ['Central Admin', 'Central Administrator'], true)) {
      return;
    }

    if (in_array($role, ['State Data Administrator', 'State Administrator', 'LGA Officer', 'LGA Data Administrator', 'LGA Administrator'], true)) {
      $scope = $this->scopeService->getUserScope();
      $facilityIds = array_values(array_filter((array) ($scope['facility_ids'] ?? []), fn($id) => (int) $id > 0));
      if (count($facilityIds) === 0) {
        $query->whereRaw('1 = 0');
        return;
      }
      $query->whereIn('facility_id', $facilityIds);
      return;
    }

    $facilityId = (int) ($user->facility_id ?? 0);
    if ($facilityId > 0) {
      $query->where('facility_id', $facilityId);
      return;
    }

    $query->whereRaw('1 = 0');
  }

  private function menuFileForRole(string $role): ?string
  {
    return match ($role) {
      'Central Admin', 'Central Administrator' => 'centralAdminMenu.json',
      'Facility Administrator' => 'facilityAdminMenu.json',
      'Data Officer' => 'dataOfficerMenu.json',
      'State Data Administrator', 'State Administrator' => 'stateOfficerMenu.json',
      'LGA Officer', 'LGA Data Administrator', 'LGA Administrator' => 'lgaOfficerMenu.json',
      'Verification Officer' => 'avoOfficerMenu.json',
      'Patient' => 'patientMenu.json',
      default => null,
    };
  }
}
