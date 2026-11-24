from pathlib import Path
from datetime import datetime
import shutil
import subprocess
import sys

root = Path("resources/views/pdf")
css_file = root / "_style.blade.php"
header_file = root / "_header.blade.php"

if not css_file.exists() or not header_file.exists():
    sys.exit("PDF files missing. No changes.")

original_css = css_file.read_text(encoding="utf-8-sig")
original_header = header_file.read_text(encoding="utf-8-sig")

marker = "HNB_PREMIUM_REAL_V4"

if marker in original_css:
    print("Design already installed.")
    sys.exit(0)

if original_css.count("</style>") != 1:
    sys.exit("Unexpected CSS structure. No changes.")

backup = Path(
    "backup-pdf-v4-" +
    datetime.now().strftime("%Y%m%d-%H%M%S")
)
backup.mkdir()

shutil.copy2(css_file, backup / "_style.blade.php")
shutil.copy2(header_file, backup / "_header.blade.php")

header = r'''
@php
    $web = $web ?? false;
    $logoPath = public_path('images/logo.png');
    $logoSrc = $web
        ? asset('images/logo.png')
        : $logoPath;
@endphp

<div class="premium-topline"></div>

<div class="premium-header">
    <table>
        <tr>
            <td style="width:32%;">
                @if(file_exists($logoPath) || $web)
                    <img src="{{ $logoSrc }}"
                         class="premium-logo"
                         alt="HN BLOOM">
                @else
                    <div class="premium-brand">
                        HN BLOOM
                    </div>
                @endif
            </td>

            <td style="width:28%; text-align:center;">
                <div class="premium-slogan">
                    INNOVATION<br>
                    CONFIANCE<br>
                    EXCELLENCE
                </div>
            </td>

            <td style="width:40%; text-align:right;">
                <div class="premium-title">
                    {{ $title }}
                </div>

                <div class="premium-ref">
                    {{ $ref }}
                </div>

                <div class="premium-date">
                    {{ $date }}
                </div>
            </td>
        </tr>
    </table>
</div>
'''

css = r'''
/* HNB_PREMIUM_REAL_V4 */

@page {
    size: A4 portrait;
    margin: 0;
}

body {
    background: #ffffff !important;
    color: #24211e !important;
    font-family: DejaVu Sans, sans-serif;
    font-size: 9px;
    line-height: 1.5;
    padding-bottom: 78px;
}

.watermark {
    display: none !important;
}

.premium-topline {
    margin: 0 38px;
    height: 8px;
    border-top: 3px solid #b58a43;
    border-bottom: 1px solid #dbc79e;
}

.premium-header {
    margin: 0 38px;
    padding: 23px 0 18px;
    border-bottom: 2px solid #c29a55;
}

.premium-header td {
    vertical-align: middle;
    border: 0;
}

.premium-logo {
    width: 165px;
    height: auto;
}

.premium-brand {
    font-family: DejaVu Serif, serif;
    font-size: 27px;
    color: #b28a48;
    font-weight: bold;
}

.premium-slogan {
    color: #4b4338;
    font-size: 9px;
    letter-spacing: 2px;
    line-height: 2;
    border-left: 1px solid #d7bd8a;
    padding-left: 10px;
}

.premium-title {
    color: #1d1a17;
    font-family: DejaVu Serif, serif;
    font-size: 26px;
    font-weight: bold;
    line-height: 1.15;
}

.premium-ref {
    display: inline-block;
    margin-top: 11px;
    padding-top: 6px;
    border-top: 2px solid #b58a43;
    color: #967039;
    font-size: 11px;
    font-weight: bold;
}

.premium-date {
    margin-top: 7px;
    font-size: 8px;
    color: #595248;
}

/* Layout */

.page {
    padding: 13px 38px 20px !important;
}

.page > table:first-child {
    margin-top: 8px !important;
}

/* Client and document information */

.card {
    background: #ffffff !important;
    border: 1px solid #d9c49d !important;
    border-left: 4px solid #b88b43 !important;
    border-radius: 5px;
    padding: 13px !important;
}

.card-label {
    color: #9d753c !important;
    font-size: 8px !important;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding-bottom: 6px;
    border-bottom: 1px solid #ebe1d0;
}

.card strong {
    color: #1f1c19;
}

/* Product table */

.grid {
    width: 100%;
    margin-top: 18px !important;
    border: 1px solid #bea06a !important;
    border-radius: 4px;
}

.grid thead {
    display: table-header-group;
}

.grid tr {
    page-break-inside: avoid;
}

.grid th {
    background: #eee5d5 !important;
    color: #211d19 !important;
    border-bottom: 2px solid #ba914f !important;
    border-right: 1px solid #d6c5a6 !important;
    padding: 11px 8px !important;
    font-size: 8px !important;
    font-weight: bold;
    text-transform: uppercase;
}

.grid td {
    background: #ffffff !important;
    color: #27231f !important;
    border-bottom: 1px solid #e8dfd1 !important;
    border-right: 1px solid #f0e9de !important;
    padding: 10px 8px !important;
}

.grid tr.alt td {
    background: #fcfaf6 !important;
}

/* Totals */

.totals {
    margin-top: 17px !important;
    border: 1px solid #c3a36e !important;
    border-radius: 4px;
}

.totals td {
    background: #ffffff !important;
    color: #29241f !important;
    padding: 9px !important;
    border-bottom: 1px solid #e5dac7;
}

.totals .ttc td {
    background: #e8d5ac !important;
    color: #201b15 !important;
    border-top: 2px solid #b48a46;
    font-size: 12px !important;
    font-weight: bold;
    padding: 12px 9px !important;
}

.totals .ttc td.right {
    color: #201b15 !important;
    font-size: 13px !important;
}

/* Conditions, payment, signatures */

.box {
    background: #ffffff !important;
    border: 1px solid #ddc9a4 !important;
    border-left: 4px solid #b68b46 !important;
    padding: 13px !important;
    margin-top: 17px !important;
}

.big {
    color: #926d36 !important;
    font-size: 21px !important;
    font-weight: bold;
}

.sign {
    margin-top: 24px !important;
    page-break-inside: avoid;
}

.sign-line {
    border-top: 1px solid #b58d50 !important;
}

.stamp {
    padding: 5px 10px !important;
    border: 1px solid currentColor;
    border-radius: 3px;
}

/* Print-friendly footer */

.footer {
    background: #ffffff !important;
    color: #36312b !important;
    border-top: 2px solid #ba9251 !important;
    padding: 13px 38px 15px !important;
    font-size: 7px !important;
}

.footer strong {
    color: #99713b !important;
}

@media print {
    .print-bar {
        display: none !important;
    }
}
'''

updated = original_css.replace(
    "</style>",
    css + "\n</style>",
    1
)

try:
    css_file.write_text(updated, encoding="utf-8")
    header_file.write_text(header, encoding="utf-8")

    for command in [
        ["php", "artisan", "view:clear"],
        ["php", "artisan", "view:cache"]
    ]:
        result = subprocess.run(command)
        if result.returncode != 0:
            raise RuntimeError("Blade validation failed")

except Exception as error:
    shutil.copy2(
        backup / "_style.blade.php",
        css_file
    )
    shutil.copy2(
        backup / "_header.blade.php",
        header_file
    )
    sys.exit(
        "ERROR: " + str(error) +
        ". Original design restored."
    )

print("SUCCESS: HN BLOOM Premium PDF integrated.")
print("Backup:", backup)
print("No CRM data or workflow modified.")
