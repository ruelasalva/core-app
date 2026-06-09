# Security Review

## Scope

This review covers current architecture risks observed in admin, portals, documents, permissions and deployment.

## Permissions Model

CORE-APP uses ORMAuth permissions.

Current direction:

- Broad permissions are being split into granular permissions.
- SAT, CFDI, billing, fiscal and accounting have granular permission work in progress.
- Menu visibility has been separated from some backend enforcement, but controller enforcement still needs full completion.

High priority:

- Finish granular controller enforcement for all critical modules.
- Remove temporary fallback permissions only after roles are seeded and tested.

## Exposed Areas

Critical exposed areas:

- Admin modules.
- Portal modules.
- Document downloads.
- SAT credentials.
- Payment/bank statements.
- Public frontend CMS.

Current deployment protections:

- `fuel/` should stay outside webroot in production.
- `docs/` should stay outside webroot.
- `.htaccess` blocks accidental private files if copied into webroot.

## Document Protection

Admin document controller includes a controlled download endpoint:

- `admin/documents/download/{document_id}`

Expected validations:

- Authenticated admin.
- Permission check.
- Active document.
- File exists.
- Path remains inside allowed storage/upload directories.
- Denied attempts are logged.

Risks found:

- Some portal views still reference `document.file_path` in supplier purchase documents.
- Some portal controllers still select `file_path` internally for controlled downloads.
- Internal selection of `file_path` is acceptable only if the response does not expose it.

High priority:

- Replace any portal UI link that builds URLs from `file_path`.
- Ensure all JSON responses return `download_url`, not physical or storage paths.

## Portal Security

Portal rule:

- Use `$this->portal_link->party_id`.
- Never trust `party_id` from portal requests.

Risks:

- Portal modules should be audited endpoint by endpoint for ownership checks.
- Supplier purchase document endpoints must be reviewed first because evidence files can be sensitive.

## SAT and Fiscal Security

Risks:

- SAT credential responses may expose certificate file path fields.
- SAT storage path is configurable and must never become publicly accessible.
- Fiscal and accounting modules require strict separation of view, export, post and close permissions.

Recommendations:

1. Mask SAT credential storage paths in admin JSON unless needed by a super-admin.
2. Keep SAT credential downloads/upload actions behind `sat.access[credentials]`.
3. Add audit logging for credential changes.

## Deployment Security

Expected production:

```text
home/user/
  fuel/
  public_html/
```

Do not upload these to `public_html`:

- `fuel/`
- `docs/`
- `.git/`
- `.agents/`
- `.codex/`
- `node_modules/`
- `composer.json`
- `composer.lock`
- `oil`
- `AGENTS.md`

## Priority Issues

High:

- Remove portal document `file_path` exposure in supplier purchase documents.
- Finish granular permission enforcement in all critical controllers.
- Move SAT credential path fields out of regular JSON responses where possible.

Medium:

- Move large controller workflows into services.
- Split large Vue scripts into smaller files after stable behavior.
- Add role testing checklist for commercial, finance and fiscal roles.

Low:

- Normalize documentation coverage for every admin module.
- Add consistent DataTables export documentation.

