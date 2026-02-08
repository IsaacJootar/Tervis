@php
    use Carbon\Carbon;
@endphp

@section('title', 'Workspace Dashboard')

<div>
    {{-- ============================================ --}}
    {{-- ACCESS DENIED VIEW --}}
    {{-- ============================================ --}}
    @if (!$hasAccess)
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bx bx-error-circle text-danger" style="font-size: 5rem;"></i>
                        </div>
                        <h3 class="text-danger mb-3">Access Denied</h3>
                        <p class="text-muted mb-4">{{ $accessError }}</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('patient-workspace') }}" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i>Go to Patient Workspace
                            </a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- ============================================ --}}
        {{-- MAIN CONTENT (Access Granted) --}}
        {{-- ============================================ --}}

        {{-- Hero Card Header --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="hero-card">
                    {{-- Floating Decorations --}}
                    <div class="hero-decoration">
                        <span class="floating-shape shape-1"></span>
                        <span class="floating-shape shape-2"></span>
                        <span class="floating-shape shape-3"></span>
                    </div>

                    {{-- Hero Content --}}
                    <div class="hero-content">
                        <div class="hero-text">
                            <h4 class="hero-title mb-1" style="color: white; font-size: 22px;">
                                <i class='bx bx-grid-alt me-2'></i>
                                Workspace Dashboard
                            </h4>

                            <p class="mb-2" style="color: rgba(255, 255, 255, 0.85); font-size: 0.875rem;">
                                <i class="bx bx-time me-1"></i>
                                {{ Carbon::now('Africa/Lagos')->format('l, F j, Y') }} |
                                Checked in at {{ $activation_time }}
                            </p>

                            <div class="hero-stats">
                                <span class="hero-stat">
                                    <i class="bx bx-building"></i>
                                    {{ $facility_name ?? 'N/A' }}
                                </span>
                                <span class="hero-stat">
                                    <i class="bx bx-map-pin"></i>
                                    {{ $facility_lga ?? 'N/A' }}, {{ $facility_state ?? 'N/A' }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Patient Info Card --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="patient-header d-flex align-items-center">
                            {{-- Avatar --}}
                            <div class="flex-shrink-0 me-4">
                                <div class="avatar avatar-xl">
                                    <span
                                        class="avatar-initial rounded-circle bg-{{ $patient_gender === 'Female' ? 'danger' : 'primary' }}"
                                        style="width: 80px; height: 80px; font-size: 2rem;">
                                        {{ strtoupper(substr($first_name, 0, 1)) }}{{ strtoupper(substr($last_name, 0, 1)) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Patient Details --}}
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                    <div>
                                        <h4 class="mb-1">{{ $first_name }} {{ $middle_name }}
                                            {{ $last_name }}</h4>
                                        <p class="mb-2">
                                            <span class="badge bg-primary me-2">DIN: {{ $patient_din }}</span>
                                            <span
                                                class="badge bg-{{ $patient_gender === 'Female' ? 'danger' : 'info' }} me-2">{{ $patient_gender }}</span>
                                            <span class="badge bg-secondary">{{ $patient_age }} years old</span>
                                        </p>
                                    </div>
                                    <div class="patient-actions">
                                        <button wire:click="backToPatientWorkspace" type="button"
                                            class="btn btn-primary px-4 py-2 d-inline-flex align-items-center workspace-back-btn">
                                            <i class="bx bx-arrow-back me-2"></i>
                                            Back to Patient Workspace
                                        </button>
                                    </div>
                                </div>

                                <div class="row mt-2 g-3">
                                    <div class="col-6 col-lg-2">
                                        <small class="text-muted">Phone:</small>
                                        <p class="mb-0 fw-medium">{{ $patient_phone ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-6 col-lg-2">
                                        <small class="text-muted">Date of Birth:</small>
                                        <p class="mb-0 fw-medium">{{ $patient_dob ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-6 col-lg-2">
                                        <small class="text-muted">Blood Group:</small>
                                        <p class="mb-0 fw-medium">{{ $patient_blood_group ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-6 col-lg-2">
                                        <small class="text-muted">Genotype:</small>
                                        <span class="badge bg-label-secondary">
                                            {{ $patient_genotype ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="col-6 col-lg-2">
                                        <small class="text-muted">NHIS Status:</small>
                                        <span class="badge bg-label-{{ $patient_nhis_status ? 'success' : 'warning' }}">
                                            {{ $patient_nhis_status ? 'NHIS Subscriber' : 'NHIS Non-Subscriber' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Registration Badges --}}
                        <hr class="my-3">
                        <div class="d-flex flex-wrap gap-2">
                            <span
                                class="badge bg-label-{{ $hasAntenatalRegistration ? 'success' : 'secondary' }} px-3 py-2">
                                <i class="bx bx-{{ $hasAntenatalRegistration ? 'check' : 'x' }} me-1"></i>
                                ANC {{ $hasAntenatalRegistration ? 'Registered' : 'Not Registered' }}
                            </span>
                            <span
                                class="badge bg-label-{{ $hasFamilyPlanningRegistration ? 'success' : 'secondary' }} px-3 py-2">
                                <i class="bx bx-{{ $hasFamilyPlanningRegistration ? 'check' : 'x' }} me-1"></i>
                                FP {{ $hasFamilyPlanningRegistration ? 'Registered' : 'Not Registered' }}
                            </span>
                            <span class="badge bg-label-{{ $hasLinkedChildren ? 'success' : 'secondary' }} px-3 py-2">
                                <i class="bx bx-{{ $hasLinkedChildren ? 'check' : 'x' }} me-1"></i>
                                {{ $hasLinkedChildren ? count($linkedChildren) . ' Linked Children' : 'No Linked Children' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Workspace Cards Grid --}}
        <div class="row">
            {{-- Card 1: Attendance & Verifications --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'attendance',
                'title' => 'Attendance & Verifications',
                'icon' => 'bx-calendar-check',
                'color' => 'success',
                'description' => 'Daily check-in and verification records',
            ])

            {{-- Card 2: Doctor Assessments --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'assessments',
                'title' => 'Doctor Assessments',
                'icon' => 'bx-stethoscope',
                'color' => 'info',
                'description' => 'Clinical assessments and diagnoses',
            ])

            {{-- Card 3: ANC Workspace --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'anc',
                'title' => 'ANC Workspace',
                'icon' => 'bx-health',
                'color' => 'danger',
                'description' => 'Tetanus, deliveries, postnatal, follow-up assessment',
            ])

            {{-- Card 6: Immunizations --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'immunizations',
                'title' => 'Immunizations',
                'icon' => 'bx-shield-plus',
                'color' => 'warning',
                'description' => 'Child immunization and vaccination records',
            ])

            {{-- Card 7: Nutrition --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'nutrition',
                'title' => 'Nutrition',
                'icon' => 'bx-food-menu',
                'color' => 'warning',
                'description' => 'Nutritional assessments (SAM/MAM tracking)',
            ])

            {{-- Card 8: Tests & Laboratory --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'laboratory',
                'title' => 'Tests & Laboratory',
                'icon' => 'bx-test-tube',
                'color' => 'info',
                'description' => 'Lab tests and diagnostic results',
            ])

            {{-- Card 9: Prescriptions & Drugs --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'prescriptions',
                'title' => 'Prescriptions & Drugs',
                'icon' => 'bx-capsule',
                'color' => 'primary',
                'description' => 'Medication prescriptions and drug history',
            ])

            {{-- Card 10: Invoices & Payments --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'invoices',
                'title' => 'Invoices & Payments',
                'icon' => 'bx-receipt',
                'color' => 'secondary',
                'description' => 'Billing, invoices, and payment records',
            ])

            {{-- Card 11: Appointments --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'appointments',
                'title' => 'Appointments',
                'icon' => 'bx-calendar',
                'color' => 'primary',
                'description' => 'Scheduled appointments and bookings',
            ])

            {{-- Card 12: Referrals --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'referrals',
                'title' => 'Referrals',
                'icon' => 'bx-transfer',
                'color' => 'dark',
                'description' => 'Referrals to other facilities or specialists',
            ])

            {{-- Card 13: Reminders & Alerts --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'reminders',
                'title' => 'Reminders & Alerts',
                'icon' => 'bx-bell',
                'color' => 'warning',
                'description' => 'Follow-up reminders and notifications',
            ])

            {{-- Card 14: Family Planning --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'family_planning',
                'title' => 'Family Planning',
                'icon' => 'bx-heart-circle',
                'color' => 'danger',
                'description' => 'Family planning methods and counseling',
            ])

            {{-- Card 15: Visits --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'visits',
                'title' => 'Visits',
                'icon' => 'bx-history',
                'color' => 'secondary',
                'description' => 'Complete visit history and records',
            ])

            {{-- Card 16: Activities --}}
            @include('livewire.partials.workspace-card', [
                'key' => 'activities',
                'title' => 'Activities',
                'icon' => 'bx-time-five',
                'color' => 'dark',
                'description' => 'Patient activity timeline and logs',
            ])
        </div>

    @endif
</div>

@once
    <style>
        .patient-header {
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .patient-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .patient-actions {
                width: 100%;
            }

            .workspace-back-btn {
                position: fixed;
                right: 16px;
                bottom: 16px;
                z-index: 1050;
                border-radius: 999px;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.25);
            }
        }

        @media (min-width: 769px) {
            .workspace-back-btn {
                display: none;
            }
        }
    </style>
@endonce
