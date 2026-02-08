<div>
    @php
        use Illuminate\Support\Facades\Auth;
        use Carbon\Carbon;
    @endphp

    @section('title', 'Monthly NHMIS Report')

    <!-- Hero Card Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="hero-card">
                <div class="hero-content">
                    <div class="hero-text">
                        <h4 class="hero-title" style="color: white; font-size: 28px;">
                            <i class='bx bx-file-blank me-2'></i>
                            National HMIS Monthly Summary Report
                        </h4>
                        <p class="hero-subtitle mb-2" style="color: rgba(255,255,255,0.9);">
                            Generate official monthly reports - NHMIS VERSION 2019
                        </p>
                    </div>
                    <div class="hero-decoration">
                        <div class="floating-shape shape-1"></div>
                        <div class="floating-shape shape-2"></div>
                        <div class="floating-shape shape-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Generate Button -->
    <div class="row mb-4">
        @if (count($availableFacilities) > 0)
            <div class="col-md-4">
                <label class="form-label">
                    <i class="bx bx-buildings me-1"></i>
                    Select Facility
                </label>
                <select wire:model="selectedFacilityId" class="form-select">
                    <option value="">All Facilities (Aggregated)</option>
                    @foreach ($availableFacilities as $facility)
                        <option value="{{ $facility['id'] }}">
                            {{ $facility['name'] }} - {{ $facility['lga'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="col-md-3">
            <label class="form-label">
                <i class="bx bx-calendar me-1"></i>
                Month
            </label>
            <select wire:model="selectedMonth" class="form-select">
                @foreach (range(1, 12) as $month)
                    <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}">
                        {{ Carbon::create(null, $month, 1)->format('F') }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Year</label>
            <select wire:model="selectedYear" class="form-select">
                @foreach (range(Carbon::now()->year, Carbon::now()->year - 5) as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <button wire:click="generateReport" class="btn btn-primary w-100" wire:loading.attr="disabled">
                <span wire:loading.remove><i class="bx bx-file-blank me-1"></i>Generate Report</span>
                <span wire:loading><span class="spinner-border spinner-border-sm me-1"></span>Generating...</span>
            </button>
        </div>


    </div>
    <!-- Print Button -->
    <div class="row mb-3">
        <div class="col-12 text-end">
            <button onclick="window.print()" class="btn btn-success btn-lg no-print">
                <i class="bx bx-printer me-2"></i>
                Print / Save as PDF
            </button>
        </div>
    </div>
    @if ($reportGenerated)
        <!-- Facility Information Card -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">FACILITY INFORMATION</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <tr>
                            <td class="bg-light" style="width: 15%;"><strong>Health Facility</strong></td>
                            <td style="width: 35%;">{{ $facilityInfo['name'] }}</td>
                            <td class="bg-light" style="width: 15%;"><strong>Month</strong></td>
                            <td style="width: 35%;">{{ Carbon::create($selectedYear, $selectedMonth, 1)->format('F') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="bg-light"><strong>Political Ward</strong></td>
                            <td>{{ $facilityInfo['ward'] }}</td>
                            <td class="bg-light"><strong>Year</strong></td>
                            <td>{{ $selectedYear }}</td>
                        </tr>
                        <tr>
                            <td class="bg-light"><strong>LGA</strong></td>
                            <td>{{ $facilityInfo['lga'] }}</td>
                            <td class="bg-light"><strong>Public/Private</strong></td>
                            <td>{{ $facilityInfo['type'] }}</td>
                        </tr>
                        <tr>
                            <td class="bg-light"><strong>State</strong></td>
                            <td>{{ $facilityInfo['state'] }}</td>
                            <td class="bg-light"><strong>Number of Beds</strong></td>
                            <td>{{ $facilityInfo['beds'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>



        <!-- Section 1: Health Facility Attendance -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">1. HEALTH FACILITY ATTENDANCE</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" style="width: 5%;">#</th>
                                <th rowspan="2" style="width: 20%;">Description</th>
                                <th colspan="6" class="text-center">Male</th>
                                <th colspan="6" class="text-center">Female</th>
                                <th rowspan="2" class="text-center">Total</th>
                            </tr>
                            <tr>
                                <th class="text-center">0-28d</th>
                                <th class="text-center">29d-11m</th>
                                <th class="text-center">12-59m</th>
                                <th class="text-center">5-9y</th>
                                <th class="text-center">10-19y</th>
                                <th class="text-center">≥20y</th>
                                <th class="text-center">0-28d</th>
                                <th class="text-center">29d-11m</th>
                                <th class="text-center">12-59m</th>
                                <th class="text-center">5-9y</th>
                                <th class="text-center">10-19y</th>
                                <th class="text-center">≥20y</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td>General Attendance</td>
                                @foreach (['0-28d', '29d-11m', '12-59m', '5-9y', '10-19y', '20+'] as $group)
                                    <td class="text-center">
                                        {{ $reportData['attendance']["general_male_{$group}"] ?? 0 }}</td>
                                @endforeach
                                @foreach (['0-28d', '29d-11m', '12-59m', '5-9y', '10-19y', '20+'] as $group)
                                    <td class="text-center">
                                        {{ $reportData['attendance']["general_female_{$group}"] ?? 0 }}</td>
                                @endforeach
                                <td class="text-center"><strong>{{ $summaryStats['total_attendance'] ?? 0 }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">2</td>
                                <td>Outpatient Attendance</td>
                                @foreach (['0-28d', '29d-11m', '12-59m', '5-9y', '10-19y', '20+'] as $group)
                                    <td class="text-center">
                                        {{ $reportData['attendance']["outpatient_male_{$group}"] ?? 0 }}</td>
                                @endforeach
                                @foreach (['0-28d', '29d-11m', '12-59m', '5-9y', '10-19y', '20+'] as $group)
                                    <td class="text-center">
                                        {{ $reportData['attendance']["outpatient_female_{$group}"] ?? 0 }}</td>
                                @endforeach
                                <td class="text-center"><strong>{{ $summaryStats['total_attendance'] ?? 0 }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 2: Inpatient Care -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">2. INPATIENT CARE (IPC)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 25%;">Description</th>
                                <th colspan="6" class="text-center">Male</th>
                                <th colspan="6" class="text-center">Female</th>
                                <th class="text-center">Total</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th class="text-center">0-28d</th>
                                <th class="text-center">29d-11m</th>
                                <th class="text-center">12-59m</th>
                                <th class="text-center">5-9y</th>
                                <th class="text-center">10-19y</th>
                                <th class="text-center">≥20y</th>
                                <th class="text-center">0-28d</th>
                                <th class="text-center">29d-11m</th>
                                <th class="text-center">12-59m</th>
                                <th class="text-center">5-9y</th>
                                <th class="text-center">10-19y</th>
                                <th class="text-center">≥20y</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">3</td>
                                <td>Patients admitted</td>
                                <td class="text-center" colspan="12">
                                    {{ $reportData['inpatient']['total_admissions'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['inpatient']['total_admissions'] ?? 0 }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">4</td>
                                <td>Inpatient discharges</td>
                                <td class="text-center" colspan="12">
                                    {{ $reportData['inpatient']['total_discharges'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['inpatient']['total_discharges'] ?? 0 }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 3: Mortality -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">3. MORTALITY (DEATHS)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Description</th>
                                <th colspan="6" class="text-center">Male</th>
                                <th colspan="6" class="text-center">Female</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">5</td>
                                <td>Deaths among individuals</td>
                                <td class="text-center" colspan="12">
                                    {{ $reportData['mortality']['total_maternal_deaths'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['mortality']['total_maternal_deaths'] ?? 0 }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th colspan="2">Maternal Mortality</th>
                                <th class="text-center">10-19 yrs</th>
                                <th class="text-center">≥20 yrs</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">6</td>
                                <td colspan="2">Deaths of women related to pregnancy</td>
                                <td class="text-center">{{ $reportData['mortality']['maternal_deaths_10_19'] ?? 0 }}
                                </td>
                                <td class="text-center">{{ $reportData['mortality']['maternal_deaths_20+'] ?? 0 }}
                                </td>
                                <td class="text-center">
                                    <strong>{{ $reportData['mortality']['total_maternal_deaths'] ?? 0 }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Causes of Deaths</th>
                                <th class="text-center">PPH</th>
                                <th class="text-center">Sepsis</th>
                                <th class="text-center">Obstructed labour</th>
                                <th class="text-center">Abortion</th>
                                <th class="text-center">Malaria</th>
                                <th class="text-center">Anaemia</th>
                                <th class="text-center">HIV</th>
                                <th class="text-center">Other</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">7</td>
                                <td>Confirmed maternal deaths due to:</td>
                                @foreach (['pph', 'sepsis', 'obstructed', 'abortion', 'malaria', 'anaemia', 'hiv', 'other'] as $cause)
                                    <td class="text-center">
                                        {{ $reportData['mortality']['maternal_death_causes'][$cause] ?? 0 }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Neonatal Deaths</th>
                                <th class="text-center">Prematurity</th>
                                <th class="text-center">Neonatal Tetanus</th>
                                <th class="text-center">Congenital Malformation</th>
                                <th class="text-center">Other</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">8</td>
                                <td>Confirmed neonatal deaths due to:</td>
                                <td class="text-center">
                                    {{ $reportData['mortality']['neonatal_causes']['premature'] ?? 0 }}</td>
                                <td class="text-center">
                                    {{ $reportData['mortality']['neonatal_causes']['tetanus'] ?? 0 }}</td>
                                <td class="text-center">
                                    {{ $reportData['mortality']['neonatal_causes']['congenital'] ?? 0 }}</td>
                                <td class="text-center">
                                    {{ $reportData['mortality']['neonatal_causes']['other'] ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Under 5 Deaths</th>
                                <th class="text-center">Malaria</th>
                                <th class="text-center">Pneumonia</th>
                                <th class="text-center">Malnutrition</th>
                                <th class="text-center">Other</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">9</td>
                                <td>Confirmed under 5 deaths due to:</td>
                                <td class="text-center">
                                    {{ $reportData['mortality']['under5_deaths']['malaria'] ?? 0 }}</td>
                                <td class="text-center">
                                    {{ $reportData['mortality']['under5_deaths']['pneumonia'] ?? 0 }}</td>
                                <td class="text-center">
                                    {{ $reportData['mortality']['under5_deaths']['malnutrition'] ?? 0 }}</td>
                                <td class="text-center">{{ $reportData['mortality']['under5_deaths']['other'] ?? 0 }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 4: Maternal Health (Ante-Natal Care) -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">4. MATERNAL HEALTH (ANTE-NATAL CARE)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Description</th>
                                <th class="text-center">10-14 yrs</th>
                                <th class="text-center">15-19 yrs</th>
                                <th class="text-center">20-35 yrs</th>
                                <th class="text-center">35-49 yrs</th>
                                <th class="text-center">≥50 yrs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">10</td>
                                <td>Antenatal attendance by pregnant women</td>
                                @foreach (['10-14', '15-19', '20-35', '35-49', '50+'] as $group)
                                    <td class="text-center">
                                        {{ $reportData['maternal']["anc_attendance_{$group}"] ?? 0 }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="text-center">11</td>
                                <td>Antenatal attendance first visit</td>
                                <td class="text-center" colspan="2">
                                    {{ $reportData['maternal']['anc_first_before20wks'] ?? 0 }}<br><small>GA <
                                            20wks</small>
                                </td>
                                <td class="text-center" colspan="3">
                                    {{ $reportData['maternal']['anc_first_after20wks'] ?? 0 }}<br><small>GA ≥
                                        20wks</small></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="bg-light" style="width: 5%;"><strong>12</strong></td>
                                <td class="bg-light" style="width: 75%;"><strong>4th ANC visit</strong></td>
                                <td>{{ $reportData['maternal']['4th_anc_visit'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>13</strong></td>
                                <td class="bg-light"><strong>8th ANC visit</strong></td>
                                <td>{{ $reportData['maternal']['8th_anc_visit'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>26</strong></td>
                                <td class="bg-light"><strong>IPT1</strong></td>
                                <td>{{ $reportData['maternal']['ipt1'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>27</strong></td>
                                <td class="bg-light"><strong>IPT2</strong></td>
                                <td>{{ $reportData['maternal']['ipt2'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>28</strong></td>
                                <td class="bg-light"><strong>IPT3</strong></td>
                                <td>{{ $reportData['maternal']['ipt3'] ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 5: Maternal Health (Labour and Delivery) -->
        <div class="card mb-4">
            <div class="card-header text-white" style="background-color: #6f42c1;">
                <h6 class="mb-0">5. MATERNAL HEALTH (LABOUR AND DELIVERY)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="bg-light" style="width: 5%;"><strong>34</strong></td>
                                <td class="bg-light" style="width: 65%;"><strong>Decision in seeking care < 24
                                            hours</strong>
                                </td>
                                <td style="width: 30%;">{{ $reportData['maternal']['decision_care_24hrs'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>36</strong></td>
                                <td class="bg-light"><strong>Deliveries (SVD/Assisted/C-Section)</strong></td>
                                <td>{{ $reportData['maternal']['total_deliveries'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>37</strong></td>
                                <td class="bg-light"><strong>Preterm births</strong></td>
                                <td>{{ $reportData['maternal']['preterm_births'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>38</strong></td>
                                <td class="bg-light"><strong>Delivery complications</strong></td>
                                <td>{{ $reportData['maternal']['delivery_complications'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>40</strong></td>
                                <td class="bg-light"><strong>Partograph monitored</strong></td>
                                <td>{{ $reportData['maternal']['partograph_used'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>41</strong></td>
                                <td class="bg-light"><strong>Skilled birth attendants</strong></td>
                                <td>{{ $reportData['maternal']['skilled_attendant'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>42</strong></td>
                                <td class="bg-light"><strong>Uterotonics received</strong></td>
                                <td>{{ $reportData['maternal']['oxytocin_received'] ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 6: Newborn Health -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">6. NEWBORN HEALTH</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Description</th>
                                <th colspan="2" class="text-center">Male</th>
                                <th colspan="2" class="text-center">Female</th>
                                <th class="text-center">Total</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th class="text-center">&lt;2.5kg</th>
                                <th class="text-center">≥2.5kg</th>
                                <th class="text-center">&lt;2.5kg</th>
                                <th class="text-center">≥2.5kg</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">48</td>
                                <td>Live Births</td>
                                <td class="text-center">{{ $reportData['newborn']['live_births_m_low'] ?? 0 }}</td>
                                <td class="text-center">{{ $reportData['newborn']['live_births_m_normal'] ?? 0 }}</td>
                                <td class="text-center">{{ $reportData['newborn']['live_births_f_low'] ?? 0 }}</td>
                                <td class="text-center">{{ $reportData['newborn']['live_births_f_normal'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['newborn']['total_live_births'] ?? 0 }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Description</th>
                                <th class="text-center">Macerated (MSB)</th>
                                <th class="text-center">Fresh Still Births (FSB)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">50</td>
                                <td>Still Births</td>
                                <td class="text-center">{{ $reportData['newborn']['macerated_stillbirths'] ?? 0 }}
                                </td>
                                <td class="text-center">{{ $reportData['newborn']['fresh_stillbirths'] ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th>Immediate Newborn Care</th>
                                <th class="text-center">Male</th>
                                <th class="text-center">Female</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">51</td>
                                <td>Babies whose cords were clamped/cut after 1 minute of birth</td>
                                <td class="text-center" colspan="2">
                                    {{ $reportData['newborn']['cord_clamped'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['newborn']['cord_clamped'] ?? 0 }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">52</td>
                                <td>Babies for whom 4% Chlorhexidine (CHX) gel is applied to cord at birth</td>
                                <td class="text-center" colspan="2">
                                    {{ $reportData['newborn']['chx_gel_applied'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['newborn']['chx_gel_applied'] ?? 0 }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">53</td>
                                <td>Babies put to breast within 1 hour with skin-to-skin to keep warm</td>
                                <td class="text-center" colspan="2">
                                    {{ $reportData['newborn']['skin_to_skin'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['newborn']['skin_to_skin'] ?? 0 }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">54</td>
                                <td>Babies whose temperature were taken at 1 hour of birth</td>
                                <td class="text-center" colspan="2">
                                    {{ $reportData['newborn']['temp_taken'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['newborn']['temp_taken'] ?? 0 }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">55</td>
                                <td>Babies not breathing/ not crying at birth</td>
                                <td class="text-center" colspan="2">
                                    {{ $reportData['newborn']['not_breathing'] ?? 0 }}</td>
                                <td class="text-center">
                                    <strong>{{ $reportData['newborn']['not_breathing'] ?? 0 }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section 7: Immunization -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">7. IMMUNIZATION</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="bg-light" style="width: 5%;"><strong>63</strong></td>
                                <td class="bg-light" style="width: 75%;"><strong>TD vaccine (Pregnant women)</strong>
                                </td>
                                <td style="width: 20%;">{{ $reportData['immunization']['td_pregnant'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>TT1</strong></td>
                                <td class="bg-light"><strong>Tetanus Toxoid 1st Dose</strong></td>
                                <td>{{ $reportData['immunization']['tt1'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>TT2</strong></td>
                                <td class="bg-light"><strong>Tetanus Toxoid 2nd Dose</strong></td>
                                <td>{{ $reportData['immunization']['tt2'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>TT3</strong></td>
                                <td class="bg-light"><strong>Tetanus Toxoid 3rd Dose</strong></td>
                                <td>{{ $reportData['immunization']['tt3'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>TT4</strong></td>
                                <td class="bg-light"><strong>Tetanus Toxoid 4th Dose</strong></td>
                                <td>{{ $reportData['immunization']['tt4'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>TT5</strong></td>
                                <td class="bg-light"><strong>Tetanus Toxoid 5th Dose</strong></td>
                                <td>{{ $reportData['immunization']['tt5'] ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info m-2 mb-0">
                    <i class="bx bx-info-circle me-2"></i>
                    <small>Other immunizations (BCG, OPV, Penta, PCV, Measles, etc.) require dedicated immunization
                        records table</small>
                </div>
            </div>
        </div>

        <!-- Section 8: Child Health & Nutrition -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">8. CHILD HEALTH & NUTRITION</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="bg-light" style="width: 5%;"><strong>98</strong></td>
                                <td class="bg-light" style="width: 75%;"><strong>Birth registration < 1 year</strong>
                                </td>
                                <td style="width: 20%;">{{ $reportData['child_health']['birth_registrations'] ?? 0 }}
                                </td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>103</strong></td>
                                <td class="bg-light"><strong>Exclusive breastfeeding</strong></td>
                                <td>{{ $reportData['child_health']['exclusive_breastfeeding'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>105</strong></td>
                                <td class="bg-light"><strong>Vitamin A supplementation</strong></td>
                                <td>{{ $reportData['child_health']['vitamin_a'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>108</strong></td>
                                <td class="bg-light"><strong>SAM admissions</strong></td>
                                <td>{{ $reportData['child_health']['sam_admissions'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>110</strong></td>
                                <td class="bg-light"><strong>Diarrhoea cases < 5 years</strong>
                                </td>
                                <td>{{ $reportData['child_health']['diarrhoea_cases'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>112</strong></td>
                                <td class="bg-light"><strong>Pneumonia cases < 5 years</strong>
                                </td>
                                <td>{{ $reportData['child_health']['pneumonia_cases'] ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-warning m-2 mb-0">
                    <i class="bx bx-error me-2"></i>
                    <small>Complete child health metrics require dedicated child health module or clinical notes
                        text comprehension-will be back</small>
                </div>
            </div>
        </div>

        <!-- Section 9: Other Health Services -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">9. OTHER HEALTH SERVICES</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="bg-light" style="width: 5%;"><strong>115</strong></td>
                                <td class="bg-light" style="width: 75%;"><strong>Family Planning counselling</strong>
                                </td>
                                <td style="width: 20%;">{{ $reportData['other_services']['fp_counselled'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>116</strong></td>
                                <td class="bg-light"><strong>New FP acceptors</strong></td>
                                <td>{{ $reportData['other_services']['fp_acceptors'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>117</strong></td>
                                <td class="bg-light"><strong>Modern contraception</strong></td>
                                <td>{{ $reportData['other_services']['modern_contraception'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>145</strong></td>
                                <td class="bg-light"><strong>Persons with fever</strong></td>
                                <td>{{ $reportData['other_services']['malaria_cases'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>147</strong></td>
                                <td class="bg-light"><strong>Malaria positive by RDT</strong></td>
                                <td>{{ $reportData['other_services']['malaria_positive'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>159</strong></td>
                                <td class="bg-light"><strong>TB screening</strong></td>
                                <td>{{ $reportData['other_services']['tb_screening'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>162</strong></td>
                                <td class="bg-light"><strong>Hepatitis B tested</strong></td>
                                <td>{{ $reportData['other_services']['hepb_tested'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>166</strong></td>
                                <td class="bg-light"><strong>Hepatitis C tested</strong></td>
                                <td>{{ $reportData['other_services']['hepc_tested'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="bg-light"><strong>170</strong></td>
                                <td class="bg-light"><strong>GBV cases seen</strong></td>
                                <td>{{ $reportData['other_services']['gbv_cases'] ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info m-2 mb-0">
                    <i class="bx bx-info-circle me-2"></i>
                    <small>Disease tracking and detailed service metrics require clinical notes coding or disease
                        registry</small>
                </div>
            </div>
        </div>

        <!-- Authentication Section -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #6c757d; color: white;">
                <h6 class="mb-0">AUTHENTICATION</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <tr>
                            <td class="bg-light" style="width: 20%;"><strong>Completed by:</strong></td>
                            <td style="width: 30%;">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                            </td>
                            <td class="bg-light" style="width: 20%;"><strong>Designation:</strong></td>
                            <td style="width: 30%;">{{ auth()->user()->designation ?? auth()->user()->role }}</td>
                        </tr>
                        <tr>
                            <td class="bg-light"><strong>Data Timestamp:</strong></td>
                            <td colspan="3" style="height: 50px;">
                                <p class="mb-2"><strong>Generated:</strong>
                                    {{ Carbon::now()->format('M d, Y h:i A') }}</p>
                            </td>
                        </tr>

                    </table>
                </div>
            </div>
        </div>

        <!-- Information Notice -->
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bx bx-info-circle me-2"></i>About This Report
                </h6>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Report Period:</strong>
                            {{ Carbon::create($selectedYear, $selectedMonth, 1)->format('F Y') }}</p>
                        <p class="mb-2"><strong>Facility/Scope:</strong> {{ $facilityInfo['name'] }}</p>
                        <p class="mb-2"><strong>Generated:</strong> {{ Carbon::now()->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Data Sources:</strong> Antenatal records, Deliveries, Postnatal care,
                            Immunization, Clinical notes</p>
                        <p class="mb-2"><strong>System:</strong> MaternalRecordPro v1.0</p>
                        <p class="mb-2"><strong>Format:</strong> NHMIS </p>
                    </div>
                </div>
                <div class="alert alert-warning mt-3 mb-0" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="bx bx-shield-quarter me-2"></i>
                        <strong>Data Completeness Notice</strong>
                    </h6>
                    <small>
                        <p class="mb-1">This report is auto-generated from available data in the system. Some NHMIS
                            fields require additional data collection:</p>
                        <ul class="mb-0">
                            <li>Full child immunization schedule (BCG, OPV, Penta, PCV, Measles) - requires
                                immunization_records table-willbe back</li>
                            <li>Child health & nutrition metrics - requires child health module</li>
                            <li>Disease surveillance (Malaria, TB, NCDs) - requires clinical coding or disease registry
                            </li>
                            <li>Family planning methods detail - requires FP module</li>
                        </ul>
                    </small>
                </div>
            </div>
        </div>
</div>
</div>
@else
<!-- Empty State -->
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bx bx-file-blank bx-lg text-muted mb-3" style="font-size: 72px;"></i>
        <h5 class="text-muted">Select filters and click "Generate Report"</h5>
        <p class="text-muted">Choose facility (if applicable), month, and year, then click the Generate Report
            button above.</p>
    </div>
</div>
@endif

<style>
    .hero-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        min-height: 180px;
    }

    .hero-content {
        position: relative;
        z-index: 2;
        padding: 2rem;
    }

    .hero-decoration {
        position: absolute;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 1;
    }

    .floating-shape {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: float 6s ease-in-out infinite;
    }

    .floating-shape.shape-1 {
        width: 80px;
        height: 80px;
        top: 20%;
        right: 10%;
    }

    .floating-shape.shape-2 {
        width: 60px;
        height: 60px;
        top: 60%;
        right: 20%;
        animation-delay: 2s;
    }

    .floating-shape.shape-3 {
        width: 40px;
        height: 40px;
        top: 40%;
        right: 5%;
        animation-delay: 4s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(180deg);
        }
    }

    .card {
        box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
        border: 1px solid rgba(67, 89, 113, 0.1);
    }

    .table-sm td,
    .table-sm th {
        padding: 0.4rem;
        font-size: 0.875rem;
    }

    /* Print Styles */
    @media print {

        .hero-card,
        .no-print,
        nav,
        footer,
        .menu,
        .sidebar {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #000 !important;
            page-break-inside: avoid;
            margin-bottom: 10px !important;
        }

        .card-header {
            background: #f8f9fa !important;
            color: #000 !important;
            border-bottom: 2px solid #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .table {
            font-size: 9px !important;
        }

        .table th,
        .table td {
            padding: 3px !important;
            border: 1px solid #000 !important;
        }

        .alert {
            display: none !important;
        }

        @page {
            margin: 0.5cm;
            size: A4;
        }
    }
</style>
</div>
