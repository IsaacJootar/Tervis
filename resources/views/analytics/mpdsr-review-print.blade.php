@php
    $metrics = (array) ($payload['metrics'] ?? []);
    $issues = (array) ($payload['surveillance_issues'] ?? []);
    $facilityRows = (array) ($payload['deaths_by_facility'] ?? []);
    $maternalRows = (array) ($payload['maternal_deaths'] ?? []);
    $perinatalRows = (array) ($payload['perinatal_deaths'] ?? []);
@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $payload['title'] ?? 'MPDSR Review Sheet' }}</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --paper: #f8fafc;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            color: var(--ink);
            background: #fff;
        }

        .toolbar {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #111827;
            background: #111827;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
        }

        .btn.light {
            background: #fff;
            color: #111827;
        }

        .sheet {
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .sheet-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            background: var(--paper);
        }

        .sheet-header h2 {
            margin: 0 0 4px 0;
            font-size: 20px;
        }

        .sheet-header p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--line);
            font-size: 12px;
        }

        .meta-grid .item {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px;
        }

        .meta-grid .item strong {
            display: block;
            margin-bottom: 4px;
        }

        .section {
            padding: 12px 16px;
            border-bottom: 1px solid var(--line);
        }

        .section:last-child {
            border-bottom: 0;
        }

        .section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
        }

        .kpi {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px;
            background: #fff;
        }

        .kpi .label {
            font-size: 11px;
            color: var(--muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .kpi .value {
            font-size: 18px;
            font-weight: 700;
        }

        .table-wrap {
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        thead th {
            background: #111827;
            color: #fff;
            border: 1px solid #111827;
            text-align: left;
            padding: 6px;
            white-space: nowrap;
        }

        tbody td {
            border: 1px solid var(--line);
            padding: 6px;
            vertical-align: top;
        }

        tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .empty {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 8px;
            padding: 12px;
            font-size: 12px;
        }

        .review-lines {
            min-height: 80px;
            border: 1px dashed var(--line);
            border-radius: 8px;
            background: #fff;
        }

        .signoff {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 10px;
            font-size: 12px;
        }

        .signoff .line {
            border-bottom: 1px solid #111827;
            padding-top: 16px;
            margin-bottom: 4px;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            body {
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                border: 0;
                border-radius: 0;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <a href="{{ route('mpdsr-report-dashboard') }}" class="btn light">Back to MPDSR Dashboard</a>
        <button type="button" class="btn" onclick="window.print()">Print Review Sheet</button>
    </div>

    @if (empty($payload))
        <div class="empty">No MPDSR review data found. Go back, refresh filters, then click Open Printable Review Sheet again.</div>
    @else
        <div class="sheet">
            <div class="sheet-header">
                <h2>{{ $payload['title'] ?? 'MPDSR Review Sheet' }}</h2>
                <p>{{ $payload['subtitle'] ?? 'Maternal and Perinatal Death Surveillance and Response' }}</p>
            </div>

            <div class="meta-grid">
                <div class="item">
                    <strong>Scope</strong>
                    <span>{{ $payload['scope_label'] ?? '-' }}</span>
                </div>
                <div class="item">
                    <strong>Date Window</strong>
                    <span>{{ $payload['date_from'] ?? '-' }} to {{ $payload['date_to'] ?? '-' }}</span>
                </div>
                <div class="item">
                    <strong>Death Type Filter</strong>
                    <span>{{ ucfirst((string) ($payload['death_type'] ?? 'all')) }}</span>
                </div>
                <div class="item">
                    <strong>Generated</strong>
                    <span>{{ $payload['generated_at'] ?? '-' }}</span>
                </div>
                <div class="item">
                    <strong>Generated By</strong>
                    <span>{{ $payload['generated_by'] ?? '-' }}</span>
                </div>
                <div class="item">
                    <strong>Role</strong>
                    <span>{{ $payload['generated_by_role'] ?? '-' }}</span>
                </div>
            </div>

            <div class="section">
                <h3>Summary Metrics</h3>
                <div class="kpi-grid">
                    <div class="kpi">
                        <div class="label">Maternal Deaths</div>
                        <div class="value">{{ $metrics['total_maternal_deaths'] ?? 0 }}</div>
                    </div>
                    <div class="kpi">
                        <div class="label">Perinatal Deaths</div>
                        <div class="value">{{ $metrics['total_perinatal_deaths'] ?? 0 }}</div>
                    </div>
                    <div class="kpi">
                        <div class="label">Stillbirths</div>
                        <div class="value">{{ $metrics['total_stillbirths'] ?? 0 }}</div>
                    </div>
                    <div class="kpi">
                        <div class="label">Neonatal Deaths</div>
                        <div class="value">{{ $metrics['total_neonatal_deaths'] ?? 0 }}</div>
                    </div>
                    <div class="kpi">
                        <div class="label">Total Deaths</div>
                        <div class="value">{{ $metrics['total_deaths'] ?? 0 }}</div>
                    </div>
                    <div class="kpi">
                        <div class="label">MMR (per 100,000)</div>
                        <div class="value">{{ $metrics['maternal_mortality_ratio'] ?? 0 }}</div>
                    </div>
                    <div class="kpi">
                        <div class="label">PMR (per 1,000)</div>
                        <div class="value">{{ $metrics['perinatal_mortality_rate'] ?? 0 }}</div>
                    </div>
                    <div class="kpi">
                        <div class="label">Review Coverage %</div>
                        <div class="value">{{ $metrics['review_coverage_rate'] ?? 0 }}</div>
                    </div>
                    <div class="kpi">
                        <div class="label">Critical Issues</div>
                        <div class="value">{{ $metrics['critical_issues_count'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Surveillance Issues for Review</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Case Type</th>
                                <th>Patient</th>
                                <th>DIN</th>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Issue</th>
                                <th>Severity</th>
                                <th>Recommended Action</th>
                                <th>Reviewed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($issues as $issue)
                                <tr>
                                    <td>{{ $issue['case_type'] ?? '-' }}</td>
                                    <td>{{ $issue['patient_name'] ?? '-' }}</td>
                                    <td>{{ $issue['din'] ?? '-' }}</td>
                                    <td>{{ $issue['facility'] ?? '-' }}</td>
                                    <td>{{ $issue['death_date'] ?? '-' }}</td>
                                    <td>{{ $issue['issue'] ?? '-' }}</td>
                                    <td>{{ $issue['severity'] ?? '-' }}</td>
                                    <td>{{ $issue['recommended_action'] ?? '-' }}</td>
                                    <td style="text-align:center;">[ ]</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">No surveillance issues in selected filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <h3>Deaths by Facility</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Facility</th>
                                <th>LGA</th>
                                <th>Maternal</th>
                                <th>Perinatal</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($facilityRows as $row)
                                <tr>
                                    <td>{{ $row['facility_name'] ?? '-' }}</td>
                                    <td>{{ $row['lga'] ?? '-' }}</td>
                                    <td>{{ $row['maternal_deaths'] ?? 0 }}</td>
                                    <td>{{ $row['perinatal_deaths'] ?? 0 }}</td>
                                    <td>{{ $row['total_deaths'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No facility summary data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <h3>Maternal Death Cases</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>DIN</th>
                                <th>Age</th>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Probable Cause</th>
                                <th>Contributing Factors</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($maternalRows as $row)
                                <tr>
                                    <td>{{ $row['patient_name'] ?? '-' }}</td>
                                    <td>{{ $row['patient_din'] ?? '-' }}</td>
                                    <td>{{ $row['age'] ?? '-' }}</td>
                                    <td>{{ $row['facility_name'] ?? '-' }}</td>
                                    <td>{{ $row['death_date'] ?? '-' }}</td>
                                    <td>{{ $row['probable_cause'] ?? '-' }}</td>
                                    <td>{{ implode('; ', (array) ($row['contributing_factors'] ?? [])) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">No maternal death cases in selected filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <h3>Perinatal Death Cases</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Mother</th>
                                <th>Mother DIN</th>
                                <th>Age</th>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Weight (kg)</th>
                                <th>Gest. Age</th>
                                <th>Probable Cause</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($perinatalRows as $row)
                                <tr>
                                    <td>{{ $row['mother_name'] ?? '-' }}</td>
                                    <td>{{ $row['mother_din'] ?? '-' }}</td>
                                    <td>{{ $row['mother_age'] ?? '-' }}</td>
                                    <td>{{ $row['facility_name'] ?? '-' }}</td>
                                    <td>{{ $row['death_date'] ?? '-' }}</td>
                                    <td>{{ $row['death_type'] ?? '-' }}</td>
                                    <td>{{ $row['baby_weight'] ?? '-' }}</td>
                                    <td>{{ $row['gestational_age'] ?? '-' }}</td>
                                    <td>{{ $row['probable_cause'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">No perinatal death cases in selected filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <h3>Review Notes</h3>
                <div class="review-lines"></div>
                <div class="signoff">
                    <div>
                        <div class="line"></div>
                        <div>Reviewed By</div>
                    </div>
                    <div>
                        <div class="line"></div>
                        <div>Role</div>
                    </div>
                    <div>
                        <div class="line"></div>
                        <div>Date</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</body>

</html>
