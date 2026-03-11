START TRANSACTION;

CREATE TEMPORARY TABLE tmp_seed_drugs (
  drug_name VARCHAR(150) NOT NULL,
  formulation VARCHAR(120) NOT NULL,
  strength VARCHAR(120) NOT NULL,
  route VARCHAR(80) NOT NULL
);

INSERT INTO tmp_seed_drugs (drug_name, formulation, strength, route) VALUES
('Paracetamol','Tablet','500mg','Oral'),
('Paracetamol','Syrup','120mg/5ml','Oral'),
('Ibuprofen','Tablet','400mg','Oral'),
('Ibuprofen','Syrup','100mg/5ml','Oral'),
('Diclofenac','Tablet','50mg','Oral'),
('Amoxicillin','Capsule','500mg','Oral'),
('Amoxicillin','Suspension','125mg/5ml','Oral'),
('Cloxacillin','Capsule','500mg','Oral'),
('Co-amoxiclav','Tablet','625mg','Oral'),
('Cefuroxime','Tablet','500mg','Oral'),
('Ceftriaxone','Injection','1g','IV/IM'),
('Azithromycin','Tablet','500mg','Oral'),
('Erythromycin','Tablet','500mg','Oral'),
('Metronidazole','Tablet','400mg','Oral'),
('Metronidazole','Infusion','500mg/100ml','IV'),
('Ciprofloxacin','Tablet','500mg','Oral'),
('Levofloxacin','Tablet','500mg','Oral'),
('Gentamicin','Injection','80mg/2ml','IV/IM'),
('Doxycycline','Capsule','100mg','Oral'),
('Fluconazole','Capsule','150mg','Oral'),
('Artemether-Lumefantrine','Tablet','20/120mg','Oral'),
('Artesunate','Injection','60mg','IV/IM'),
('Sulfadoxine-Pyrimethamine','Tablet','500/25mg','Oral'),
('Quinine','Injection','600mg/2ml','IV/IM'),
('Albendazole','Tablet','400mg','Oral'),
('Mebendazole','Tablet','100mg','Oral'),
('Vitamin A','Capsule','200000IU','Oral'),
('Folic Acid','Tablet','5mg','Oral'),
('Ferrous Sulphate','Tablet','200mg','Oral'),
('Calcium Carbonate','Tablet','500mg','Oral'),
('Multivitamin','Tablet','Standard','Oral'),
('Omeprazole','Capsule','20mg','Oral'),
('Pantoprazole','Tablet','40mg','Oral'),
('Loperamide','Capsule','2mg','Oral'),
('Oral Rehydration Salts','Sachet','WHO Formula','Oral'),
('Zinc Sulphate','Tablet','20mg','Oral'),
('Salbutamol','Inhaler','100mcg','Inhalation'),
('Salbutamol','Syrup','2mg/5ml','Oral'),
('Prednisolone','Tablet','5mg','Oral'),
('Hydrocortisone','Injection','100mg','IV/IM'),
('Chlorpheniramine','Tablet','4mg','Oral'),
('Cetirizine','Tablet','10mg','Oral'),
('Loratadine','Tablet','10mg','Oral'),
('Amlodipine','Tablet','5mg','Oral'),
('Lisinopril','Tablet','10mg','Oral'),
('Hydrochlorothiazide','Tablet','25mg','Oral'),
('Metformin','Tablet','500mg','Oral'),
('Glibenclamide','Tablet','5mg','Oral'),
('Insulin Regular','Injection','100IU/ml','SC'),
('Insulin NPH','Injection','100IU/ml','SC');

INSERT INTO drug_catalog_items (
  facility_id,
  state_id,
  lga_id,
  ward_id,
  drug_name,
  formulation,
  strength,
  route,
  notes,
  is_active,
  created_at,
  updated_at
)
SELECT
  f.id AS facility_id,
  f.state_id,
  f.lga_id,
  f.ward_id,
  t.drug_name,
  t.formulation,
  t.strength,
  t.route,
  'Seeded starter drug catalog item.' AS notes,
  1 AS is_active,
  NOW() AS created_at,
  NOW() AS updated_at
FROM facilities f
CROSS JOIN tmp_seed_drugs t
LEFT JOIN drug_catalog_items d
  ON d.facility_id = f.id
  AND d.drug_name = t.drug_name
  AND d.formulation = t.formulation
  AND d.strength = t.strength
  AND d.deleted_at IS NULL
WHERE d.id IS NULL;

DROP TEMPORARY TABLE tmp_seed_drugs;

COMMIT;

