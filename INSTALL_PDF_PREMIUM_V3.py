from pathlib import Path
from datetime import datetime
import shutil
import subprocess
import sys

root = Path("resources/views/pdf")
style = root / "_style.blade.php"
header = root / "_header.blade.php"
footer = root / "_footer.blade.php"

files = [style, header, footer]

if not all(f.exists() for f in files):
    sys.exit("ERROR: PDF template files missing.")

original = {f: f.read_text(encoding="utf-8-sig") for f in files}

marker = "HNB_PREMIUM_PDF_DESIGN_V3"
if marker in original[style]:
    print("Premium PDF V3 already installed.")
    sys.exit(0)

if "</style>" not in original[style]:
    sys.exit("ERROR: CSS structure unexpected. No changes.")

backup = Path("backup-pdf-premium-" +
              datetime.now().strftime("%Y%m%d-%H%M%S"))
backup.mkdir()

for f in files:
    shutil.copy2(f, backup / f.name)

new_header = r'''
@php
    $web = $web ?? false;
    $logoFs = public_path('images/hn-bloom-logo.png');
    $logoSrc = $web ? asset('images/hn-bloom-logo.png') : $logoFs;
@endphp

<div class="hnb-top-decoration"></div>

<div class="hnb-header">
    <table>
        <tr>
            <td class="hnb-brand">
                @if(file_exists($logoFs) || $web)
                    <img src="{{ $logoSrc }}" class="hnb-logo" alt="HN BLOOM">
                @else
                    <div class="hnb-logo-fallback">HN BLOOM</div>
                @endif
            </td>
            <td class="hnb-slogan">
                ENSEMBLE VERS<br>
                UN AVENIR MEILLEUR
                <div class="hnb-gold-line"></div>
            </td>
        </tr>
    </table>
</div>

<div class="hnb-document-heading">
    <table>
        <tr>
            <td class="hnb-document-title">{{ $title }}</td>
            <td class="hnb-document-details">
                <strong>{{ $ref }}</strong>
                <div>{{ $date }}</div>
            </td>
        </tr>
    </table>
</div>
'''

new_footer = r'''
<div class="hnb-footer">
    <table>
        <tr>
            <td>
                <strong>HN BLOOM</strong>
                <div>{{ config('commerce.company.address') }}</div>
            </td>
            <td>
                <div>{{ config('commerce.company.phone') }}</div>
                <div>{{ config('commerce.company.email') }}</div>
            </td>
            <td>
                <div>{{ config('commerce.company.website') }}</div>
                <div>Page <span class="pagenum"></span></div>
            </td>
        </tr>
    </table>
</div>
'''

premium_css = r'''
/* HNB_PREMIUM_PDF_DESIGN_V3 */

@page {
    size: A4 portrait;
    margin: 0;
}

body {
    background: #ffffff !important;
    color: #181818 !important;
    font-family: DejaVu Sans, sans-serif;
    font-size: 9px;
    padding-bottom: 96px;
}

.hnb-top-decoration {
    height: 12px;
    background: #181818;
    border-bottom: 5px solid #cda34e;
}

.hnb-header {
    padding: 24px 40px 8px;
}

.hnb-header td {
    vertical-align: middle;
    border: none;
}

.hnb-brand {
    width: 48%;
}

.hnb-logo {
    width: 205px;
    height: auto;
}

.hnb-logo-fallback {
    font-family: DejaVu Serif, serif;
    font-size: 30px;
    font-weight: bold;
    color: #bd8a2e;
}

.hnb-slogan {
    width: 52%;
    text-align: center;
    font-size: 14px;
    letter-spacing: 3px;
    line-height: 1.55;
    color: #181818;
}

.hnb-gold-line {
    margin: 13px auto 0;
    width: 85%;
    border-top: 2px solid #c99b46;
}

.hnb-document-heading {
    margin: 18px 40px 14px;
}

.hnb-document-heading td {
    vertical-align: middle;
}

.hnb-document-title {
    width: 61%;
    font-family: DejaVu Sans, sans-serif;
    font-size: 30px;
    font-weight: bold;
    color: #101010;
    text-transform: uppercase;
    border-bottom: 2px solid #c99b46;
}

.hnb-document-details {
    width: 39%;
    text-align: center;
    padding: 12px 8px !important;
    border: 1px solid #c99b46;
    font-size: 10px;
    line-height: 1.8;
}

.hnb-document-details strong {
    font-size: 12px;
    color: #111111;
}

.page {
    padding: 8px 40px 12px !important;
}

.card {
    background: #ffffff !important;
    border: 1px solid #e1d1af !important;
    border-left: 3px solid #c99b46 !important;
    padding: 12px !important;
}

.card-label {
    color: #a4772c !important;
    font-size: 9px !important;
    font-weight: bold;
}

.grid {
    margin-top: 24px !important;
    border: 1px solid #c99b46 !important;
    border-radius: 0 !important;
}

.grid th {
    background: #111111 !important;
    color: #ffffff !important;
    padding: 12px 8px !important;
    border-right: 1px solid #c99b46 !important;
    font-size: 8px !important;
}

.grid td {
    padding: 11px 8px !important;
    background: #ffffff !important;
    border-bottom: 1px solid #d8d8d8 !important;
}

.grid tr.alt td {
    background: #fffdf8 !important;
}

.totals {
    border: 1px solid #c99b46 !important;
    margin-top: 20px !important;
}

.totals td {
    padding: 9px !important;
}

.totals .ttc td {
    background: #111111 !important;
    color: #e6c27c !important;
    font-size: 12px !important;
    font-weight: bold;
}

.totals .ttc td.right {
    color: #f1d28b !important;
    font-size: 13px !important;
}

.box {
    background: #ffffff !important;
    border-left: 3px solid #c99b46 !important;
}

.watermark {
    opacity: 0.04 !important;
}

.hnb-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #111111;
    color: #ffffff;
    border-top: 7px solid #c99b46;
    padding: 15px 35px;
    font-size: 8px;
    line-height: 1.6;
}

.hnb-footer table {
    width: 100%;
}

.hnb-footer td {
    color: #ffffff;
    padding: 0 9px;
    vertical-align: middle;
}

.hnb-footer strong {
    color: #e3bc69;
    font-size: 11px;
}
'''

updated_style = original[style].replace(
    "</style>",
    premium_css + "\n</style>",
    1
)

try:
    style.write_text(updated_style, encoding="utf-8")
    header.write_text(new_header, encoding="utf-8")
    footer.write_text(new_footer, encoding="utf-8")

    for cmd in [
        ["php", "artisan", "view:clear"],
        ["php", "artisan", "view:cache"]
    ]:
        if subprocess.run(cmd).returncode != 0:
            raise RuntimeError("Blade cache failed")

except Exception as e:
    for f in files:
        shutil.copy2(backup / f.name, f)
    sys.exit("ERROR: " + str(e) + ". Backup restored.")

print("SUCCESS: HN BLOOM Premium PDF V3 installed.")
print("Backup:", backup)
print("No controllers, approvals or database changed.")
