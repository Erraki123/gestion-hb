# HN BLOOM — Phase 3B : WhatsApp Inbox (Admin)

This patch adds a protected UltraMsg inbound webhook and a minimal read-only admin inbox. It does NOT activate outbound WhatsApp, send notifications, or use any real API credentials.

## Installation (PowerShell, from Laravel root)

1. Back up your files and database. Extract ZIP in Laravel root, then append once to routes/web.php:

```powershell
$line = "require __DIR__ . '/ultramsg_phase3b.php';"
if (-not (Select-String -Path .\routes\web.php -SimpleMatch $line -Quiet)) {
  Add-Content -Path .\routes\web.php -Value "`r`n$line"
}
php artisan migrate
php artisan optimize:clear
php artisan route:list --path=integrations/ultramsg
php artisan route:list --path=admin/whatsapp-inbox
php artisan test --filter=UltraMsgWebhookTest
```

2. Set ULTRAMSG_WEBHOOK_SECRET to a strong random URL-safe secret 32-128 chars and ULTRAMSG_INSTANCE_ID to your real instance ID in server configuration. Never share tokens or secret URL. Keep NOTIFY_WHATSAPP=false until an authorized test.

3. On a HTTPS test deployment, configure the UltraMsg webhook URL as https://YOUR_DOMAIN/integrations/ultramsg/inbound/YOUR_LONG_SECRET and turn on `Webhook on Received`. Do not put the secret into logs or public chat.

4. Admin inbox: /admin/whatsapp-inbox. Messages are view-only. Test with consenting numbers. For `fromMe=true`, groups or non-chat messages nothing is stored. Duplicate provider IDs are ignored. Unknown phone numbers remain unlinked.

Security note: UltraMsg's published examples do not document a signed webhook header. A secret URL is an additional barrier, but it is not cryptographic source authentication. For high security, use provider-supported signatures or an independently protected gateway.

Migration safe: new table only. No changes to existing business tables. For large client databases, add a normalized_phone indexed field before production (current matching scans phone values).
