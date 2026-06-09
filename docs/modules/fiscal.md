# Fiscal, SAT and CFDI Modules

## Purpose

Fiscal modules connect SAT/CFDI operations with tax review, VAT, REP audit, fiscal ledger and DIOT roadmap.

## SAT

Controller:

- `fuel/app/classes/controller/admin/sat.php`

Services:

- `Service_Core_Sat_Sync`
- `Service_Core_Sat_Validation`
- `Service_Core_Sat_Catalog_Sync`
- `Service_Core_Sat_RepTaxImporter`

Main areas:

- SAT credentials.
- SAT downloads.
- Package verification.
- XML and metadata download.
- SAT catalog sync.

Granular permissions observed:

- `sat.access[view]`
- `sat.access[download]`
- `sat.access[credentials]`
- `sat.access[catalog_sync]`

Risk:

- Some SAT credential responses still include certificate path fields. Those fields must be reviewed before exposing to less privileged roles.

## CFDI

Controller:

- `fuel/app/classes/controller/admin/cfdi.php`

Service:

- `Service_Core_Sat_Cfdi_Importer`

Main areas:

- XML import.
- CFDI classification.
- Supplier mappings.
- Convert CFDI to purchase.
- Convert CFDI to sale.
- Materialize catalogs.

Granular permissions observed:

- `cfdi.access[view]`
- `cfdi.access[audit]`
- `cfdi.access[classify]`
- `cfdi.access[link]`
- `cfdi.access[convert_purchase]`
- `cfdi.access[convert_sale]`

## VAT Summary

Controller:

- `fuel/app/classes/controller/admin/fiscal.php`

Services:

- `Service_Core_Fiscal_VatSummary`
- `Service_Core_Fiscal_VatDetail`

Endpoints:

- `admin/fiscal/vat`
- `admin/fiscal/vat_data`

Permission:

- `fiscal.access[iva]`

## Fiscal Ledger

Services:

- `Service_Core_Fiscal_LedgerDetail`
- `Service_Core_Fiscal_TaxLedgerBuilder`

Endpoints:

- `admin/fiscal/ledger`
- `admin/fiscal/ledger_data`
- `admin/fiscal/validations`
- `admin/fiscal/validations_data`

Permissions:

- `fiscal.access[ledger]`

## REP Audit

Service:

- `Service_Core_Fiscal_RepAudit`

Endpoints:

- `admin/fiscal/rep_audit`
- `admin/fiscal/rep_audit_data`

Permissions:

- `fiscal.access[view]`
- `billing.access[rep]`

## DIOT Roadmap

Current service:

- `Service_Core_Fiscal_DiotPreview`

Endpoints:

- `admin/fiscal/diot`
- `admin/fiscal/diot_data`

Permission:

- `fiscal.access[diot]`

Roadmap:

1. Keep DIOT preview read-only until validations are complete.
2. Add export permission only when export endpoint exists.
3. Add repair task if historical supplier invoices need recalculation.
4. Document DIOT field sources by table.

## Closing Center

Endpoints:

- `admin/fiscal/closing`
- `admin/fiscal/closing_data`

Permission:

- `fiscal.access[closing]`

Risk:

- Closing logic still has substantial controller code and should move to services before more fiscal automation is added.

