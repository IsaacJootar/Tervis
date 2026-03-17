<?php

use App\Livewire\Login;

//
use App\Livewire\Avo\DinActivations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

//
use App\Livewire\Core\FacilityReports;
use App\Livewire\Core\FacilityPatients;
use App\Livewire\Core\FacilityRemindersHub;
use App\Livewire\Central\CreateFacility;


//
use App\Livewire\Analytics\RiskDashboard;
use App\Livewire\Core\StaffManagement;
use App\Livewire\Core\FacilityAdministration;

//
use App\Livewire\Core\FacilityDepartments;
use App\Livewire\Core\BedManagement;
use App\Livewire\Core\AdmittedPatients;
use App\Livewire\Core\PharmacyOperations;
use App\Livewire\Core\LaboratoryOperations;
use App\Livewire\Core\FacilitySections;
use App\Livewire\Core\LgaOfficerDashboard;
use App\Livewire\Core\PatientAppointments;

//
use App\Livewire\Core\StateOfficerDashboard;
use App\Livewire\Analytics\RealTimeDashboard;
use App\Livewire\Core\FacilityAdminDashboard;
use App\Livewire\Registers\AntenatalRegister;
use App\Livewire\Workspaces\patientWorkspace;
use App\Livewire\Central\CreateAdministrators;
use App\Livewire\Central\CentralAdminDashboard;
use App\Livewire\Central\FacilityModuleManagement;
use App\Livewire\Workspaces\WorkspaceDashboard;
use App\Livewire\Workspaces\Modules\Attendance;
use App\Livewire\Workspaces\Modules\Activities;
use App\Livewire\Workspaces\Modules\Appointments;
use App\Livewire\Workspaces\Modules\HealthInsurance;
use App\Livewire\Workspaces\Modules\Reminders;
use App\Livewire\Workspaces\Modules\Visits;
use App\Livewire\Workspaces\Modules\ANC\Deliveries;
use App\Livewire\Workspaces\Modules\ANC\Postnatal;
use App\Livewire\Workspaces\Modules\ANC\AncOverview;
use App\Livewire\Workspaces\Modules\ANC\FollowUpAssessment;
use App\Livewire\Workspaces\Modules\Child\Immunizations;
use App\Livewire\Workspaces\Modules\Child\Nutrition;
use App\Livewire\Workspaces\Modules\Child\ActivityRegister;
use App\Livewire\Workspaces\Modules\Clinical\DoctorAssessments;
use App\Livewire\Workspaces\Modules\Clinical\Laboratory;
use App\Livewire\Workspaces\Modules\Clinical\Prescriptions;
use App\Livewire\Workspaces\Modules\Clinical\Invoices;
use App\Livewire\Workspaces\Modules\Clinical\Referrals;
use App\Livewire\Workspaces\Modules\Clinical\DrugCatalog;
use App\Livewire\Workspaces\Modules\FamilyPlanning\ClientFollowUp;
use App\Livewire\Analytics\MpdsrReportDashboard;
use App\Livewire\Analytics\HealthTrendsDashboard;
use App\Livewire\Analytics\MonthlyReportDashboard;
use App\Livewire\Registers\FamilyPlanningRegister;
use App\Livewire\Registers\GeneralPatientsRegister;
use App\Livewire\Analytics\BatchDiagnosticDashboard;
use App\Livewire\Analytics\BatchPredictiveDashboard;
use App\Http\Controllers\language\LanguageController;
use App\Livewire\Workspaces\Modules\ANC\TetanusVaccinations;

// Login
Route::get('/login', Login::class)->name('login');

// Central Admin, this is like our main central system for the tenancy
Route::middleware(['auth', 'role.redirect'])->prefix('central')->group(function () {
  Route::get('/create-administrators', CreateAdministrators::class)->name('create-administrators');
  Route::get('/create-facility', CreateFacility::class)->name('create-facility');
  Route::get('/facility-module-management', FacilityModuleManagement::class)->name('central-facility-module-management');
  Route::get('/central-admin-dashboard', CentralAdminDashboard::class)->name('central-admin-dashboard');
  Route::redirect('/central-admin/users', '/central/create-administrators');
});

// Central legacy path compatibility (kept to prevent broken bookmarks while central menu is cleaned up)
Route::middleware(['auth'])->prefix('central-admin')->group(function () {
  Route::redirect('/roles', '/central/create-administrators');
  Route::redirect('/facilities', '/central/create-facility');
  Route::redirect('/facilities/approval', '/central/create-facility');

  Route::redirect('/reports/system', '/core/reports-hub');
  Route::redirect('/reports/users', '/core/reports-hub');
  Route::redirect('/reports/facilities', '/core/reports-hub');

  Route::redirect('/settings/general', '/central/central-admin-dashboard');
  Route::redirect('/settings/security', '/central/central-admin-dashboard');
  Route::redirect('/settings/backup', '/central/central-admin-dashboard');

  Route::redirect('/audit/system', '/core/facility-administration');
  Route::redirect('/audit/user', '/core/facility-administration');
  Route::redirect('/notifications', '/core/reminders-notifications-hub');
});


// Core (system) routes with middleware
Route::middleware(['auth', 'role.redirect'])->prefix('core')->group(function () {

  Route::get('/staff-management', StaffManagement::class)->name('staff-management');
  Route::get('/facility-administration', FacilityAdministration::class)->name('facility-administration');
  Route::get('/create-data-officers', function () {
    return redirect()->route('staff-management');
  })->name('create-data-officers');
  Route::get('/facility-admin-dashboard', FacilityAdminDashboard::class)->name('facility-admin-dashboard');
  Route::get('/lga-officer-dashboard', LgaOfficerDashboard::class)->name('lga-officer-dashboard');
  Route::get('/state-officer-dashboard', StateOfficerDashboard::class)->name('state-officer-dashboard');
  Route::get('/update-officer-designation', function () {
    return redirect()->route('staff-management');
  })->name('update-officer-designation');
  Route::get('/disable-data-officer-account', function () {
    return redirect()->route('staff-management');
  })->name('disable-data-officer-account');
  Route::get('/facility-departments', FacilityDepartments::class)->name('facility-departments');
  Route::get('/facility-sections', FacilitySections::class)->name('facility-sections');
  Route::get('/bed-management', BedManagement::class)->name('bed-management');
  Route::get('/admitted-patients', AdmittedPatients::class)->name('admitted-patients');
  Route::get('/pharmacy-operations', PharmacyOperations::class)->middleware('module.enabled:prescriptions')->name('pharmacy-operations');
  Route::get('/laboratory-operations', LaboratoryOperations::class)->middleware('module.enabled:laboratory')->name('laboratory-operations');
  Route::get('/facility-patients', FacilityPatients::class)->name('facility-patients');
  Route::get('/patient-appointments', PatientAppointments::class)->middleware('module.enabled:appointments')->name('patient-appointments');
  Route::get('/reminders-notifications-hub', FacilityRemindersHub::class)->middleware('module.enabled:reminders')->name('facility-reminders-hub');
  Route::get('/facility-reports', FacilityReports::class)->middleware('module.enabled:reports')->name('patient-reports');
  Route::get('/reports-hub', FacilityReports::class)->middleware('module.enabled:reports')->name('reports-hub');
  Route::get('/reports-hub/print', function () {
    $payload = session('reports_hub_print_payload', []);

    if (($payload['report_key'] ?? null) === 'monthly_nhmis_summary') {
      $templatePath = resource_path('MONTHLY NHMIS REPORT.html');
      if (is_file($templatePath)) {
        $html = file_get_contents($templatePath);
        if ($html !== false) {
          $keyValues = (array) ($payload['summary_key_values'] ?? []);

          $html = preg_replace_callback(
            '/(<td[^>]*data-summary-key="([^"]+)"[^>]*>)(.*?)(<\/td>)/is',
            function ($match) use ($keyValues) {
              $key = (string) ($match[2] ?? '');
              $value = (int) ($keyValues[$key] ?? 0);
              return (string) ($match[1] ?? '') . $value . (string) ($match[4] ?? '');
            },
            $html
          );

          $dateFrom = (string) ($payload['date_from'] ?? '');
          $dateTo = (string) ($payload['date_to'] ?? '');
          $month = '-';
          $year = '-';
          if (!empty($dateTo)) {
            try {
              $endDate = \Carbon\Carbon::parse($dateTo);
              $month = $endDate->format('F');
              $year = $endDate->format('Y');
            } catch (\Throwable $e) {
              // Ignore parse failure, keep '-'
            }
          }

          $facilityIds = array_values(array_filter(array_map('intval', (array) ($payload['facility_ids'] ?? []))));
          $facilities = collect();
          if (!empty($facilityIds)) {
            $facilities = \App\Models\Facility::query()
              ->whereIn('id', $facilityIds)
              ->get(['id', 'name', 'lga', 'state']);
          }

          $facilityNames = $facilities->pluck('name')->filter()->values()->toArray();
          $healthFacilityLabel = !empty($facilityNames)
            ? implode(', ', $facilityNames)
            : (string) ($payload['scope_label'] ?? '-');

          $lgaLabel = $facilities->pluck('lga')->filter()->unique()->values()->implode(', ');
          $stateLabel = $facilities->pluck('state')->filter()->unique()->values()->implode(', ');

          if ($lgaLabel === '') {
            $lgaLabel = '-';
          }
          if ($stateLabel === '') {
            $stateLabel = '-';
          }

          $bedQuery = \App\Models\Bed::query();
          if (!empty($facilityIds)) {
            $bedQuery->whereIn('facility_id', $facilityIds);
          } else {
            $bedQuery->whereRaw('1 = 0');
          }
          if (\Illuminate\Support\Facades\Schema::hasColumn('beds', 'is_active')) {
            $bedQuery->where('is_active', true);
          }
          $bedsCount = (int) $bedQuery->count();

          $infoValues = [
            (string) ($payload['report_key'] ?? 'monthly_nhmis_summary'),
            $month,
            $year,
            trim(($dateFrom ?: '-') . ' to ' . ($dateTo ?: '-')),
            $healthFacilityLabel,
            $lgaLabel,
            $stateLabel,
            (string) $bedsCount,
          ];

          $index = 0;
          $html = preg_replace_callback('/<div class="val"><\/div>/i', function () use (&$index, $infoValues) {
            $value = htmlspecialchars((string) ($infoValues[$index] ?? ''), ENT_QUOTES, 'UTF-8');
            $index++;
            return '<div class="val">' . $value . '</div>';
          }, $html, 8);

          $reportsHubUrl = route('reports-hub');
          $reportName = htmlspecialchars((string) ($payload['title'] ?? 'Monthly NHMIS Summary'), ENT_QUOTES, 'UTF-8');
          $sectionLabel = htmlspecialchars((string) ($payload['section_label'] ?? 'NHMIS Monthly'), ENT_QUOTES, 'UTF-8');
          $scopeLabel = htmlspecialchars((string) ($payload['scope_label'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $generatedAt = htmlspecialchars((string) ($payload['generated_at'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $generatedBy = htmlspecialchars((string) ($payload['generated_by'] ?? '-'), ENT_QUOTES, 'UTF-8');
          $generatedByRole = htmlspecialchars((string) ($payload['generated_by_role'] ?? ''), ENT_QUOTES, 'UTF-8');
          $signatureDate = htmlspecialchars((string) ($payload['generated_at'] ?? '-'), ENT_QUOTES, 'UTF-8');

          $toolbar = '
            <style>
              .reports-toolbar{display:flex;gap:8px;align-items:center;margin-bottom:10px;padding:8px;background:#111827;color:#fff;}
              .reports-toolbar .btn{background:#fff;color:#111827;border:1px solid #fff;padding:6px 10px;text-decoration:none;border-radius:4px;font-size:12px;}
              .reports-toolbar .meta{font-size:11px;display:flex;gap:10px;flex-wrap:wrap;}
              @media print {.reports-toolbar{display:none !important;}}
            </style>
            <div class="reports-toolbar">
              <a class="btn" href="' . htmlspecialchars($reportsHubUrl, ENT_QUOTES, 'UTF-8') . '">Back To Reports Hub</a>
              <button class="btn" type="button" onclick="window.print()">Print</button>
              <div class="meta">
                <span><strong>Report:</strong> ' . $reportName . '</span>
                <span><strong>Section:</strong> ' . $sectionLabel . '</span>
                <span><strong>Scope:</strong> ' . $scopeLabel . '</span>
                <span><strong>Generated:</strong> ' . $generatedAt . '</span>
                <span><strong>By:</strong> ' . $generatedBy . ($generatedByRole !== '' ? ' (' . $generatedByRole . ')' : '') . '</span>
              </div>
            </div>';

          $html = preg_replace_callback('/<body[^>]*>/i', function ($match) use ($toolbar) {
            return $match[0] . $toolbar;
          }, $html, 1);

          $injectAuthBlock = function (string $blockNo) use (&$html, $generatedBy, $generatedByRole, $signatureDate) {
            $pattern = '/(<strong>\s*' . preg_quote($blockNo, '/') . '\.\s*(?:Completed by|Verified by):\s*<\/strong>\s*<p>Name:\s*<span class="sl">)(.*?)(<\/span><\/p>\s*<p>Designation:\s*<span class="sl">)(.*?)(<\/span><\/p>\s*<p>Signature\/Date:\s*<span class="sl">)(.*?)(<\/span><\/p>)/is';
            $html = preg_replace_callback($pattern, function ($m) use ($generatedBy, $generatedByRole, $signatureDate) {
              return $m[1] . $generatedBy . $m[3] . $generatedByRole . $m[5] . $signatureDate . $m[7];
            }, $html, 1);
          };

          $injectAuthBlock('187');

          $brandFooter = '
            <div style="margin-top:12px;display:flex;align-items:center;justify-content:center;">
              <img src="/assets/cureva-c19-blue-2.png" alt="Cureva" style="height:48px;max-width:186px;width:auto;object-fit:contain;display:block;" />
            </div>';

          $html = preg_replace(
            '/(<p style="margin-top:8px;color:#999">This template contains ALL 187 numbered fields for monthly reporting \(Version\s*2019\)<\/p>)/i',
            '$1' . $brandFooter,
            $html,
            1
          );

          return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
      }
    }

    return view('core.reports-hub-print', compact('payload'));
  })->middleware('module.enabled:reports')->name('reports-hub-print');
});

// Analytics middleware
Route::middleware(['auth', 'role.redirect'])->prefix('analytics')->group(function () {

  Route::get('/real-time-dashboard', RealTimeDashboard::class)->name('real-time-dashboard');
  Route::get('/risk-dashboard', RiskDashboard::class)->name('risk-dashboard');
  Route::get('/health-trends-dashboard', HealthTrendsDashboard::class)->name('health-trends-dashboard');
  Route::get('/diagnostic-assistant', HealthTrendsDashboard::class)->name('diagnostic-assistant');
  Route::get('/batch-diagnostics-dashboard', BatchDiagnosticDashboard::class)->name('batch-diagnostics-dashboard');
  Route::get('/batch-predictive-dashboard', BatchPredictiveDashboard::class)->name('batch-predictive-dashboard');
  Route::get('/mpdsr-report-dashboard', MpdsrReportDashboard::class)->name('mpdsr-report-dashboard');
  Route::get('/monthly-report-dashboard', MonthlyReportDashboard::class)->name('monthly-report-dashboard');
});


// Patient Din Activation and Workspace routes with middleware
Route::middleware(['auth', 'role.redirect'])->prefix('avo')->group(function () {

  Route::get('/din-activations', DinActivations::class)->name('din-activations');
});


// Patient Workspace Access route with middleware
Route::middleware(['auth', 'role.redirect'])->prefix('workspaces')->group(function () {

  Route::get('/patient-workspace', PatientWorkspace::class)->name('patient-workspace');

  Route::get('/drug-catalog', DrugCatalog::class)
    ->middleware('module.enabled:prescriptions')
    ->name('workspaces-drug-catalog-management');

  Route::get('/{patientId}/dashboard', WorkspaceDashboard::class)
    ->name('workspace-dashboard');

  Route::get('/{patientId}/attendance', Attendance::class)
    ->middleware('module.enabled:attendance')
    ->name('workspaces-attendance');

  Route::get('/{patientId}/activities', Activities::class)
    ->name('workspaces-activities');

  Route::get('/{patientId}/assessments', DoctorAssessments::class)
    ->middleware('module.enabled:assessments')
    ->name('workspaces-assessments');


  Route::get('/{patientId}/anc', AncOverview::class)
    ->middleware('module.enabled:anc')
    ->name('workspaces-antenatal');

  Route::get('/{patientId}/anc/follow-up-assessment', FollowUpAssessment::class)
    ->middleware('module.enabled:anc')
    ->name('workspaces-antenatal-followup');

  // TT Vaccinations inside Antenatal workspace
  Route::get('/{patientId}/anc/tetanus-vaccinations', TetanusVaccinations::class)
    ->middleware('module.enabled:anc')
    ->name('workspaces-antenatal-tt-vaccinations');

  Route::get('/{patientId}/anc/deliveries', Deliveries::class)
    ->middleware('module.enabled:anc')
    ->name('workspaces-antenatal-deliveries');

  Route::get('/{patientId}/anc/postnatal', Postnatal::class)
    ->middleware('module.enabled:anc')
    ->name('workspaces-antenatal-postnatal');

  Route::get('/{patientId}/child-health/nutrition', Nutrition::class)
    ->middleware('module.enabled:child_health')
    ->name('workspaces-child-health-nutrition');

  Route::get('/{patientId}/child-health/immunizations', Immunizations::class)
    ->middleware('module.enabled:child_health')
    ->name('workspaces-child-health-immunizations');

  Route::get('/{patientId}/child-health/vaccination-schedule', ActivityRegister::class)
    ->middleware('module.enabled:child_health')
    ->name('workspaces-child-health-vaccination-schedule');

  Route::get('/{patientId}/child-health/activity-register', function ($patientId) {
    return redirect()->route('workspaces-child-health-vaccination-schedule', ['patientId' => $patientId]);
  })->name('workspaces-child-health-activity-register');

  
  Route::get('/{patientId}/laboratory', Laboratory::class)
    ->middleware('module.enabled:laboratory')
    ->name('workspaces-laboratory');

  Route::get('/{patientId}/prescriptions', Prescriptions::class)
    ->middleware('module.enabled:prescriptions')
    ->name('workspaces-prescriptions');

  Route::get('/{patientId}/invoices', Invoices::class)
    ->middleware('module.enabled:invoices')
    ->name('workspaces-invoices');

  Route::get('/{patientId}/appointments', Appointments::class)
    ->middleware('module.enabled:appointments')
    ->name('workspaces-appointments');

  Route::get('/{patientId}/visits', Visits::class)
    ->middleware('module.enabled:visits')
    ->name('workspaces-visits');

  Route::get('/{patientId}/reminders', Reminders::class)
    ->middleware('module.enabled:reminders')
    ->name('workspaces-reminders');

  Route::get('/{patientId}/family-planning', ClientFollowUp::class)
    ->middleware('module.enabled:family_planning')
    ->name('workspaces-family-planning');

  Route::get('/{patientId}/health-insurance', HealthInsurance::class)
    ->middleware('module.enabled:health_insurance')
    ->name('workspaces-health-insurance');

  Route::get('/{patientId}/referrals', Referrals::class)
    ->middleware('module.enabled:referrals')
    ->name('workspaces-referrals');

  Route::get('/{patientId}/drug-catalog', function ($patientId) {
    return redirect()->route('workspaces-drug-catalog-management');
  })->name('workspaces-drug-catalog');

});




// Registers routes with middleware
Route::middleware(['auth', 'role.redirect'])->prefix('registers')->group(function () {
  //3 Tier Entry Points
  Route::get('/antenatal-register', AntenatalRegister::class)->name('antenatal-register');
  Route::get('/general-patients-register', GeneralPatientsRegister::class)
    ->name('general-patients-register');
  Route::get('/family-planning-register', FamilyPlanningRegister::class)
    ->name('family-planning-register');
});



// Home redirect
Route::get('/', function () {
  return redirect()->route('login'); // a route just be here for now, am coming
});



// Logout route
Route::post('/logout', function () {

  Auth::logout();
  request()->session()->invalidate();
  request()->session()->regenerateToken();
  return redirect()->route('login');
})->name('logout');






