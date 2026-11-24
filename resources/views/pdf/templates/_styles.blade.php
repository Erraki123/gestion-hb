<style>
@page { size:A4; margin: 12mm 12mm 31mm 12mm; }
* { box-sizing:border-box; }
body { margin:0; font-family: DejaVu Sans, sans-serif; color:#25231f; font-size:9px; line-height:1.45; background:#fffdfa; }
.page-shell { position:relative; }
table { width:100%; border-collapse:collapse; }
.topline td { height:5px; background:#c49a54; }
.masthead { margin:0 0 15px; border-bottom:1px solid #e4d4b5; }
.masthead td { padding:12px 7px 11px; vertical-align:middle; }
.brand-cell { width:25%; }
.brand-logo { width:105px; max-height:62px; object-fit:contain; }
.brand-fallback span { display:block; font:40px Georgia,serif; color:#b78a45; }
.brand-fallback small { color:#8f6a34; letter-spacing:2px; }
.brand-center { text-align:center; width:48%; }
.brand-motto { color:#4a463f; font-size:7px; letter-spacing:2px; font-weight:bold; }
.brand-sub { color:#a17b40; font-size:7px; letter-spacing:1px; margin-top:5px; }
.brand-contact { text-align:right; width:27%; font-size:7px; color:#69645a; line-height:1.8; }
.title-row { margin:0 0 14px; }
.title-row td { vertical-align:bottom; }
.document-title { font: bold 30px Georgia,serif; letter-spacing:1px; color:#191816; }
.document-number { color:#a67a39; font-size:8px; letter-spacing:1.1px; font-weight:bold; margin-top:3px; }
.date-cell { width:34%; text-align:right; }
.date-cell div { margin:2px 0; }
.label { display:block; color:#a17b40; font-size:7px; font-weight:bold; letter-spacing:1.2px; margin-bottom:3px; }
.date-cell strong { font-size:9px; color:#24211d; }
.status { display:inline-block; margin-top:5px; border:1px solid #c7a66e; padding:3px 8px; color:#9b7338; font-size:7px; font-weight:bold; }
.client-row { margin-bottom:15px; }
.client-row td { width:50%; vertical-align:top; padding:10px 12px; border:1px solid #e8dcc6; background:#fffefa; }
.client-card { border-left:3px solid #c49a54 !important; }
.meta-card { border-left:3px solid #e6d6b7 !important; }
.client-card strong { font-size:11px; display:block; margin:3px 0; }
.client-card div:not(.label), .meta-card div:not(.label) { font-size:8px; color:#514d45; margin:2px 0; }
.section-heading { font-size:8px; color:#9b7338; font-weight:bold; letter-spacing:1.5px; margin:0 0 7px; }
.items { border:1px solid #c7a66e; }
.items th { background:#e9d2a4; color:#332b1e; font-size:7px; letter-spacing:.4px; padding:9px 6px; text-align:right; border-right:1px solid #d8bd8b; }
.items th.designation { text-align:left; width:38%; }
.items th.last { border-right:0; }
.items td { padding:10px 6px; border-bottom:1px solid #e8dece; border-right:1px solid #eee7dc; font-size:8px; vertical-align:top; }
.items tbody tr:nth-child(even) td { background:#fcf8f0; }
.items td.designation { width:38%; }
.items td.designation strong { display:block; font-size:8px; }
.items td.designation small { display:block; color:#777166; font-size:7px; }
.items td.num { text-align:right; white-space:nowrap; }
.items td.last { border-right:0; font-weight:bold; }
.empty-row { text-align:center; color:#8c8579; padding:22px !important; }
.lower-row { margin-top:15px; }
.lower-row td { vertical-align:top; }
.notes-cell { width:55%; padding:6px 18px 0 3px; }
.totals-cell { width:45%; }
.thankyou { color:#28231d; font:italic 17px Georgia,serif; margin:5px 0 2px; }
.thankyou-brand { color:#a17b40; letter-spacing:3px; font-size:8px; font-weight:bold; }
.notes { margin-top:10px; color:#625d53; font-size:8px; }
.terms { margin-top:8px; color:#625d53; font-size:7px; }
.totals { border:1px solid #dfcda9; }
.totals td { padding:7px 9px; border-bottom:1px solid #eee4d2; font-size:8px; }
.totals .amount { text-align:right; font-weight:bold; white-space:nowrap; }
.totals .grand-total td { background:#e8cb91; color:#241f17; border:0; font-size:10px; font-weight:bold; padding:10px 9px; }
.amount-words { margin-top:6px; text-align:right; color:#83765f; font-size:7px; font-style:italic; }
.payment-box { margin-top:10px; padding:8px 10px; border:1px solid #e6d7ba; background:#fcf8ef; font-size:8px; }
.signature-row { margin-top:22px; }
.signature-row td { width:50%; text-align:center; padding:0 15px; vertical-align:top; }
.signature-title { color:#6d665b; font-size:8px; font-weight:bold; }
.signature-line { height:35px; border-bottom:1px solid #c6a36b; margin-bottom:5px; }
.signature-row small { color:#91897b; font-size:7px; }
.footer { position:fixed; bottom:-25mm; left:0; right:0; height:23mm; border-top:1px solid #c49a54; padding:6px 0 0; background:#fffdfa; }
.footer td { vertical-align:middle; }
.qr-cell { width:18%; }
.qr { width:64px; height:64px; }
.footer-company { width:58%; font-size:6.5px; line-height:1.6; color:#5b564c; }
.footer-company strong, .footer-web strong { color:#9c7339; font-size:7px; letter-spacing:.6px; }
.footer-web { width:24%; text-align:right; font-size:6.5px; color:#777064; }
.theme-prestige .topline td { height:9px; background:#201d18; border-bottom:3px solid #c49a54; }
.theme-prestige .masthead { border-bottom:2px solid #c49a54; }
.theme-prestige .document-title { color:#9d7338; }
.theme-prestige .items th { background:#29251f; color:#f3e7d1; border-color:#62513a; }
.theme-prestige .client-row td { background:#fbf7ef; }
.theme-prestige .totals .grand-total td { background:#c49a54; color:#fff; }
.theme-editorial .masthead { border-bottom:0; border-left:4px solid #c49a54; padding-left:8px; }
.theme-editorial .title-row { border-top:1px solid #e7d8bd; border-bottom:1px solid #e7d8bd; padding:10px 0; }
.theme-editorial .document-title { font-size:27px; }
.theme-editorial .client-row td { border:0; border-top:1px solid #d9c39d; border-bottom:1px solid #d9c39d; }
.theme-editorial .items th { background:#f2e6d0; border-bottom:2px solid #b88a45; }
.theme-editorial .totals { border:0; border-top:2px solid #b88a45; }
.theme-editorial .totals .grand-total td { background:#29251f; color:#f3e7d1; }
</style>