<?php

use App\Livewire\Login;

//
use App\Livewire\Avo\DinActivations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

//
use App\Livewire\Core\FacilityReports;
use App\Livewire\Core\FacilityPatients;
use App\Livewire\Central\CreateFacility;
use App\Livewire\Patient\PatientProfile;


//
use App\Livewire\Patient\PatientTetanus;
use App\Livewire\Analytics\RiskDashboard;
use App\Livewire\Core\CreateDataofficers;

//
use App\Livewire\Core\FacilityDepartments;
use App\Livewire\Core\LgaOfficerDashboard;
use App\Livewire\Core\PatientAppointments;

//
use App\Livewire\Patient\PatientAntenatal;
use App\Livewire\Patient\PatientDashboard;
use App\Livewire\Patient\PatientPostnatal;
use App\Livewire\Patient\PatientDeliveries;
use App\Livewire\Core\StateOfficerDashboard;
use App\Livewire\Analytics\RealTimeDashboard;
use App\Livewire\Core\FacilityAdminDashboard;
use App\Livewire\Registers\AntenatalRegister;
use App\Livewire\Workspaces\patientWorkspace;
use App\Livewire\Central\CreateAdministrators;
use App\Livewire\Central\CentralAdminDashboard;
use App\Livewire\Core\UpdateOfficerDesignation;
use App\Livewire\Workspaces\WorkspaceDashboard;
use App\Livewire\Workspaces\Modules\Attendance;
use App\Livewire\Workspaces\Modules\Activities;
use App\Livewire\Workspaces\Modules\ANC\Deliveries;
use App\Livewire\Workspaces\Modules\ANC\Postnatal;
use App\Livewire\Workspaces\Modules\ANC\AncOverview;
use App\Livewire\Workspaces\Modules\ANC\FollowUpAssessment;
use App\Livewire\Analytics\MpdsrReportDashboard;
use App\Livewire\Core\DisableDataOfficerAccount;
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

// Central Admin, this is like our main central system for the tenenacy. I wil improve this module later
//Route::middleware(['auth', 'role.redirect'])->prefix('central')->group(function () {
Route::get('/central/create-administrators', CreateAdministrators::class)->name('create-administrators');
Route::get('/central/create-facility', CreateFacility::class)->name('create-facility');
Route::get('/central/central-admin-dashboard', CentralAdminDashboard::class)->name('central-admin-dashboard');


// Core (system) routes with middleware
Route::middleware(['auth', 'role.redirect'])->prefix('core')->group(function () {

  Route::get('/create-data-officers', CreateDataOfficers::class)->name('create-data-officers');
  Route::get('/facility-admin-dashboard', FacilityAdminDashboard::class)->name('facility-admin-dashboard');
  Route::get('/lga-officer-dashboard', LgaOfficerDashboard::class)->name('lga-officer-dashboard');
  Route::get('/state-officer-dashboard', StateOfficerDashboard::class)->name('state-officer-dashboard');
  Route::get('/update-officer-designation', UpdateOfficerDesignation::class)->name('update-officer-designation');
  Route::get('/disable-data-officer-account', DisableDataOfficerAccount::class)->name('disable-data-officer-account');
  Route::get('/facility-departments', FacilityDepartments::class)->name('facility-departments');
  Route::get('/facility-patients', FacilityPatients::class)->name('facility-patients');
  Route::get('/patient-appointments', PatientAppointments::class)->name('patient-appointments');
  Route::get('/facility-reports', FacilityReports::class)->name('patient-reports');
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



// Patient routes with middleware
Route::middleware(['auth', 'role.redirect'])->prefix('patient')->group(function () {
  Route::get('/patient-dashboard', PatientDashboard::class)->name('patient-dashboard');
  Route::get('/patient-profile', PatientProfile::class)->name('patient-profile');
  Route::get('/patient-antenatal', PatientAntenatal::class)->name('patient-antenatal');
  Route::get('/patient-deliveries', PatientDeliveries::class)->name('patient-deliveries');
  Route::get('/patient-postnatal', PatientPostnatal::class)->name('patient-postnatal');
  Route::get('/patient-tetanus', PatientTetanus::class)->name('patient-tetanus');
});


// Patient Din Activation and Workspace routes with middleware
Route::middleware(['auth', 'role.redirect'])->prefix('avo')->group(function () {

  Route::get('/din-activations', DinActivations::class)->name('din-activations');
});


// Patient Workspace Access route with middleware
Route::middleware(['auth', 'role.redirect'])->prefix('workspaces')->group(function () {

  Route::get('/patient-workspace', PatientWorkspace::class)->name('patient-workspace');

  Route::get('/{patientId}/dashboard', WorkspaceDashboard::class)
    ->name('workspace-dashboard');

  Route::get('/{patientId}/attendance', Attendance::class)
    ->name('workspaces-attendance');

  Route::get('/{patientId}/activities', Activities::class)
    ->name('workspaces-activities');

  Route::get('/{patientId}/anc', AncOverview::class)
    ->name('workspaces-antenatal');

  Route::get('/{patientId}/anc/follow-up-assessment', FollowUpAssessment::class)
    ->name('workspaces-antenatal-followup');

  // TT Vaccinations inside Antenatal workspace
  Route::get('/{patientId}/anc/tetanus-vaccinations', TetanusVaccinations::class)
    ->name('workspaces-antenatal-tt-vaccinations');

  Route::get('/{patientId}/anc/deliveries', Deliveries::class)
    ->name('workspaces-antenatal-deliveries');

  Route::get('/{patientId}/anc/postnatal', Postnatal::class)
    ->name('workspaces-antenatal-postnatal');
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
  return redirect()->route('test'); // a route just be here for now, am coming
});



// Logout route
Route::post('/logout', function () {

  Auth::logout();
  request()->session()->invalidate();
  request()->session()->regenerateToken();
  return redirect()->route('test');
})->name('logout');
