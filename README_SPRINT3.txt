HN BLOOM UI SPRINT 3 - Admin Dashboard + Prospection modal

1. Extract ZIP to the project root.
2. PowerShell: powershell -ExecutionPolicy Bypass -File .\INSTALL_HNB_UI_SPRINT3.ps1
3. Open /admin/dashboard and /prospects; Ctrl+F5.

Changes:
- Existing four KPI cards now lead the dashboard (under period control).
- Approval, follow-up, ATLAS, WhatsApp alerts are compact notices after KPIs.
- ATLAS long details area becomes one compact entry and an accessible modal overlay.
- Source CSV / existing filters, per-company modal and review forms remain intact.
- No changes to controllers, routes, migrations or API integrations.
- CSS is under public/css/hnb-ui-sprint3.css, loaded from layouts/app.blade.php.
- Installer backs up every changed file. Idempotent on rerun.

Notes:
- The admin-only ATLAS section remains admin-only.
- The overlay opens again after applying its filters or a request to /prospects?atlas=open.
- Test keyboard and mobile behavior in the browser after install.
- This ZIP is a patch for the existing HN BLOOM version, not a standalone app.
