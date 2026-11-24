from pathlib import Path
from datetime import datetime
import shutil, subprocess, sys

root = Path.cwd()
order_file = root / "app/Http/Controllers/OrderController.php"
quote_file = root / "app/Http/Controllers/QuoteController.php"
view_file = root / "resources/views/orders/show.blade.php"

files = [order_file, quote_file, view_file]

if not all(p.is_file() for p in files):
    sys.exit("ERROR: fichiers manquants. Aucun changement.")

old = {p: p.read_text(encoding="utf-8-sig") for p in files}
new = dict(old)

# 1. Seul Admin peut confirmer une commande.
guard_old = """if ($to === 'confirmee' && (! $request->user()->isAdmin() || $o->approval_status !== 'approved'))"""
guard_new = """if ($to === 'confirmee' && ! $request->user()->isAdmin())"""

if guard_old in new[order_file]:
    new[order_file] = new[order_file].replace(guard_old, guard_new, 1)
elif guard_new not in new[order_file]:
    sys.exit("ERROR: OrderController different. Aucun changement.")

# 2. Bouton Confirmer visible pour Admin.
button_old = """auth()->user()->isAdmin() && $order->approval_status === 'approved'"""
button_new = """auth()->user()->isAdmin()"""

if button_old in new[view_file]:
    new[view_file] = new[view_file].replace(button_old, button_new, 1)

# 3. Ne pas afficher la validation interne dans la page Admin.
panel_old = """@if ($order->status === 'nouvelle')"""
panel_new = """@if ($order->status === 'nouvelle' && ! auth()->user()->isAdmin())"""

if panel_old in new[view_file]:
    new[view_file] = new[view_file].replace(panel_old, panel_new, 1)

# 4. Message correct selon le rôle qui convertit le devis.
message_old = """->with('success', 'Commande créée en attente de validation administrative. Aucun stock déduit.');"""
message_new = """->with('success', auth()->user()->isAdmin()
                ? 'Commande créée. Vous pouvez la confirmer.'
                : 'Commande créée en attente de validation administrative. Aucun stock déduit.');"""

if message_old in new[quote_file]:
    new[quote_file] = new[quote_file].replace(message_old, message_new, 1)
elif "Vous pouvez la confirmer." not in new[quote_file]:
    sys.exit("ERROR: Message de commande introuvable. Aucun changement.")

# Backup avant toute modification.
backup = root / ("backup-admin-original-" + datetime.now().strftime("%Y%m%d-%H%M%S"))
backup.mkdir()

for p in files:
    shutil.copy2(p, backup / p.name)

try:
    for p in files:
        if new[p] != old[p]:
            p.write_text(new[p], encoding="utf-8")

    commands = [
        ["php", "-l", str(order_file)],
        ["php", "-l", str(quote_file)],
        ["php", "artisan", "view:clear"],
        ["php", "artisan", "view:cache"]
    ]

    for cmd in commands:
        if subprocess.run(cmd).returncode != 0:
            raise RuntimeError("Validation failed")

except Exception as e:
    for p in files:
        shutil.copy2(backup / p.name, p)
    sys.exit("ERROR: " + str(e) + ". Backup restored.")

print("SUCCESS: Admin order confirmation restored.")
print("Commercial confirmation remains blocked.")
print("No database modifications.")
print("BACKUP:", backup)
