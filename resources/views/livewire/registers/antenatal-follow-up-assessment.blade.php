<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antenatal Clinicalfollow up Assessment </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .bg-clinical-dark {
            background-color: #2c3e50;
            color: white;
        }

        .form-label {
            font-weight: 600;
            color: #444;
            font-size: 0.85rem;
            margin-bottom: 3px;
        }

        .section-header {
            border-left: 4px solid #0d6efd;
            padding-left: 10px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .intelligence-badge {
            font-size: 0.7rem;
            background: #e8f0fe;
            color: #1967d2;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4">
                <div class="card p-3 mb-4 border-top border-primary border-4">
                    <div class="section-header text-primary">Pelvic Assessment</div>
                    <div class="mb-3">
                        <label class="form-label">X-Ray Pelvimetry</label>
                        <div class="d-flex gap-3">
                            <div class="form-check"><input class="form-check-input" type="radio" name="pelv"
                                    id="y"><label class="form-check-label">Yes</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="pelv"
                                    id="n" checked><label class="form-check-label">No</label></div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-12"><label class="form-label">Inlet</label><input type="text"
                                class="form-control form-control-sm" placeholder="Promontory reached?"></div>
                        <div class="col-12"><label class="form-label">Cavity</label><input type="text"
                                class="form-control form-control-sm" placeholder="Sacrum Straight?"></div>
                        <div class="col-12"><label class="form-label">Outlet</label><input type="text"
                                class="form-control form-control-sm" placeholder="Sub-pubic Arch"></div>
                    </div>
                </div>

                <div class="card p-3 border-top border-info border-4">
                    <div class="section-header text-info">Initial Laboratory</div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Hb/Genotype</label><input type="text"
                                class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label">Rhesus</label><input type="text"
                                class="form-control form-control-sm"></div>
                        <div class="col-12"><label class="form-label">Kahn (VDRL)</label><input type="text"
                                class="form-control form-control-sm"></div>
                        <div class="col-12"><label class="form-label">Antimalarials & Therapy</label>
                            <textarea class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-clinical-dark d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Follow-up Assessment Entry</h5>
                        <span class="badge bg-primary">Visit Log</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Visit Date</label>
                                <input type="date" class="form-control" value="2026-01-31">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">B.P. <span
                                        class="intelligence-badge">Auto-Alert</span></label>
                                <input type="text" class="form-control" id="bpInput" placeholder="120/80"
                                    oninput="checkBP(this.value)">
                                <small id="bpAlert" class="text-danger fw-bold" style="display:none;">High BP! Check
                                    for Preeclampsia.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">P.C.V (%) <span
                                        class="intelligence-badge">Trend</span></label>
                                <input type="number" class="form-control" id="pcvInput"
                                    oninput="checkPCV(this.value)">
                                <small id="pcvAlert" class="text-warning fw-bold" style="display:none;">Anemic
                                    Range.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control">
                            </div>

                            <hr>

                            <div class="col-md-4">
                                <label class="form-label">Height of Fundus (cm)</label>
                                <input type="number" class="form-control" placeholder="e.g. 32">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Presentation & Position</label>
                                <select class="form-select">
                                    <option>--Select--</option>
                                    <option>Cephalic</option>
                                    <option>Breech</option>
                                    <option>Transverse</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Relation to Brim</label>
                                <input type="text" class="form-control" placeholder="e.g. 5/5 Palpable">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Foetal Heart Rate (bpm)</label>
                                <input type="number" class="form-control" id="fhrInput"
                                    oninput="checkFHR(this.value)">
                                <small id="fhrAlert" class="text-danger fw-bold" style="display:none;">Fetal
                                    Distress Alert!</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Urine (Alb/Sug)</label>
                                <input type="text" class="form-control" placeholder="Trace / Nil">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Oedema</label>
                                <select class="form-select" id="oedemaSelect" onchange="checkOedema(this.value)">
                                    <option value="none">None</option>
                                    <option value="+">+</option>
                                    <option value="++">++</option>
                                    <option value="+++">+++</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Clinical Remarks</label>
                                <textarea class="form-control" rows="2" id="remarksField"></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-danger fw-bold">Special Delivery Instructions</label>
                                <textarea class="form-control border-danger" rows="2" placeholder="Forecast for delivery..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Next Return Date <span
                                        class="intelligence-badge">Auto-Calc</span></label>
                                <input type="date" class="form-control" id="nextVisit">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button class="btn btn-secondary me-2">Print Card</button>
                        <button class="btn btn-primary px-5">Save Assessment</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // INTELLIGENCE LOGIC EXAMPLES:

        // 1. BP Warning (Systolic > 140 or Diastolic > 90)
        function checkBP(val) {
            const parts = val.split('/');
            if (parts.length === 2) {
                if (parseInt(parts[0]) >= 140 || parseInt(parts[1]) >= 90) {
                    document.getElementById('bpAlert').style.display = 'block';
                    document.getElementById('remarksField').value += "High BP noted. ";
                } else {
                    document.getElementById('bpAlert').style.display = 'none';
                }
            }
        }

        // 2. PCV / Anemia Alert
        function checkPCV(val) {
            if (val < 30 && val > 0) {
                document.getElementById('pcvAlert').style.display = 'block';
            } else {
                document.getElementById('pcvAlert').style.display = 'none';
            }
        }

        // 3. Fetal Distress (Normal is 110-160)
        function checkFHR(val) {
            if ((val < 110 || val > 160) && val > 0) {
                document.getElementById('fhrAlert').style.display = 'block';
            } else {
                document.getElementById('fhrAlert').style.display = 'none';
            }
        }

        // 4. Oedema Correlation
        function checkOedema(val) {
            if (val === '++' || val === '+++') {
                alert("Significant Oedema detected. Please check BP and Urine Protein immediately.");
            }
        }
    </script>

</body>

</html>
