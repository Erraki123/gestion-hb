HN BLOOM FINAL SPRINT C - DOCUMENT RELEASE APPROVAL

Installs an explicit director approval gate for every quote/invoice external send.
Separate from existing quote price approvals and order approvals.

- Commercial/Admin can request release from quote/invoice detail.
- Director/Admin can approve or reject (reason required on reject).
- Content and recipient changes invalidate approval.
- Both DocumentSender::queue() and SendDocumentJob::handle() enforce gate.
- Only Admin can manually dispatch; automatic sends still disabled by Sprint B.
- Adds append-only-by-request audit table document_release_approvals.
- Does not migrate existing docs into approved state. Existing jobs become skipped.
- A migration is required; make DB backup prior to installing.

IMPORTANT: patch based on the user-provided source dated 2026-11-15 15:40 + Sprint B.
Run INSTALL_FINAL_SPRINT_C.ps1 in project root; it backs up modified source files.
Review output and manually test as Admin and Commercial before production.
