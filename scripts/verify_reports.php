<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Livewire\Core\FacilityReports;
use App\Models\Facility;

$facilityId = Facility::query()->value('id');
if (!$facilityId) {
    fwrite(STDERR, "No facility found; cannot run report verification." . PHP_EOL);
    exit(1);
}

$component = app(FacilityReports::class);
$component->scopeInfo = [
    'scope_type' => 'facility',
    'facility_ids' => [(int) $facilityId],
];
$component->selectedFacilityId = (int) $facilityId;
$component->date_from = now()->startOfMonth()->format('Y-m-d');
$component->date_to = now()->format('Y-m-d');

$ref = new ReflectionClass($component);
$run = $ref->getMethod('runReport');
$run->setAccessible(true);

$checks = [
    'child_immunization_register' => ['Visit Date', 'Child Name', 'HepB0', 'OPV0', 'BCG', 'Comments'],
    'daily_anc_register' => ['S/N', 'Date', 'Patient Name', 'Card Number', 'Age 10-14', 'Outcome RO'],
    'daily_family_planning_register' => ['S/N', 'Date', 'DIN', 'Patient Name', 'Client Card No', 'Method Selected'],
    'immunization_summary_register' => ['S/N', 'Ward', 'Health Facility', 'Type Public', 'Type Private', 'PCV2 24m+'],
    'immunization_summary_tally' => ['S/N', 'Facility', 'ROTA3 10-23w', 'TD1 P', 'TD5 NP', 'Comments'],
    'nutrition_growth_register' => ['S/N', 'Date', 'Patient Name', 'Card Number', 'Age (Months)', 'Outcome Non-Recovered'],
];

$overallPass = true;

foreach ($checks as $reportKey => $requiredLabels) {
    try {
        [$columns, $rows] = $run->invoke($component, $reportKey);
        $labels = array_map(fn($col) => $col['label'] ?? '', $columns);
        $missing = array_values(array_filter($requiredLabels, fn($label) => !in_array($label, $labels, true)));

        echo "REPORT: {$reportKey}" . PHP_EOL;
        echo "  columns=" . count($labels) . ", rows=" . count($rows) . PHP_EOL;
        if (count($missing) === 0) {
            echo "  status=PASS" . PHP_EOL;
        } else {
            $overallPass = false;
            echo "  status=FAIL missing=[" . implode(', ', $missing) . "]" . PHP_EOL;
        }
    } catch (Throwable $e) {
        $overallPass = false;
        echo "REPORT: {$reportKey}" . PHP_EOL;
        echo "  status=ERROR " . $e->getMessage() . PHP_EOL;
    }
}

exit($overallPass ? 0 : 2);

