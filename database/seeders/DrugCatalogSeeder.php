<?php

namespace Database\Seeders;

use App\Models\DrugCatalogItem;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class DrugCatalogSeeder extends Seeder
{
  public function run(): void
  {
    $drugs = [
      ['drug_name' => 'Paracetamol', 'formulation' => 'Tablet', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Paracetamol', 'formulation' => 'Syrup', 'strength' => '120mg/5ml', 'route' => 'Oral'],
      ['drug_name' => 'Ibuprofen', 'formulation' => 'Tablet', 'strength' => '400mg', 'route' => 'Oral'],
      ['drug_name' => 'Ibuprofen', 'formulation' => 'Syrup', 'strength' => '100mg/5ml', 'route' => 'Oral'],
      ['drug_name' => 'Diclofenac', 'formulation' => 'Tablet', 'strength' => '50mg', 'route' => 'Oral'],
      ['drug_name' => 'Amoxicillin', 'formulation' => 'Capsule', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Amoxicillin', 'formulation' => 'Suspension', 'strength' => '125mg/5ml', 'route' => 'Oral'],
      ['drug_name' => 'Cloxacillin', 'formulation' => 'Capsule', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Co-amoxiclav', 'formulation' => 'Tablet', 'strength' => '625mg', 'route' => 'Oral'],
      ['drug_name' => 'Cefuroxime', 'formulation' => 'Tablet', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Ceftriaxone', 'formulation' => 'Injection', 'strength' => '1g', 'route' => 'IV/IM'],
      ['drug_name' => 'Azithromycin', 'formulation' => 'Tablet', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Erythromycin', 'formulation' => 'Tablet', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Metronidazole', 'formulation' => 'Tablet', 'strength' => '400mg', 'route' => 'Oral'],
      ['drug_name' => 'Metronidazole', 'formulation' => 'Infusion', 'strength' => '500mg/100ml', 'route' => 'IV'],
      ['drug_name' => 'Ciprofloxacin', 'formulation' => 'Tablet', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Levofloxacin', 'formulation' => 'Tablet', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Gentamicin', 'formulation' => 'Injection', 'strength' => '80mg/2ml', 'route' => 'IV/IM'],
      ['drug_name' => 'Doxycycline', 'formulation' => 'Capsule', 'strength' => '100mg', 'route' => 'Oral'],
      ['drug_name' => 'Fluconazole', 'formulation' => 'Capsule', 'strength' => '150mg', 'route' => 'Oral'],
      ['drug_name' => 'Artemether-Lumefantrine', 'formulation' => 'Tablet', 'strength' => '20/120mg', 'route' => 'Oral'],
      ['drug_name' => 'Artesunate', 'formulation' => 'Injection', 'strength' => '60mg', 'route' => 'IV/IM'],
      ['drug_name' => 'Sulfadoxine-Pyrimethamine', 'formulation' => 'Tablet', 'strength' => '500/25mg', 'route' => 'Oral'],
      ['drug_name' => 'Quinine', 'formulation' => 'Injection', 'strength' => '600mg/2ml', 'route' => 'IV/IM'],
      ['drug_name' => 'Albendazole', 'formulation' => 'Tablet', 'strength' => '400mg', 'route' => 'Oral'],
      ['drug_name' => 'Mebendazole', 'formulation' => 'Tablet', 'strength' => '100mg', 'route' => 'Oral'],
      ['drug_name' => 'Vitamin A', 'formulation' => 'Capsule', 'strength' => '200000IU', 'route' => 'Oral'],
      ['drug_name' => 'Folic Acid', 'formulation' => 'Tablet', 'strength' => '5mg', 'route' => 'Oral'],
      ['drug_name' => 'Ferrous Sulphate', 'formulation' => 'Tablet', 'strength' => '200mg', 'route' => 'Oral'],
      ['drug_name' => 'Calcium Carbonate', 'formulation' => 'Tablet', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Multivitamin', 'formulation' => 'Tablet', 'strength' => 'Standard', 'route' => 'Oral'],
      ['drug_name' => 'Omeprazole', 'formulation' => 'Capsule', 'strength' => '20mg', 'route' => 'Oral'],
      ['drug_name' => 'Pantoprazole', 'formulation' => 'Tablet', 'strength' => '40mg', 'route' => 'Oral'],
      ['drug_name' => 'Loperamide', 'formulation' => 'Capsule', 'strength' => '2mg', 'route' => 'Oral'],
      ['drug_name' => 'Oral Rehydration Salts', 'formulation' => 'Sachet', 'strength' => 'WHO Formula', 'route' => 'Oral'],
      ['drug_name' => 'Zinc Sulphate', 'formulation' => 'Tablet', 'strength' => '20mg', 'route' => 'Oral'],
      ['drug_name' => 'Salbutamol', 'formulation' => 'Inhaler', 'strength' => '100mcg', 'route' => 'Inhalation'],
      ['drug_name' => 'Salbutamol', 'formulation' => 'Syrup', 'strength' => '2mg/5ml', 'route' => 'Oral'],
      ['drug_name' => 'Prednisolone', 'formulation' => 'Tablet', 'strength' => '5mg', 'route' => 'Oral'],
      ['drug_name' => 'Hydrocortisone', 'formulation' => 'Injection', 'strength' => '100mg', 'route' => 'IV/IM'],
      ['drug_name' => 'Chlorpheniramine', 'formulation' => 'Tablet', 'strength' => '4mg', 'route' => 'Oral'],
      ['drug_name' => 'Cetirizine', 'formulation' => 'Tablet', 'strength' => '10mg', 'route' => 'Oral'],
      ['drug_name' => 'Loratadine', 'formulation' => 'Tablet', 'strength' => '10mg', 'route' => 'Oral'],
      ['drug_name' => 'Amlodipine', 'formulation' => 'Tablet', 'strength' => '5mg', 'route' => 'Oral'],
      ['drug_name' => 'Lisinopril', 'formulation' => 'Tablet', 'strength' => '10mg', 'route' => 'Oral'],
      ['drug_name' => 'Hydrochlorothiazide', 'formulation' => 'Tablet', 'strength' => '25mg', 'route' => 'Oral'],
      ['drug_name' => 'Metformin', 'formulation' => 'Tablet', 'strength' => '500mg', 'route' => 'Oral'],
      ['drug_name' => 'Glibenclamide', 'formulation' => 'Tablet', 'strength' => '5mg', 'route' => 'Oral'],
      ['drug_name' => 'Insulin Regular', 'formulation' => 'Injection', 'strength' => '100IU/ml', 'route' => 'SC'],
      ['drug_name' => 'Insulin NPH', 'formulation' => 'Injection', 'strength' => '100IU/ml', 'route' => 'SC'],
    ];

    $facilities = Facility::query()->get(['id', 'state_id', 'lga_id', 'ward_id']);

    foreach ($facilities as $facility) {
      foreach ($drugs as $drug) {
        DrugCatalogItem::query()->firstOrCreate(
          [
            'facility_id' => $facility->id,
            'drug_name' => $drug['drug_name'],
            'formulation' => $drug['formulation'],
            'strength' => $drug['strength'],
          ],
          [
            'state_id' => $facility->state_id,
            'lga_id' => $facility->lga_id,
            'ward_id' => $facility->ward_id,
            'route' => $drug['route'],
            'notes' => 'Seeded starter drug catalog item.',
            'is_active' => true,
          ]
        );
      }
    }
  }
}

