from pathlib import Path
from datetime import datetime
import shutil
import subprocess
import sys

file = Path("resources/views/quotes/show.blade.php")

if not file.exists():
    sys.exit("ERROR: Quote view not found")

content = file.read_text(encoding="utf-8-sig")
marker = "HNB_QUOTE_ADMIN_SIMPLE_V1"

if marker in content:
    print("Patch already installed")
    sys.exit(0)

start = "        @if (! $order && $quote->requires_approval)"
end = "        {{-- ÉTAPE SUIVANTE"

if content.count(start) != 1 or content.count(end) != 1:
    sys.exit("ERROR: View structure differs. No changes made.")

a = content.index(start)
b = content.index(end)

if a >= b:
    sys.exit("ERROR: Unexpected view structure")

section = content[a:b]

if "quotes.approval.decide" not in section:
    sys.exit("ERROR: Approval section not found")

replacement = (
    "        {{-- HNB_QUOTE_ADMIN_SIMPLE_V1 --}}\n"
    "        @if (! auth()->user()->isAdmin())\n"
    + section
    + "        @endif\n\n"
)

updated = content[:a] + replacement + content[b:]

import re

pattern = r"(?m)^(\s*)(@include\('documents\._release_review',\s*\['document'\s*=>\s*\$quote,\s*'type'\s*=>\s*'quote'\]\))\s*$"
matches = list(re.finditer(pattern, updated))

if len(matches) > 1:
    sys.exit("ERROR: Multiple release blocks found")

if matches:
    m = matches[0]
    indent = m.group(1)
    block = m.group(2)
    wrapped = (
        indent + "@if (! auth()->user()->isAdmin())\n"
        + indent + block + "\n"
        + indent + "@endif"
    )
    updated = updated[:m.start()] + wrapped + updated[m.end():]

updated = updated.replace(
    "Quelle est la réponse du client ?",
    "Décision du client"
)
updated = updated.replace(
    "Choisissez une des deux options ci-dessous.",
    "Enregistrez la réponse du client."
)

backup = Path("backup-quote-ui-" + datetime.now().strftime("%Y%m%d-%H%M%S"))
backup.mkdir()
shutil.copy2(file, backup / "show.blade.php")

file.write_text(updated, encoding="utf-8")

for command in [
    ["php", "artisan", "view:clear"],
    ["php", "artisan", "view:cache"],
]:
    result = subprocess.run(command)
    if result.returncode != 0:
        shutil.copy2(backup / "show.blade.php", file)
        sys.exit("ERROR: Restored backup after Blade failure")

print("SUCCESS: Admin quote UI simplified")
print("Backup:", backup)
