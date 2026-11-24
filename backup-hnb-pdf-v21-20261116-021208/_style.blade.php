<style>
/* HN BLOOM EXECUTIVE SIGNATURE V18 — fixed A4, elegant and print aware. */
@page { size:A4 portrait; margin:0; }
* {box-sizing:border-box;}
html,body {margin:0;padding:0;}
body {background:#fffefa; color:#302b26; font-family:DejaVu Sans,sans-serif; font-size:10px; line-height:1.54;}
table {width:100%;border-collapse:collapse;}
.right {text-align:right;} .center {text-align:center;}
.gold {color:#95734a;} .muted {color:#756f66;font-size:8.3px;}
.pagenum:before {content:counter(page);}

/* Distinctive editorial masthead, not a full-page image or oversized zakhrafa. */
.hn-doc-header {margin:0 25px;border-bottom:1.6px solid #b28b50;}
.hn-metal-accent {height:3px;background:#b28b50; margin:0 0 0 0;border-bottom:2px solid #b99c73;}
.hn-upper-edge {height:2px;background:#e0d1ba;}
.hn-masthead {margin:8px 0 6px;}
.hn-masthead td {border:0;}
.hn-brand-col {padding:3px 12px 3px 2px;}
.hn-main-logo {width:172px;height:auto;}
.hn-brand-fallback {font: bold 27px DejaVu Serif,serif;color:#a37d47;}
.hn-mantra-col {text-align:center;padding:5px 3px;border-left:1px solid #d8c7b0 !important;}
.hn-mantra-col div {font: 8.2px DejaVu Serif,serif;letter-spacing:1.65px;line-height:2.2;color:#50483d;}
.hn-icons-col {padding:5px 0 5px 12px;border-left:1px solid #d8c7b0 !important;}
.hn-icon-band {display:none;}
.hn-icon-band td {width:25%;text-align:center;padding:7px 0 4px;border-right:1px solid #e2d7c8;}
.hn-icon-band td:last-child {border-right:0;}
.hn-icon-band img {width:29px;height:29px;}
.hn-services-title {margin-top:6px;color:#786752;text-align:center;font-size:7.0px;letter-spacing:.35px;}
.hn-title-line {margin:13px 0 0;border-top:1px solid #e5d9cb;}
.hn-title-line td {vertical-align:middle;}
.hn-doc-title {font:bold 34px DejaVu Serif,serif;border-left:5px solid #9e7e56;padding:13px 12px 14px;color:#27241f;text-transform:uppercase;line-height:1.1;}
.hn-meta-area {text-align:right;border-left:1px solid #e3d7c8;padding:10px 12px;background:#fbf7ef;}
.hn-meta-label {font-size:7px;letter-spacing:1.1px;color:#75644d;}
.hn-meta-ref {font-size:14px;font-weight:bold;color:#5b4a36;padding-top:5px;}
.hn-meta-date {font-size:8.3px;color:#4b453c;padding-top:5px;}

/* Data section; ensure all original invoice fields remain. */
.page {padding:15px 27px 84px;}
.card {background:#fff;border:1px solid #d0bd9f;border-radius:4px;padding:11px 13px;min-height:93px;color:#3b352d;font-size:9.7px;line-height:1.65;}
.card-label {display:block;background:#eee9e1;color:#41382d;border-bottom:1px solid #c9b293;padding:9px 12px;margin:-11px -13px 9px;text-transform:uppercase;letter-spacing:1.0px;font-size:9.2px;font-weight:bold;}
.card-label:before {content:'';}
.card strong {font-size:11.3px;color:#211f1b;}
.hn-card-icon {width:20px;height:20px;vertical-align:middle;margin-right:8px;}
.hn-info-grid .card {border-top:3px solid #a88a64;}
.info td {padding:6px 3px;vertical-align:top;}

/* Corporate catalogue table: readable, calm contrast, no bright yellow fills. */
.grid {margin-top:17px;border:1px solid #cabbab;}
.grid thead {display:table-header-group;}
.grid tr {page-break-inside:avoid;}
.grid th {background:#45403a;color:#fffdf8;border-right:1px solid #72685c;border-bottom:2px solid #ae9169;padding:11px 8px;font-size:8.1px;font-weight:bold;letter-spacing:.3px;text-transform:uppercase;}
.grid th:last-child {border-right:0;}
.grid td {padding:10px 8px;background:#fffefa;color:#282521;border-right:1px solid #eee6da;border-bottom:1px solid #e8e1d6;}
.grid td:last-child {border-right:0;}
.grid tr.alt td {background:#faf8f4;}

/* Monetary figures strongly structured but without saturated background. */
.totals {margin-top:15px;border:1px solid #bfa988;}
.totals td {background:#fffefa;border-bottom:1px solid #e7dfd2;padding:9px 10px;font-size:9.4px;color:#322e28;}
.totals .ttc td {background:#e8d6b6;color:#2d2720;border-top:2px solid #a58862;border-bottom:1px solid #a58862;font-size:12px;font-weight:bold;padding:13px 10px;}
.totals .ttc td.right {font-size:14px;}
.stamp {display:inline-block;font-size:7.4px;font-weight:bold;letter-spacing:.4px;padding:5px 10px;border:1px solid currentColor;text-transform:uppercase;}
.stamp-paid {color:#467557;}.stamp-late,.void {color:#a34540;}.stamp-draft {color:#9b7544;}
.void {font-size:10px;font-weight:bold;}

/* Actual data sections: receipts, payments, words, notes. */
.hn-section-heading {margin-top:18px;margin-bottom:5px;color:#544335;font-size:11px;font-weight:bold;text-transform:uppercase;letter-spacing:1.1px;border-bottom:2px solid #a78c68;padding-bottom:8px;}
.hn-section-heading img {width:19px;height:19px;vertical-align:middle;margin-right:7px;}
.hn-summary-row {margin-top:15px;}
.hn-summary-row td {vertical-align:top;}
.hn-words {margin-top:13px;border-left:3px solid #9e8160;padding:12px 14px;background:#f7f4ef;font-size:9.5px;}
.hn-conditions {margin-top:13px;border:1px solid #d8c7ad;background:#fffefa;padding:13px 15px;font-size:9.6px;}
.hn-conditions-title {color:#58452f;font-size:10px;font-weight:bold;margin-bottom:7px;}
.hn-payments {margin-top:8px !important;}
.hn-payments th {background:#ece5da !important;color:#3d3228 !important;border-top:1px solid #baa07c;}
.box {border:1px solid #ccb99b;border-left:3px solid #9c7e5a;background:#f9f6f1;padding:12px 14px;margin:15px 0;}
.big {font:bold 21px DejaVu Serif,serif;color:#8d6c47;margin-top:4px;}
.sign {margin-top:20px;}.sign td {width:50%;text-align:center;font-size:8px;color:#625d55;}
.sign-line {width:68%;margin:29px auto 0;border-top:1px solid #ae9672;}
.fill td {padding-top:10px;}.fill td.line {border-bottom:1px solid #c1b29b;}

/* Short-doc decorative closure: in flow and under 90px high; no full-page PNG. */
.hn-visual-closure {margin:19px 0 0;height:77px;position:relative;page-break-inside:avoid;overflow:hidden;}
.hn-visual-closure img {width:100%;height:65px;opacity:.55;display:block;}
.hn-closure-label {text-align:center;letter-spacing:2px;color:#a38a68;font: bold 10px DejaVu Serif,serif;}
.hn-closure-label span {font: 7px DejaVu Sans,sans-serif;letter-spacing:1px;color:#86796c;margin-left:8px;}

/* Footer is light, compact and legible, never a black rectangle. */
.footer.hn-footer {position:fixed;bottom:0;left:0;right:0;height:78px;padding:17px 27px 6px;background:#fffefa;border-top:2px solid #ac8952;font-size:7.5px;line-height:1.5;color:#4b443a;}
.hn-footer-line {height:1px;background:#d4c2a4;margin-bottom:7px;}
.hn-footer td {border:0;padding:0 10px;vertical-align:middle;}
.hn-footer td:first-child {padding-left:0;}
.hn-footer strong {font-size:9.5px;color:#6f5435;}
.hn-foot-contact {text-align:center;border-left:1px solid #d5c5ad !important;}
.hn-foot-web {text-align:right;border-left:1px solid #d5c5ad !important;}
.hn-small-icon {width:16px;height:16px;margin-right:4px;vertical-align:middle;}
.print-bar {padding:10px 28px;}
@media print {.print-bar{display:none !important;}}

/* HYBRID LUXURY V19: scoped, bounded PNG assets + print-safe CSS */
.hnb-hybrid-ribbon {width:100%;height:52px;overflow:hidden;position:relative;margin-top:-8px;}
.hnb-hybrid-ribbon img {height:52px;width:100%;display:block;}
.hn-icons-col {padding:0 0 3px 7px !important;border-left:0 !important;}
.hn-services-title {font-size:7.8px !important;color:#69583c !important;letter-spacing:.5px;margin-top:4px;line-height:1.5;}
.hn-doc-title {border-left:0 !important;border-bottom:3px solid #b28b50;padding:10px 14px 11px !important;background:#f6efe3;color:#211d18 !important;}
.hn-meta-area {border:1px solid #ddc69c !important;background:#fffdfa !important;padding:9px 12px !important;}
.hn-meta-ref {font-size:13px !important;color:#825d2d !important;}
.card {border:1px solid #d3b989 !important;border-radius:3px !important;}
.card-label {background:#f0e2cb !important;border-bottom:1px solid #d2b483 !important;color:#5f4625 !important;}
.hn-info-grid .card {border-top:2px solid #ae8750 !important;}
.grid th {background:#51483e !important;color:#fff9ef !important;border-bottom:2px solid #b79760 !important;}
.grid td {background:#fffefa !important;}
.totals .ttc td {background:#e6ce9d !important;color:#292017 !important;}
.hn-conditions {background:#fffdfa !important;}
.footer.hn-footer {background:#fffefa !important;}
/* bounded footer wave IMAGE: only inside fixed footer, never a standalone flow element */
.hn-footer {background-image:url('{{ public_path('images/hnb-pdf-hybrid/bottom-wave.png') }}') !important;
    background-repeat:no-repeat !important;background-position:center top !important;background-size:100% 25px !important;}
/* Repeat table headings and avoid row splitting */
.grid thead {display:table-header-group;}
.grid tr {page-break-inside:avoid;}


/* HNB HYBRID BRANDED V20 -- ONLY HEADER AND FOOTER LAYOUT */
.hnb-v20-header { margin:0 23px !important; padding:0 !important; border-bottom:1.5px solid #b99562 !important; }
.hnb-v20-visual { margin:0; height:101px; width:100%; overflow:hidden; }
.hnb-v20-visual img { display:block; width:100%; height:101px; }
.hnb-v20-title { margin:0 !important; border-top:1px solid #d7c5a9 !important; }
.hnb-v20-title .hn-doc-title { background:#f8f3ea !important; font:bold 25px DejaVu Serif,serif !important; padding:10px 12px !important; border-bottom:0 !important; border-left:3px solid #a98244 !important; }
.hnb-v20-title .hn-meta-area { padding:7px 10px !important; border:0 !important; border-left:1px solid #ddc9ac !important; background:#fffdfa !important; }
.hnb-v20-title .hn-meta-ref { font-size:11px !important; padding-top:2px !important; }
.hnb-v20-title .hn-meta-date { font-size:7.6px !important; padding-top:2px !important; }
.page { padding-bottom:103px !important; }
.footer.hn-footer.hnb-v20-footer {
    position:fixed !important; bottom:0 !important; left:0 !important; right:0 !important;
    height:94px !important; padding:10px 24px 9px !important;
    background:#fffdf9 !important; background-image:none !important;
    border-top:1.5px solid #b88d52 !important;
    line-height:1.35 !important; color:#3d362b !important;
    font-size:7.3px !important; overflow:hidden;
}
.hnb-v20-footer-art { position:absolute; width:100%; height:87px; left:0; bottom:0; z-index:0; }
.hnb-v20-footer-data { position:relative; z-index:2; table-layout:fixed; width:100%; }
.hnb-v20-footer-data td { border:0 !important; text-align:left !important; padding:0 5px !important;
    word-wrap:break-word; overflow-wrap:break-word; vertical-align:top; }
.hnb-v20-footer-data td:nth-child(2) { border-left:1px solid #dac8aa !important; }
.hnb-v20-footer-data td:nth-child(3) { border-left:1px solid #dac8aa !important; }
.hnb-v20-footer-data strong { font-size:8.5px !important; color:#8f6c37 !important; }
.hnb-v20-footer-data div { margin-top:2px; }
.hnb-v20-legal { font-size:6.2px; }
.hnb-v20-identifiers { position:relative; z-index:2; border-top:1px solid #e6dccc;
    margin-top:5px; padding-top:3px; color:#6b5b45; font-size:6.4px; }

</style>
