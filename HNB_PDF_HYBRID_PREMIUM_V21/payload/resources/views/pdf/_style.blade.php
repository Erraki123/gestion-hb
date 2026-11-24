<style>
/* HN BLOOM V21 | Unified premium print design. Replaces stacked legacy overrides. */
@page { size:A4 portrait; margin:0; }
* { box-sizing:border-box; }
html,body { margin:0; padding:0; }
body { font:10.4px/1.52 DejaVu Sans,sans-serif; color:#2c2926; background:#fffefa; }
table { border-collapse:collapse; width:100%; }
.right { text-align:right; } .center { text-align:center; }
.gold { color:#9f7745; } .muted { color:#736d65; font-size:8.4px; }
.pagenum:before { content: counter(page); }

/* Full artistic header image. The asset includes the complete HN BLOOM logo. */
.hn-doc-header { margin:0 28px; padding:0; border-bottom:1px solid #b9a079; }
.hn-visual-header { height:108px; width:100%; overflow:hidden; padding:0; margin:0; }
.hn-visual-header img { display:block; width:100%; height:106px; }
.hn-title-line { margin:0; border-top:1px solid #d8c8b0; background:#fffefa; }
.hn-title-line td { vertical-align:middle; }
.hn-title-main { border-left:4px solid #a78555; border-bottom:2px solid #ad8d59; background:#f5f0e7; }
.hn-doc-title { font:bold 29px/1.05 DejaVu Serif,serif; color:#2b2520; letter-spacing:.5px; text-transform:uppercase; padding:11px 13px; }
.hn-meta-area { text-align:right; padding:10px 13px; background:#fffefa; border-left:1px solid #d8c9b4; }
.hn-meta-label { font-size:7.5px; color:#927d61; letter-spacing:1.2px; font-weight:bold; }
.hn-meta-ref { font:bold 13px DejaVu Sans,sans-serif; color:#6e5033; padding-top:4px; }
.hn-meta-date { font-size:8.5px; color:#4b443d; margin-top:4px; }

/* Body, full-width paper geometry and enhanced information cards. */
.page { padding:18px 28px 125px; position:relative; }
.card { background:#fffdfa; border:1px solid #ccb89a; border-radius:4px; border-left:3px solid #a78a5d; padding:13px 14px; min-height:92px; font-size:9.8px; line-height:1.7; color:#362f27; }
.card-label { display:block; margin:-13px -14px 11px; padding:8px 11px; background:#eae1d3; border-bottom:1px solid #cbb595; color:#44372b; font-size:9.2px; letter-spacing:.65px; text-transform:uppercase; font-weight:bold; }
.card strong { color:#211e1b; font-size:11.5px; }
.hn-card-icon { width:19px; height:19px; margin-right:7px; vertical-align:middle; }
.hn-info-grid td { vertical-align:top; }
.info td { padding:6px 4px; vertical-align:top; }

/* Larger product table but limited ink usage. */
.grid { margin-top:17px; border:1px solid #c3ad8c; border-bottom:2px solid #a8895d; }
.grid thead { display:table-header-group; }
.grid tr { page-break-inside:avoid; }
.grid th { background:#54483a; color:#fffaf2; border-right:1px solid #897660; border-bottom:2px solid #b49a73; padding:10px 8px; font-size:8.4px; font-weight:bold; letter-spacing:.2px; text-transform:uppercase; }
.grid th:last-child { border-right:0; }
.grid td { background:#fffefa; border-right:1px solid #e7dac7; border-bottom:1px solid #e9dfd0; padding:10px 8px; font-size:9.1px; }
.grid td:last-child { border-right:0; }
.grid tr.alt td { background:#fbf8f2; }

/* Totals: premium, restrained highlighting. */
.totals { margin-top:15px; border:1px solid #c3aa87; background:#fff; }
.totals td { padding:9px 10px; background:#fffefa; color:#2f2923; border-bottom:1px solid #e6d9c8; font-size:9.1px; }
.totals .ttc td { border-top:2px solid #ae8b57; background:#e9ddc9; color:#2d251b; padding:11px 10px; font-size:11.5px; font-weight:bold; }
.totals .ttc td.right { font-size:13px; }

/* Semantically existing conditional sections. */
.stamp { display:inline-block; padding:4px 8px; border:1px solid currentColor; font-size:7.4px; letter-spacing:.6px; font-weight:bold; }
.stamp-paid { color:#477458; } .stamp-late,.void { color:#9f4c43; } .stamp-draft { color:#997442; }
.void { font-weight:bold; font-size:10px; }
.hn-section-heading { color:#675037; font-size:11px; letter-spacing:.7px; text-transform:uppercase; font-weight:bold; margin:18px 0 5px; border-bottom:1.5px solid #b89d75; padding-bottom:8px; }
.hn-section-heading img { width:17px; height:17px; vertical-align:middle; margin-right:7px; }
.hn-payments { margin-top:6px!important; }
.hn-payments th { background:#e7ddcf!important; color:#352d24!important; border-top:1px solid #bda681; }
.hn-words { margin:15px 0 0; border-left:3px solid #a2855a; padding:11px 13px; background:#f6f0e6; font-size:9.5px; }
.hn-conditions { margin-top:15px; padding:12px 14px; border:1px solid #d1bfa6; background:#fffdfa; }
.hn-conditions-title { font-size:10.5px; font-weight:bold; color:#56412b; margin-bottom:6px; }
.box { margin:15px 0; border:1px solid #cdb99d; border-left:3px solid #a2865d; padding:13px 14px; background:#fcf9f4; }
.big { font:bold 21px DejaVu Serif,serif; color:#83613b; margin-top:5px; }
.sign { margin-top:19px; } .sign td { width:50%; text-align:center; color:#60574b; font-size:8.1px; }
.sign-line { border-top:1px solid #a98b60; width:67%; margin:29px auto 0; }
.fill td { padding-top:10px; } .fill td.line { border-bottom:1px solid #c7b697; }
.hn-visual-closure { margin:18px 0 0; height:60px; overflow:hidden; page-break-inside:avoid; }
.hn-visual-closure img { width:100%; height:50px; opacity:.40; }
.hn-closure-label { color:#9a805c; text-align:center; font:bold 10px DejaVu Serif,serif; letter-spacing:1px; }
.hn-closure-label span { font:7px DejaVu Sans,sans-serif; letter-spacing:.5px; color:#796e60; }

/* FIX: footer contact details laid out ABOVE the ornament, not under it. */
.footer.hn-footer { position:fixed; bottom:0; left:0; right:0; height:116px; padding:10px 28px 24px; margin:0; background:#fffefa; color:#423a31; font-size:8px; line-height:1.45; border-top:2px solid #a78755; overflow:hidden; }
.hn-foot-company-row { margin:0; padding-bottom:6px; border-bottom:1px solid #e5d8c4; position:relative; z-index:2; }
.hn-footer-brand { color:#8e673a; font-weight:bold; font-size:9.8px; letter-spacing:.8px; }
.hn-footer-address { margin-top:2px; font-size:8.5px; color:#39342e; width:100%; word-wrap:break-word; }
.hn-foot-contacts { margin-top:6px; table-layout:fixed; position:relative; z-index:2; }
.hn-foot-contacts td { vertical-align:top; padding:3px 7px; text-align:left; font-size:8.1px; color:#3a322b; border:0; word-wrap:break-word; }
.hn-foot-contacts td:first-child { padding-left:0; }
.hn-foot-contacts td+td { border-left:1px solid #d6c5ae; }
.hn-foot-icon { font-weight:bold; color:#a48454; padding-right:4px; }
.hn-foot-legal { margin-top:5px; padding-top:4px; border-top:1px solid #eee5d9; font-size:7.15px; line-height:1.25; position:relative; z-index:2; }
.hn-dot { color:#bba37f; }
.hn-foot-page { float:right; padding-left:7px; color:#8b7a65; }
.hn-footer-wave { display:block; position:absolute; z-index:0; width:100%; height:18px; left:0; bottom:0; }
.print-bar { padding:10px 28px; } @media print { .print-bar { display:none; } }
</style>
