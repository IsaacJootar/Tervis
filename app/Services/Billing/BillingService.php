<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\PatientPayment;
use App\Models\PaymentAllocation;
use Carbon\Carbon;

class BillingService
{
  public function findOrCreateOpenInvoice(
    array $context,
    string $invoiceDate,
    ?string $notes = null,
    bool $reuseExistingOpenInvoice = false
  ): Invoice
  {
    if ($reuseExistingOpenInvoice) {
      $invoice = Invoice::query()
        ->where('patient_id', $context['patient_id'])
        ->where('facility_id', $context['facility_id'])
        ->whereDate('invoice_date', $invoiceDate)
        ->whereIn('status', ['draft', 'partially_paid'])
        ->latest('id')
        ->first();

      if ($invoice) {
        return $invoice;
      }
    }

    return Invoice::create([
      'patient_id' => $context['patient_id'],
      'facility_id' => $context['facility_id'],
      'state_id' => $context['state_id'] ?? null,
      'lga_id' => $context['lga_id'] ?? null,
      'ward_id' => $context['ward_id'] ?? null,
      'month_year' => Carbon::parse($invoiceDate)->startOfMonth()->format('Y-m-d'),
      'invoice_code' => $this->generateCode('INV'),
      'invoice_date' => $invoiceDate,
      'total_amount' => 0,
      'amount_paid' => 0,
      'outstanding_amount' => 0,
      'status' => 'draft',
      'notes' => $notes,
      'created_by' => $context['created_by'] ?? null,
    ]);
  }

  public function addInvoiceLine(Invoice $invoice, array $line): InvoiceLine
  {
    $quantity = (float) ($line['quantity'] ?? 1);
    $unitPrice = (float) ($line['unit_price'] ?? 0);
    $lineAmount = array_key_exists('line_amount', $line)
      ? (float) $line['line_amount']
      : ($quantity * $unitPrice);

    return InvoiceLine::create([
      'invoice_id' => $invoice->id,
      'patient_id' => $invoice->patient_id,
      'facility_id' => $invoice->facility_id,
      'module' => $line['module'] ?? null,
      'reference_type' => $line['reference_type'] ?? null,
      'reference_id' => $line['reference_id'] ?? null,
      'reference_code' => $line['reference_code'] ?? null,
      'description' => $line['description'],
      'quantity' => $quantity,
      'unit_price' => $unitPrice,
      'line_amount' => $lineAmount,
      'service_date' => $line['service_date'] ?? $invoice->invoice_date,
      'created_by' => $line['created_by'] ?? null,
    ]);
  }

  public function createPaymentAndAllocate(Invoice $invoice, array $paymentData): ?PatientPayment
  {
    $amount = (float) ($paymentData['amount_received'] ?? 0);
    if ($amount <= 0) {
      return null;
    }

    $invoice = $this->refreshInvoiceTotals($invoice);
    $allocatable = min($amount, (float) $invoice->outstanding_amount);
    if ($allocatable <= 0) {
      return null;
    }

    $payment = PatientPayment::create([
      'patient_id' => $invoice->patient_id,
      'facility_id' => $invoice->facility_id,
      'state_id' => $paymentData['state_id'] ?? $invoice->state_id,
      'lga_id' => $paymentData['lga_id'] ?? $invoice->lga_id,
      'ward_id' => $paymentData['ward_id'] ?? $invoice->ward_id,
      'month_year' => Carbon::parse($paymentData['payment_date'] ?? now())->startOfMonth()->format('Y-m-d'),
      'payment_code' => $this->generateCode('PAY'),
      'payment_date' => $paymentData['payment_date'] ?? now()->format('Y-m-d'),
      'amount_received' => $allocatable,
      'payment_method' => $paymentData['payment_method'] ?? null,
      'notes' => $paymentData['notes'] ?? null,
      'received_by' => $paymentData['received_by'] ?? null,
    ]);

    PaymentAllocation::create([
      'patient_payment_id' => $payment->id,
      'invoice_id' => $invoice->id,
      'amount_allocated' => $allocatable,
    ]);

    $this->refreshInvoiceTotals($invoice);

    return $payment;
  }

  public function refreshInvoiceTotals(Invoice $invoice): Invoice
  {
    $invoice->refresh();

    $total = (float) $invoice->lines()->sum('line_amount');
    $paid = (float) $invoice->allocations()->sum('amount_allocated');
    $outstanding = max(0, $total - $paid);

    $status = 'draft';
    if ($total > 0 && $paid <= 0) {
      $status = 'unpaid';
    } elseif ($outstanding > 0 && $paid > 0) {
      $status = 'partially_paid';
    } elseif ($total > 0 && $outstanding <= 0) {
      $status = 'paid';
    }

    $invoice->update([
      'total_amount' => $total,
      'amount_paid' => $paid,
      'outstanding_amount' => $outstanding,
      'status' => $status,
    ]);

    return $invoice->fresh();
  }

  private function generateCode(string $prefix): string
  {
    return $prefix . '-' . now()->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
  }
}
