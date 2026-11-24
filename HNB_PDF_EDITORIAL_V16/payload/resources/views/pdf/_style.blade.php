<style>
/* HNB EDITORIAL LUXURY V16 - DomPDF friendly, no full-height decorative images */
@page { size: A4 portrait; margin: 0; }
* { box-sizing: border-box; }
html,body { margin:0; padding:0; }
body { font-family: DejaVu Sans, sans-serif; font-size:9px; line-height:1.5; color:#24201a; background:#fffefa; }
table { border-collapse:collapse; width:100%; }
.right { text-align:right; } .center { text-align:center; }
.muted { color:#72685b; font-size:8px; }
.gold { color:#a77823; }
.pagenum:before { content:counter(page); }
.hn-doc-header { margin:0 27px; border-bottom:2px solid #d4a332; padding-bottom:0; position:relative; }
.hn-upper-edge { height:6px; background:#d4a332; border-bottom:2px solid #f1e3bf; }
.hn-brand-row { margin-top:9px; margin-bottom:11px; }
.hn-brand-row td { vertical-align:middle; border:0; }
.hn-brand-col { padding:4px 9px 2px 4px; }
.hn-main-logo { width:155px; height:auto; }
.hn-brand-fallback { font:bold 25px DejaVu Serif,serif; color:#d4a332; }
.hn-mantra-col { text-align:center; border-left:1px solid #d8b875 !important; padding:9px 2px; }
.hn-mantra-col div { font:7.5px DejaVu Serif,serif; letter-spacing:2px; line-height:2.05; }
.hn-brand-services-col { border-left:1px solid #e0c992 !important; padding:6px 3px 3px 10px; }
.hn-icon-band { width:100%; border:1px solid #d3ad65; background:#fbf4e5; }
.hn-icon-band td { width:25%; height:37px; text-align:center; vertical-align:middle; border-right:1px solid #ecdabb; }
.hn-icon-band img { width:25px; height:25px; }
.hn-services-title { margin-top:6px; font-size:7px; letter-spacing:.6px; color:#8e672d; text-align:center; }
.hn-document-row { margin-top:6px; border-top:1px solid #ead6ab; }
.hn-document-row td { vertical-align:middle; }
.hn-doc-title { font:bold 29px DejaVu Serif,serif; color:#1e1a14; letter-spacing:1px; text-transform:uppercase; padding:9px 6px 9px 12px; border-left:5px solid #d4a332; }
.hn-meta-area { text-align:right; border-left:1px solid #e2cfaa; padding:7px 8px 6px; }
.hn-meta-label { font-size:6.6px; letter-spacing:1.5px; color:#8b704c; }
.hn-meta-ref { font-size:12px; font-weight:bold; color:#a47426; padding-top:2px; }
.hn-meta-date { font-size:7.5px; color:#595144; padding-top:3px; }
.page { padding:17px 27px 65px; }
.card { border:1px solid #d2b477; border-left:3px solid #d4a332; background:#fffdfa; padding:11px 12px; min-height:67px; }
.card-label { display:block; background:#f4e7ca; border-bottom:1px solid #d5ae5e; padding:6px 8px; margin:-11px -12px 9px; font:bold 8px DejaVu Sans,sans-serif; color:#85601c; text-transform:uppercase; letter-spacing:1.1px; }
.card-label:before { content:'◆'; color:#c2912a; margin-right:7px; }
.card strong { color:#211b12; font-size:10px; }
.info td { padding:5px 3px; vertical-align:top; }
.stamp { display:inline-block; border:1px solid currentColor; padding:4px 8px; font-size:7px; font-weight:bold; text-transform:uppercase; }
.stamp-paid { color:#4d714d; } .stamp-late,.void { color:#a3473f; } .stamp-draft { color:#a9792a; }
.void { font-weight:bold; font-size:10px; }
.grid { margin-top:16px; border:1px solid #cda45e; border-top:3px solid #d4a332; }
.grid thead { display:table-header-group; }
.grid tr { page-break-inside:avoid; }
.grid th { background:#e8d6b2; color:#2d251a; border-right:1px solid #c9a05f; font-weight:bold; font-size:8px; text-transform:uppercase; letter-spacing:.4px; padding:10px 7px; }
.grid td { background:#fffefa; color:#2c251b; border-bottom:1px solid #e6dac8; border-right:1px solid #f0e5d3; padding:9px 7px; vertical-align:middle; }
.grid tr.alt td { background:#faf5eb; }
.grid td:last-child, .grid th:last-child { border-right:0; }
.totals { margin-top:16px; border:1px solid #c7a262; }
.totals td { padding:9px 9px; border-bottom:1px solid #e9dfce; background:#fffefa; color:#2b231b; font-size:9px; }
.totals .ttc td { background:#ead094; border-top:2px solid #bd8b32; color:#20190e; font-size:12px; font-weight:bold; padding:12px 9px; }
.totals .ttc td.right { font-size:13px; }
.box { background:#fcf6e9; padding:11px 13px; border:1px solid #d7bb84; border-left:3px solid #d4a332; margin:15px 0; }
.big { font:bold 20px DejaVu Serif,serif; color:#a77a27; margin-top:4px; }
.sign { margin-top:22px; }
.sign td { text-align:center; width:50%; font-size:8px; color:#746650; }
.sign-line { margin:34px auto 0; width:70%; border-top:1px solid #c29955; }
.fill td { padding-top:9px; font-size:9px; }
.fill td.line { border-bottom:1px solid #c9b798; }
.footer.hn-footer { position:fixed; bottom:0; left:0; right:0; height:64px; padding:10px 27px 7px; border-top:3px solid #d4a332; background:#fcf8f0; font-size:7px; line-height:1.55; color:#494034; }
.hn-footer td { border:0; padding:0 10px; }
.hn-footer td:first-child { padding-left:0; }
.hn-footer strong { font-size:9px; letter-spacing:.4px; color:#a37424; }
.hn-foot-contact { text-align:center; border-left:1px solid #dec38a !important; }
.hn-foot-web { text-align:right; border-left:1px solid #dec38a !important; }
.hn-small-icon { width:13px; height:13px; vertical-align:middle; margin-right:4px; }
.print-bar { padding:10px 27px; }
@media print { .print-bar { display:none; } }
</style>
