from pathlib import Path
from datetime import datetime
import shutil
import subprocess
import sys

view = Path("resources/views/quotes/show.blade.php")
controller = Path("app/Http/Controllers/QuoteController.php")

if not view.exists() or not controller.exists():
    sys.exit("ERROR: Quote files not found.")

v = view.read_text(encoding="utf-8-sig")
c = controller.read_text(encoding="utf-8-sig")

old_button = "@if ($quote->canConvert() && $quote->canSendToClient())"
new_button = "@if ($quote->isOpen() && (auth()->user()->isAdmin() || $quote->canSendToClient()))"

marker = "// HNB_ADMIN_CLIENT_ACCEPT_V1"

if marker in c:
    print("Fix already installed.")
    sys.exit(0)

if v.count(old_button) != 1:
    sys.exit("ERROR: Client accept button condition not found.")

start = c.find("public function convert(")

if start < 0:
    sys.exit("ERROR: Convert method not found.")

tail = c[start:]

guards = [
    "if ($q->approval_status !== 'approved') {",
    "if (! $q->canSendToClient()) {"
]

guard = next((g for g in guards if g in tail), None)

if guard is None:
    sys.exit("ERROR: Approval guard not found. No changes made.")

v2 = v.replace(old_button, new_button, 1)

approval = """// HNB_ADMIN_CLIENT_ACCEPT_V1
            // An explicit Admin client-accept action also records
            // the director's approval before conversion.
            if (auth()->user()->isAdmin() && $q->approval_status !== 'approved') {
                $q->update([
                    'approval_status' => 'approved',
                    'approval_decided_by' => auth()->id(),
                    'approval_decided_at' => now(),
                    'approval_comment' => 'Validation direction lors de l acceptation client',
                ]);
            }

            """

tail = tail.replace(guard, approval + guard, 1)
c2 = c[:start] + tail

backup = Path(
    "backup-admin-client-accept-" +
    datetime.now().strftime("%Y%m%d-%H%M%S")
)
backup.mkdir()

shutil.copy2(view, backup / "show.blade.php")
shutil.copy2(controller, backup / "QuoteController.php")

def restore():
    shutil.copy2(backup / "show.blade.php", view)
    shutil.copy2(backup / "QuoteController.php", controller)

try:
    view.write_text(v2, encoding="utf-8")
    controller.write_text(c2, encoding="utf-8")

    for command in [
        ["php", "-l", str(controller)],
        ["php", "artisan", "view:clear"],
        ["php", "artisan", "view:cache"],
    ]:
        if subprocess.run(command).returncode != 0:
            raise RuntimeError("Validation failed")

except Exception as exc:
    restore()
    sys.exit("ERROR: " + str(exc) + ". Backup restored.")

print("SUCCESS: Admin can accept or refuse client decision.")
print("Commercial approval protection preserved.")
print("Backup:", backup)
