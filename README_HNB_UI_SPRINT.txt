HN BLOOM UI Sprint 1 - Premium Consistency
==========================================
Applies to all authenticated pages using resources/views/layouts/app.blade.php.
Scope: dashboard admin/commercial, prospects, approvals, quotes, invoices, atlas views, AI floating overlay.
Updates only: app Blade layout + new CSS file. No controller/model/route/database/API changes.
Installer creates a timestamped backup of modified files.

Steps from project root (PowerShell):
  Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_UI_SPRINT_1.zip" -DestinationPath . -Force
  powershell -ExecutionPolicy Bypass -File .\INSTALL_HNB_UI_SPRINT.ps1
  npm run build
  php artisan view:cache
  php artisan serve
Visit dashboard, /prospects, /approvals, /admin/atlas/scoring-v2.
Refresh Ctrl+F5.

Notes:
- If npm run build fails, UI won't show new CSS. Do not deploy before fixing.
- The CSS is a harmonization layer, not a full redesign of every individual view.
- This archive does not contain post-source Sprint B/E changes and does not overwrite those files.
- Keep UI patch backups; to rollback restore backed-up app.blade.php and remove new hnb-finish.css as applicable.
