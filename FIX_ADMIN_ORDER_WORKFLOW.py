from pathlib import Path
from datetime import datetime
import shutil
import subprocess
import sys

controller = Path("app/Http/Controllers/QuoteController.php")
view = Path("resources/views/orders/show.blade.php")

if not controller.exists() or not view.exists():
    sys.exit("ERROR: Project files missing.")

code = controller.read_text(encoding="utf-8-sig")
blade = view.read_text(encoding="utf-8-sig")

marker = "HNB_ADMIN_ORDER_NO_DOUBLE_APPROVAL"

if marker in code:
    print("Fix already installed.")
    sys.exit(0)

start = code.find("$order = Order::create([")
if start == -1:
    sys.exit("ERROR: Order creation block not found.")

end = code.find("]);", start)
if end == -1:
    sys.exit("ERROR: Order creation block incomplete.")

block = code[start:end]
anchor = "'notes'         => $q->notes,"

if block.count(anchor) != 1:
    sys.exit("ERROR: Unexpected Order structure. Nothing changed.")

extra = """'notes'         => $q->notes,

                // HNB_ADMIN_ORDER_NO_DOUBLE_APPROVAL
                'approval_status' => auth()->user()->isAdmin() ? 'approved' : 'pending',
                'approval_requested_by' => auth()->id(),
                'approval_requested_at' => now(),
                'approval_decided_by' => auth()->user()->isAdmin() ? auth()->id() : null,
                'approval_decided_at' => auth()->user()->isAdmin() ? now() : null,"""

new_block = block.replace(anchor, extra, 1)
new_code = code[:start] + new_block + code[end:]

old_view = "@if ($order->status === 'nouvelle')"
new_view = """@if ($order->status === 'nouvelle' && (! auth()->user()->isAdmin() || $order->approval_status !== 'approved'))"""

if blade.count(old_view) != 1:
    sys.exit("ERROR: Order view structure differs. Nothing changed.")

new_blade = blade.replace(old_view, new_view, 1)

backup = Path("backup-admin-order-fix-" + datetime.now().strftime("%Y%m%d-%H%M%S"))
backup.mkdir()

shutil.copy2(controller, backup / "QuoteController.php")
shutil.copy2(view, backup / "order-show.blade.php")

controller.write_text(new_code, encoding="utf-8")
view.write_text(new_blade, encoding="utf-8")

commands = [
    ["php", "-l", str(controller)],
    ["php", "artisan", "view:clear"],
    ["php", "artisan", "view:cache"]
]

for cmd in commands:
    result = subprocess.run(cmd)
    if result.returncode != 0:
        shutil.copy2(backup / "QuoteController.php", controller)
        shutil.copy2(backup / "order-show.blade.php", view)
        sys.exit("ERROR: Fix reverted. Check output.")

print("SUCCESS: Admin-created orders are approved without duplicate requests.")
print("Commercial orders still require director approval.")
print("Backup:", backup)
