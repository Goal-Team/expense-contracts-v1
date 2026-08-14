# Release Notes - 2026-04-17

## Release Summary
This release introduces the Agreement Template module and updates contract creation to preview agreement templates from Word files (DOC/DOCX) instead of the previous Quill-based template editor flow.

## End User Changes

### 1) New Agreement Template Management
Users can now manage agreement templates from dedicated screens:
- Create template
- Edit template
- Publish/archive template
- Download uploaded source DOCX
- Sync placeholders and manage variable defaults

### 2) Template Preview Change in Contract Creation
In contract creation, selecting "Take from template" now:
- Resolves the best matching published agreement template by scope
- Shows a file-based template preview using an inline PDF rendering of the Word source
- Displays template name and preview status

### 3) Better Scope Matching
Published templates are selected using fallback scope matching:
- contract_type + payment_type + entity_type_id
- contract_type + payment_type
- contract_type + entity_type_id
- contract_type only

## Developer Changes

### 1) New Controller
Added dedicated controller for agreement template CRUD and related actions:
- `Modules/Contract/app/Http/Controllers/AgreementTemplateController.php`

Includes:
- CRUD endpoints
- Placeholder sync
- Variable update
- Publish flow
- Preview/download
- Contract-facing resolve API and preview endpoint

### 2) Route Additions
Added agreement-template routes under Contract module:
- Management routes: index/create/store/edit/update/destroy
- Actions: sync-placeholders, variables update, publish, preview, download
- Contract create integration:
  - POST `agreement-templates/resolve-for-contract`
  - GET `agreement-templates/{id}/contract-preview`

### 3) Contract Create UI/JS Migration
Updated contract create flow from Quill content rendering to file preview rendering:
- View updates in `Modules/Contract/resources/views/contract/contractCreate.blade.php`
- JS updates in `Modules/Contract/resources/assets/js/contract.js`

### 4) Contract Setup Template Source Migration
Contract setup template fetch/store now uses `AgreementTemplate` instead of `ContractTemplates`:
- Updated `Modules/Contractsetup/app/Http/Controllers/ContractsetupController.php`

### 5) New Models
Added:
- `app/Models/AgreementTemplate.php`
- `app/Models/AgreementTemplateVariable.php`
- `app/Models/AgreementTemplateRender.php`

### 6) New Services
Added:
- `app/Services/AgreementTemplateStorageService.php`
- `app/Services/AgreementTemplateVariableService.php`
- `app/Services/AgreementTemplateValidationService.php`
- `app/Services/AgreementTemplateSourceResolver.php`
- `app/Services/AgreementTemplateRenderService.php`
- `app/Services/TemplateTokenService.php`

Registered in:
- `app/Providers/AppServiceProvider.php`

### 7) Database Changes
Added migration files:
- `database/migrations/2026_04_17_000001_create_agreement_templates_table.php`
- `database/migrations/2026_04_17_000002_create_agreement_template_variables_table.php`
- `database/migrations/2026_04_17_000003_create_agreement_template_renders_table.php`

## Operational Notes

### Deployment
1. Apply DB changes (migrations or equivalent raw SQL).
2. Deploy backend + frontend changes together.
3. Clear caches if needed (`config`, `route`, `view`) per environment standards.

### Backward Compatibility
- Legacy `ContractTemplates` model file still exists.
- Contract setup/template flow in changed paths now reads/writes through `AgreementTemplate`.
- `ContractCustomController` changes are not included in this release state.

## QA Focus
- Agreement template CRUD and validations
- Publish conflict behavior for same scope
- Placeholder sync and variable updates
- Contract create template preview (loading, empty state, error state)
- Download and preview endpoints access with middleware/auth

## Risks
- DOCX-to-PDF preview depends on server document conversion stack and PDF rendering behavior.
- Existing template data may require migration strategy if still only stored in legacy `contract_templates` table.
