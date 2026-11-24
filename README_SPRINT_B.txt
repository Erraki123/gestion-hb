HN BLOOM FINAL SPRINT B - URGENT DOCUMENT SEND SAFETY

Applies to SOURCE_ACTUEL_20261115 ZIP plus Sprint A.

- Disables automatic quote/invoice sending by default, even if prior notify flags are enabled.
- Limits HTTP documents.send action to Admin role.
- Disables CLI documents:send unless code config is reviewed and changed AND --confirm used.
- Does not add documented two-stage approval for all documents. Admin sending is NOT separate approval.
- Does NOT prevent manual email/WhatsApp from other independent endpoints or service integrations.
- Source patch tested for PHP syntax only; run the installer and application tests locally.

Installer: powershell -ExecutionPolicy Bypass -File .\INSTALL_FINAL_SPRINT_B.ps1

IMPORTANT: Workflow for both all quotes AND invoices approval still needs implementation and E2E tests before production.
