<style>
/* HNB_LUXE_SIGNATURE_V14 - layout stable A4, decorative design without full-page images */
@page { size: A4 portrait; margin: 0; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.45; color: #211e18; background: #fffefa; }
table { width: 100%; border-collapse: collapse; }
.right { text-align:right; } .center { text-align:center; }
.muted { color:#736d62; font-size:8px; } .gold { color:#ae802d; }

/* Vertical proportions optimized for one-page documents with few lines. */
.head { margin:0 30px; padding:22px 3px 16px; position:relative; border-bottom:2px solid #D4A332; }
.hnb-top-edge { position:absolute; top:0; left:0; right:0; height:6px; border-top:2px solid #D4A332; border-bottom:1px solid #dfc688; }
.head:before { content:""; position:absolute; top:15px; left:-12px; width:31px; height:33px; border-top:2px solid #D4A332; border-left:2px solid #D4A332; }
.head:after { content:""; position:absolute; top:15px; right:-12px; width:31px; height:33px; border-top:2px solid #D4A332; border-right:2px solid #D4A332; }
.logo { width:156px; height:auto; }
.brand-text { font-family:DejaVu Serif,serif; font-size:27px; font-weight:bold; color:#D4A332; }
.brand-tagline { font-family:DejaVu Serif,serif; font-size:8px; line-height:2; color:#554737; letter-spacing:2.6px; }
.doc-title { font-family:DejaVu Serif,serif; font-weight:bold; font-size:27px; line-height:1.2; color:#242017; letter-spacing:1px; text-transform:uppercase; }
.doc-ref { display:inline-block; font-size:9px; font-weight:bold; color:#9e7227; border-top:1px solid #c69b4b; margin-top:6px; padding-top:5px; }
.doc-date { font-size:8px; color:#5d5648; margin-top:6px; }

.page { padding:16px 30px 0; position:relative; }
/* Small gold navigation-like details in existing information boxes. */
.card { padding:12px 13px; border:1px solid #d4b982; border-top:2px solid #D4A332; border-left:3px solid #D4A332; background:#fffefa; }
.card-label { display:block; color:#9d7024; font-size:8px; font-weight:bold; text-transform:uppercase; letter-spacing:1.1px; padding-bottom:6px; margin-bottom:7px; border-bottom:1px solid #eaddc4; }
.card strong { color:#201b15; }
.info td { padding:6px 3px; vertical-align:top; }
.stamp { display:inline-block; margin-top:2px; padding:5px 9px; font-weight:bold; font-size:7px; letter-spacing:.6px; border:1px solid currentColor; text-transform:uppercase; }
.stamp-paid { color:#476d4d; } .stamp-late,.void { color:#9b453f; } .stamp-draft { color:#a67729; }
.void { font-weight:bold; font-size:10px; }

/* Premium table: just one slim dark header, not ink-heavy blocks. */
.grid { margin-top:17px; border:1px solid #cba563; border-top:2px solid #D4A332; }
.grid thead { display:table-header-group; }
.grid tr { page-break-inside:avoid; }
.grid th { background:#302820; color:#f4e0ab; text-transform:uppercase; font-size:7.4px; letter-spacing:.45px; font-weight:bold; padding:10px 6px; border-right:1px solid #a68a5d; }
.grid th:last-child { border-right:0; }
.grid td { padding:9px 7px; color:#2f2921; border-bottom:1px solid #e8decb; border-right:1px solid #ece1d0; vertical-align:middle; }
.grid td:last-child { border-right:0; }
.grid tr.alt td { background:#fcf8ed; }
.grid tbody tr:last-child td { border-bottom:0; }

/* Highly legible financial summary, warm gold metallic-style accent. */
.totals { margin-top:17px; border:1px solid #cba66e; border-top:2px solid #D4A332; }
.totals td { background:#fffefa; color:#322c21; font-size:9px; padding:9px 10px; border-bottom:1px solid #e8dbc4; }
.totals tr:nth-child(even):not(.ttc) td { background:#fcf8ee; }
.totals .ttc td { background:#e9d19a; color:#231c12; padding:12px 10px; font-weight:bold; font-size:11px; border-top:2px solid #D4A332; border-bottom:1px solid #D4A332; }
.totals .ttc td.right { font-size:13px; }

.box { background:#fffdfa; color:#4d4538; margin:16px 0; padding:12px 13px; border:1px solid #dfc9a2; border-left:3px solid #D4A332; }
.big { font-family:DejaVu Serif,serif; color:#9d732b; font-size:20px; font-weight:bold; margin-top:5px; }
.sign { margin-top:24px; }
.sign td { width:50%; text-align:center; color:#65594a; font-size:8px; vertical-align:top; }
.sign-line { margin:38px auto 0; border-top:1px solid #b98d43; width:70%; }
.fill td { padding-top:10px; font-size:9px; }
.fill td.line { border-bottom:1px solid #c2b49a; }

/* Footer has retained legal/company fields, no large black fill or extra page-wide art. */
.footer { position:fixed; bottom:0; left:0; right:0; color:#4a4035; background:#fffefa; border-top:3px solid #D4A332; padding:13px 30px 12px; font-size:7px; line-height:1.58; }
.footer:before { content:""; position:absolute; top:6px; left:30px; right:30px; border-top:1px solid #e5cf9c; }
.footer strong { color:#9e7023; letter-spacing:.35px; font-size:7.5px; }
.pagenum:before { content:counter(page); }
.print-bar { padding:10px 30px; }
@media print { .print-bar { display:none; } }

/* V15: HN BLOOM luxury gold iconography, DomPDF-safe: no page-height ornaments */
.hnb-industries {
    position:absolute;
    top:7px;
    right:7px;
    height:15px;
    white-space:nowrap;
    z-index:3;
    padding:0 3px;
    background:#fffefa;
}
.hnb-industries img { width:13px; height:13px; margin-left:8px; vertical-align:middle; }
/* Refined top architecture: keep the original gold logo and title sizes */
.head { border-bottom:2px solid #d4a332; }
.head:before, .head:after { border-color:#d4a332; }
.card { border:1px solid #d8bf89; border-top:2px solid #d4a332; border-left:3px solid #d4a332; background:#fffefa; }
.card-label {
    position:relative;
    color:#a27727;
    font-size:8px;
    padding-left:13px;
    border-bottom:1px solid #e6d5ad;
    letter-spacing:1.0px;
}
.card-label:before {
    content:"";
    position:absolute;
    left:1px;
    top:2px;
    height:6px;
    width:6px;
    border:1.4px solid #d4a332;
    transform:rotate(45deg);
}
/* One small dark table header is intentional; everything else is light to save ink. */
.grid { border:1px solid #d1b278; }
.grid th { background:#3a3024; color:#f9e7b7; border-right:1px solid #9b7844; }
.grid tr.alt td { background:#fffaf0; }
.totals { border:1px solid #d3b276; }
.totals .ttc td { background:#f0d69b; border-top:2px solid #d4a332; }
.footer { border-top:2px solid #d4a332; background:#fffefa; }
.hnb-foot-icon { width:10px; height:10px; vertical-align:middle; margin-right:3px; }
/* Do not introduce position:fixed full-page frames or oversized pseudo-elements. */

</style>
