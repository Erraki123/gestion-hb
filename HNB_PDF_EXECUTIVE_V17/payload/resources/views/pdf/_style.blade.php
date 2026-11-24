<style>
/* HN BLOOM EXECUTIVE EDITORIAL V17 — bounded A4 flow, DomPDF friendly */
@page { size:A4 portrait; margin:0; }
* { box-sizing:border-box; }
html,body { margin:0; padding:0; }
body { background:#fffdfa; color:#292318; font-family:DejaVu Sans,sans-serif; font-size:9.4px; line-height:1.5; }
table { width:100%; border-collapse:collapse; }
.right { text-align:right; } .center { text-align:center; }
.gold { color:#b6862d; } .muted { color:#786d5d; font-size:8px; }
.pagenum:before { content:counter(page); }
.hn-doc-header { margin:0 27px; border-bottom:2px solid #cc9c31; }
.hn-upper-edge { height:5px; background:#d4a332; }
.hn-masthead { margin:11px 0 12px; }
.hn-masthead td { border:0; }
.hn-brand-col { padding:3px 12px 3px 5px; }
.hn-main-logo { width:165px; height:auto; }
.hn-brand-fallback { color:#c6932f; font: bold 25px DejaVu Serif,serif; }
.hn-mantra-col { text-align:center; padding:7px 4px; border-left:1px solid #d5b16b !important; }
.hn-mantra-col div { font-family:DejaVu Serif,serif; letter-spacing:1.8px; font-size:7.2px; line-height:2.1; }
.hn-icons-col { padding:4px 1px 4px 10px; border-left:1px solid #dcc493 !important; }
.hn-icon-band { background:#f4ead4; border-top:2px solid #d0a348; border-bottom:1px solid #d8b877; }
.hn-icon-band td { width:25%; text-align:center; padding:8px 0 5px; border-right:1px solid #e5d6b9; }
.hn-icon-band td:last-child { border-right:0; }
.hn-icon-band img { width:31px; height:31px; }
.hn-services-title { margin-top:6px; color:#8c6a37; text-align:center; font-size:7.2px; letter-spacing:.45px; }
.hn-title-line { border-top:1px solid #e7d6b7; margin-top:10px; }
.hn-title-line td { vertical-align:middle; }
.hn-doc-title { font:bold 32px DejaVu Serif,serif; border-left:6px solid #d4a332; padding:11px 11px; color:#211b14; text-transform:uppercase; letter-spacing:.2px; }
.hn-meta-area { text-align:right; border-left:1px solid #e3cfac; padding:7px 10px; background:#fdf8ed; }
.hn-meta-label { font-size:6.6px; letter-spacing:1.15px; color:#927444; }
.hn-meta-ref { font-size:12.5px; font-weight:bold; color:#a66d19; padding-top:3px; }
.hn-meta-date { font-size:8px; color:#4d463f; padding-top:3px; }
.page { padding:15px 27px 72px; }
.card { padding:11px 12px; border:1px solid #d6b777; border-radius:3px; background:#fffefa; min-height:75px; }
.card-label { display:block; font-size:9.2px; font-weight:bold; color:#7d5217; background:#f0dfb9; padding:8px 9px; margin:-11px -12px 10px; border-bottom:1px solid #d2a758; text-transform:uppercase; letter-spacing:.55px; }
.card-label:before { content:'◆'; color:#c18f26; margin-right:5px; }
.card strong { font-size:10px; }
.hn-card-icon { width:19px; height:19px; vertical-align:middle; margin-right:7px; }
.hn-info-grid .card { border-top:3px solid #c79936; }
.info td { padding:6px 3px; vertical-align:top; }
.grid { margin-top:16px; border:1px solid #cda555; }
.grid thead { display:table-header-group; }
.grid tr { page-break-inside:avoid; }
.grid th { background:#d7b76e; color:#241c12; border-right:1px solid #e8d3a5; padding:11px 7px; text-transform:uppercase; font-size:8px; font-weight:bold; }
.grid th:last-child { border-right:0; }
.grid td { padding:10px 7px; border-bottom:1px solid #e8e0d3; border-right:1px solid #f0e8db; background:#fffefa; }
.grid td:last-child { border-right:0; }
.grid tr.alt td { background:#fbf6ec; }
.totals { border:1px solid #c6a060; margin-top:13px; }
.totals td { color:#2e2519; background:#fffefa; border-bottom:1px solid #e7dcc7; padding:9px 10px; font-size:9px; }
.totals .ttc td { background:#e7c980; color:#22180a; border-top:2px solid #bb8c2d; font-size:11.4px; font-weight:bold; padding:12px 9px; }
.totals .ttc td.right { font-size:12.8px; }
.stamp { display:inline-block; font-size:7px; font-weight:bold; letter-spacing:.4px; padding:5px 10px; border:1px solid currentColor; text-transform:uppercase; }
.stamp-paid { color:#477b58; }.stamp-late,.void { color:#a34540; }.stamp-draft { color:#b0852d; }
.void { font-size:10px; font-weight:bold; }
.hn-section-heading { margin-top:18px; margin-bottom:5px; color:#94681f; font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:.85px; border-bottom:1px solid #bd9b5c; padding-bottom:7px; }
.hn-section-heading img { width:18px; height:18px; vertical-align:middle; margin-right:7px; }
.hn-summary-row { margin-top:15px; }
.hn-summary-row td { vertical-align:top; }
.hn-words { margin-top:13px; border-left:3px solid #c9a05d; padding:10px 11px; background:#fbf5e9; font-size:8.7px; }
.hn-conditions { margin-top:12px; border:1px solid #decaa2; background:#fffdfa; padding:10px; }
.hn-conditions-title { color:#9d742c; font-size:9px; font-weight:bold; margin-bottom:6px; }
.hn-payments { margin-top:9px !important; }
.hn-payments th { background:#eddfbf !important; color:#2c2319 !important; border-top:1px solid #c99e4a; }
.box { border:1px solid #d8bd8b; border-left:4px solid #c79a42; background:#fbf5e9; padding:11px 13px; margin:15px 0; }
.big { font:bold 21px DejaVu Serif,serif; color:#a5771e; margin-top:4px; }
.sign { margin-top:19px; }
.sign td { width:50%; text-align:center; font-size:8px; color:#6c6355; }
.sign-line { width:70%; margin:28px auto 0; border-top:1px solid #c9a258; }
.fill td { padding-top:10px; }
.fill td.line { border-bottom:1px solid #c9b796; }
.footer.hn-footer { position:fixed; bottom:0; left:0; right:0; height:65px; padding:9px 27px 6px; background:#fdf9f1; border-top:3px solid #d4a332; font-size:7px; line-height:1.55; color:#514434; }
.hn-footer-line { height:1px; background:#e7d5ac; margin-bottom:7px; }
.hn-footer td { border:0; padding:0 9px; vertical-align:middle; }
.hn-footer td:first-child { padding-left:0; }
.hn-footer strong { font-size:9px; color:#9c6e1d; }
.hn-foot-contact { text-align:center; border-left:1px solid #e0c795 !important; }
.hn-foot-web { text-align:right; border-left:1px solid #e0c795 !important; }
.hn-small-icon { width:16px; height:16px; margin-right:4px; vertical-align:middle; }
.print-bar { padding:10px 27px; }
@media print { .print-bar { display:none; } }
</style>
