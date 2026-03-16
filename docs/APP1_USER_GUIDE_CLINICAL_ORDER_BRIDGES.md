# APP1 User Guide: Clinical Order Bridges (Doctor, Lab, Pharmacy)

Last updated: 2026-03-17

## 1) Purpose

This guide explains how pending clinical orders move across modules and how completion is recorded.

- Patient-level modules:
  - Doctor Assessment: `/workspaces/{patientId}/assessments`
  - Laboratory: `/workspaces/{patientId}/laboratory`
  - Prescriptions: `/workspaces/{patientId}/prescriptions`
- Facility-level module:
  - Pharmacy Operations: `/core/pharmacy-operations`

## 2) Doctor -> Laboratory Bridge

### Step A: Create pending lab requests
1. Open `Doctor Assessment`.
2. Enter one or more test orders.
3. Save assessment.
4. System writes `lab_test_orders` with `status = pending`.

### Step B: Complete pending requests in Laboratory
1. Open patient `Laboratory` module.
2. In `Pending Requested Tests`, check the tests being processed.
3. Enter/save laboratory result record.
4. System:
   - saves `lab_tests` record,
   - marks selected `lab_test_orders` as `completed`,
   - links each completed order to the lab result using `completed_lab_test_id`.

Important:
- If pending lab requests exist, selection is required before save/update.
- All records are facility-scoped and patient-scoped.

## 3) Doctor -> Prescriptions -> Dispensing Bridge

### Step A: Create pending prescriptions
1. Open `Doctor Assessment`.
2. Enter one or more drug orders.
3. Save assessment.
4. System writes `prescriptions` with `status = pending`.

### Step B: Checkout in Prescriptions
1. Open patient `Prescriptions` module.
2. Select pending prescriptions being fulfilled.
3. Add drugs to cart and set quantities.
4. Enter charge amount and submit checkout.

System effects:
- Creates `drug_dispense_lines` for each cart line.
- Marks matched pending `prescriptions` as `dispensed`.
- Deducts stock using FIFO from available `drug_stock_batches`.
- Writes stock ledger entries to `drug_stock_movements`.
- Adds billing line to patient invoice.

Important:
- If pending prescriptions exist, selection is required before checkout.
- Checkout fails with clear error if stock is insufficient.

## 4) Pharmacy Operations (Facility Admin)

Use `/core/pharmacy-operations` for stock control:

1. `Step 1: Stock In`: add new batches (with qty, expiry, supplier). This is where real stock quantity is added.
2. `Step 2 (Optional): Stock Adjustment`: add/deduct with reason for corrections.
3. `Inventory Overview`: monitor available, low, out-of-stock, expired balance.
4. `Stock Batches`: batch-level balance audit.
5. `Stock Movement Log`: all stock-in/issue/adjustment events.

Important:
- `Reorder Level` is only a warning threshold.
- Saving reorder level does not increase stock.

## 5) Usage Order (Recommended Daily)

1. Doctor records assessments with orders.
2. Lab team completes pending tests in Laboratory.
3. Pharmacy team dispenses pending prescriptions in Prescriptions.
4. Facility admin monitors stock health in Pharmacy Operations.

## 6) Troubleshooting

- Pending tests not clearing:
  - Ensure checkboxes are selected before lab save.
  - Confirm patient/facility context is correct.
- Pending prescriptions not clearing:
  - Ensure pending checkboxes are selected before checkout.
- Checkout stock error:
  - Add stock first in Pharmacy Operations (`Stock In`) or adjust stock.

## 7) Data Scope Rules

1. Patient identity can be recognized across scope.
2. Clinical activity records stay facility-scoped.
3. Pending/completion transitions are always constrained by both `patient_id` and `facility_id`.
