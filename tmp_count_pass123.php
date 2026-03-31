<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$roles = [
  'Central Admin',
  'State Data Administrator',
  'State Administrator',
  'LGA Officer',
  'LGA Data Administrator',
  'LGA Administrator',
  'Facility Administrator',
  'Data Officer',
  'Verification Officer',
];
$total = 0;
$matched = 0;
foreach (App\Models\User::whereIn('role', $roles)->get() as $user) {
  $total++;
  if (Illuminate\Support\Facades\Hash::check('pass123', $user->password)) {
    $matched++;
  }
}
echo 'TOTAL=' . $total . PHP_EOL;
echo 'MATCHED=' . $matched . PHP_EOL;
