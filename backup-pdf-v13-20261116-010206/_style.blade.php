<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9.4px;
        line-height: 1.44;
        color: #1f1d1a;
        background: #fffdf9;
        margin: 0;
        padding: 0 0 60px;
    }

    table { width:100%; border-collapse:collapse; }
    .right { text-align:right; }
    .center { text-align:center; }
    .muted { color:#7e766a; font-size:8px; }
    .gold { color:#b68a3e; }
    .pagenum:before { content: counter(page); }

    .watermark {
        position: fixed;
        top: 230px;
        left: 80px;
        width: 530px;
        text-align:center;
        opacity: .05;
        z-index: 0;
    }
    .watermark img { width: 530px; }

    .head-shell {
        position: relative;
        margin: 0 30px;
        padding-top: 8px;
    }
    .head-topline {
        height: 3px;
        background: #c59b4b;
        border-bottom: 1px solid #ead8b5;
    }
    .head-corner {
        position: absolute;
        top: 0;
        right: 0;
        width: 118px;
        height: 60px;
        border-top: 14px solid #e2c37d;
        border-left: 14px solid transparent;
        opacity: .95;
    }

    .head {
        position: relative;
        z-index: 2;
        padding: 14px 0 12px;
        border-bottom: 1px solid #cfb07a;
    }

    .logo {
        width: 157px;
        height: auto;
    }
    .brand-text {
        font-family: DejaVu Serif, serif;
        font-size: 22px;
        font-weight: bold;
        letter-spacing: 2px;
        color: #b68937;
    }
    .brand-tagline {
        font-size: 6.7px;
        letter-spacing: 2.2px;
        text-transform: uppercase;
        color: #5b5449;
        line-height: 1.9;
    }
    .doc-title {
        font-family: DejaVu Serif, serif;
        font-size: 27px;
        font-weight: bold;
        color: #1a1714;
        letter-spacing: .8px;
        line-height: 1.1;
        margin-top: 2px;
    }
    .doc-ref {
        display: inline-block;
        margin-top: 8px;
        padding-top: 5px;
        border-top: 1.5px solid #c3984b;
        color: #b07f2f;
        font-size: 8px;
        font-weight: bold;
        letter-spacing: .8px;
        text-transform: uppercase;
    }
    .doc-date {
        color:#5f594f;
        font-size:7.7px;
        margin-top:5px;
    }

    .page {
        position: relative;
        z-index: 1;
        padding: 12px 30px 0;
    }

    .card {
        padding: 10px 11px;
        border: 1px solid #dbc7a1;
        border-left: 3px solid #bc9047;
        border-radius: 4px;
        background: #fffdfa;
    }
    .card-label {
        color: #a7792e;
        font-size: 6.8px;
        font-weight: bold;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .info td { padding: 4px 3px; vertical-align: top; }

    .stamp {
        display:inline-block;
        font-size:7px;
        font-weight:bold;
        letter-spacing:1px;
        padding:4px 9px;
        border:1px solid currentColor;
        border-radius: 3px;
        text-transform:uppercase;
        background: #fff;
    }
    .stamp-paid { color:#4d7c5a; }
    .stamp-late, .void { color:#a34e42; }
    .stamp-draft { color:#a9782d; }
    .void { font-weight:bold; font-size:10px; }

    .grid {
        margin-top: 12px;
        border: 1px solid #ccb086;
        border-radius: 4px;
        overflow: hidden;
    }
    .grid th {
        background: #efe4cf;
        color: #262119;
        text-align: left;
        font-size: 7px;
        text-transform: uppercase;
        letter-spacing: .35px;
        padding: 8px 6px;
        font-weight: bold;
        border-right: 1px solid #d7bf93;
        border-bottom: 1px solid #caa56b;
    }
    .grid td {
        padding: 7px 6px;
        border-bottom: 1px solid #ece2d1;
        border-right: 1px solid #f1ebdf;
        color: #29251f;
        vertical-align: middle;
    }
    .grid tr.alt td { background: #fcf9f2; }
    .grid .right { text-align:right; }

    .totals {
        margin-top: 12px;
        border: 1px solid #d6bea0;
        border-radius: 4px;
        overflow:hidden;
    }
    .totals td {
        padding: 7px 8px;
        color: #312d27;
        border-bottom: 1px solid #eee4d5;
        font-size: 8.6px;
        background: #fff;
    }
    .totals .ttc td {
        background: #e5d1a7;
        color: #1e1912;
        font-size: 11px;
        font-weight: bold;
        padding: 10px 8px;
        border-bottom: 0;
        border-top: 1px solid #c3974c;
    }
    .totals .ttc td.right { font-size: 12px; }

    .box {
        border: 1px solid #dcc8a3;
        border-left: 3px solid #c39648;
        background: #fffdfa;
        padding: 9px 11px;
        margin: 14px 0;
        color: #544e45;
    }
    .big {
        color: #a97929;
        font-size: 16px;
        font-weight: bold;
        margin-top: 4px;
    }

    .fill td {
        padding-top: 8px;
        color:#2b2823;
        font-size:9px;
    }
    .fill td.line { border-bottom:1px solid #b6aa96; }

    .sign {
        margin-top: 18px;
    }
    .sign td {
        width: 50%;
        text-align: center;
        color: #666055;
        font-size: 8px;
        vertical-align: top;
    }
    .sign-line {
        margin: 28px auto 0;
        border-top: 1px solid #b88a3d;
        width: 68%;
    }

    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 9px 30px 10px;
        font-size: 7px;
        line-height: 1.5;
        color: #373229;
        background: #fffdf9;
        border-top: 2px solid #bc8f43;
    }
    .footer strong {
        color: #a97929;
        letter-spacing: .4px;
    }

    .print-bar { padding:10px 30px; }
    @media print { .print-bar { display:none; } }

/* HNB SIGNATURE GOLD V5 — same gold hue as the HN BLOOM logo */
:root { --hnb-gold: #d4a332; }
body { background:#fffdfa; color:#24201b; }
.hnb-gold-art-top {
 position:fixed; top:0; right:0; width:235px; height:101px;
 overflow:hidden; z-index:-1; opacity:.45;
}
.hnb-gold-art-top img { width:235px; height:auto; }
.hnb-gold-art-bottom {
 position:fixed; bottom:37px; left:0; width:235px; height:101px;
 overflow:hidden; z-index:-1; opacity:.33;
}
.hnb-gold-art-bottom img { width:235px; height:auto; }
.head-shell { margin:0 33px; padding-top:12px; }
.head-topline { height:3px; background:#d5a52f; border-bottom:1px solid #f1ddb0; }
.head-corner { display:none; }
.head { padding:16px 0 18px; border-bottom:2px solid #d6a333; }
.logo { width:165px; }
.brand-text { color:#d3a02c; font-size:27px; }
.brand-tagline { color:#554b3d; font-size:7.1px; letter-spacing:2.1px; }
.doc-title { color:#1d1a15; font-size:30px; }
.doc-ref { border-top:2px solid #d4a332; color:#ae7f1e; font-size:9px; }
.doc-date { color:#514a3c; font-size:8.1px; }
.page { padding:15px 33px 0; }
.card {
 border:1px solid #e1c992;
 border-left:4px solid #d5a333;
 border-radius:6px;
 background:#fffdfa;
 padding:12px 13px;
}
.card-label { color:#a87920; font-size:7.6px; letter-spacing:1.4px; margin-bottom:7px; }
.grid { margin-top:18px; border:1px solid #d5ad59; border-radius:6px; }
.grid th {
 background:#f3e7c9; color:#262018;
 padding:10px 7px; border-right:1px solid #dfc995;
 border-top:2px solid #d3a02a; border-bottom:1px solid #bb8c30;
 font-size:7.2px;
}
.grid td { padding:9px 7px; border-bottom:1px solid #e5dac5; border-right:1px solid #f0e6d0; }
.grid tr.alt td { background:#fdf9ef; }
.totals { margin-top:17px; border:1px solid #d7b874; border-radius:6px; }
.totals td { padding:9px 9px; background:#fffdf9; border-bottom:1px solid #eadac1; }
.totals .ttc td {
 border-top:2px solid #d3a02a;
 background:#f2dfaf;
 color:#262017;
 font-size:12px;
 padding:13px 9px;
}
.totals .ttc td.right { color:#222019; font-size:13px; }
.box { border:1px solid #ddc89e; border-left:4px solid #d4a332; padding:11px 12px; background:#fffdfa; }
.gold,.big { color:#ae7d24; }
.sign-line { border-top:1px solid #d4a332; }
.watermark { top:300px; left:100px; width:420px; opacity:.035; }
.watermark img { width:420px; }
.footer {
 background:#fffdfa; border-top:2px solid #d4a332;
 padding:11px 33px 13px; color:#40382a;
}
.footer strong { color:#b07d20; }
/* Preserve all existing document rows, columns, notes and payment data. */


/* HNB ART DECO V6 | official print design | golden color #D4A332 */
body { background: #fffefa !important; color: #211d19 !important; }
.head-shell { margin: 0 27px !important; padding-top: 12px !important; }
.head-topline { height: 5px !important; background: #d4a332 !important; border-bottom: 1px solid #f5e2ac !important; }
.head { padding: 18px 8px 16px !important; border-bottom: 1px solid #d4a332 !important; }
.hnb-v6-kicker { font-size: 6px; font-weight:bold; color: #ac832b; text-align:center; letter-spacing: 2.3px; margin-top: 7px; }
.logo { width: 179px !important; }
.doc-title { font-family: DejaVu Serif,serif !important; font-size: 33px !important; letter-spacing: 1px !important; color:#1d1a15 !important; }
.doc-ref { font-size: 9.5px !important; color:#a57621 !important; }
.brand-tagline { color:#78664b !important; letter-spacing:2.2px !important; }
.page { padding: 17px 27px 0 !important; }
.card { background:#fffefa !important; border: 1px solid #ddbf77 !important; border-left: 5px solid #d4a332 !important; padding: 15px 14px !important; }
.card-label { color:#aa7820 !important; font-size: 8px !important; letter-spacing: 1.2px !important; border-bottom:1px solid #ecd9aa; padding-bottom:6px; margin-bottom:7px; }
.grid { margin-top: 19px !important; border: 1px solid #b99752 !important; }
.grid th { background: #26231e !important; color: #fff4d2 !important; border:1px solid #b99752 !important; border-top: 2px solid #d4a332 !important; padding: 11px 8px !important; font-size: 8px !important; }
.grid td { padding: 10px 7px !important; border-bottom: 1px solid #e4d6b7 !important; background: #fffefa !important; }
.grid tr.alt td { background:#faf2df !important; }
.totals { margin-top: 18px !important; border: 1px solid #cda04a !important; }
.totals td { background: #fffdfa !important; border-bottom:1px solid #e5d4b0 !important; padding: 9px !important; font-size:9px !important; }
.totals .ttc td { background:#f1d69b !important; border-top:3px solid #d4a332 !important; color:#241b10 !important; padding:14px 9px !important; font-size:13px !important; }
.totals .ttc td.right { font-size: 14px !important; color:#221c12 !important; }
.box { background:#fffdfa !important; border: 1px solid #d1b16a !important; border-left:5px solid #d4a332 !important; padding:12px !important; }
.sign-line { border-top:1px solid #b58934 !important; }
.watermark { top:320px !important; left:118px !important; width:370px !important; opacity:.018 !important; }
.watermark img { width:370px !important; }
.footer { background:#fffefa !important; border-top:0 !important; padding:15px 28px 13px !important; font-size:7.5px !important; }
.hnb-v6-foot-rule { position:absolute; top:0; left:28px; right:28px; height:3px; background:#d4a332; }
.footer strong { color:#a87c20 !important; font-size:8.4px !important; }
.hnb-v6-bottom-deco { position:fixed; z-index:-2; left:0; right:0; bottom:38px; height:180px; opacity:.64; }
.hnb-v6-bottom-deco img { width:100%; height:180px; }
.hnb-v6-bottom-seal { position:fixed; z-index:-1; right:55px; bottom:115px; width:145px; opacity:.14; }
.hnb-v6-bottom-seal img { width:145px; }
.hnb-v6-top-medallion { position:fixed; z-index:-1; top:0; right:0; width:210px; height:155px; background:#fffefa; border-right:5px solid #d4a332; }
@media print { .print-bar {display:none !important;} }


/* HN BLOOM V7 | ZAKHRAFA | decorative print layer, not data */
.hnb-v7-page-ornament {
    position: fixed;
    top: 0; left: 0;
    width: 595pt; height: 842pt;
    z-index: -5;
    overflow: hidden;
}
.hnb-v7-page-ornament img {
    width: 595pt; height: 842pt;
}
.hnb-v6-bottom-deco, .hnb-v6-bottom-seal, .hnb-v6-top-medallion,
.hnb-gold-art-top, .hnb-gold-art-bottom { display: none !important; }
.watermark { opacity: .012 !important; }
/* Keep decorative borders away from document content. */
.head-shell { margin: 0 38px !important; }
.page { padding-left: 38px !important; padding-right: 38px !important; }
.footer { padding-left: 38px !important; padding-right: 38px !important; }
.head { border-bottom: 2px solid #D4A332 !important; }
.head-topline { height: 4px !important; background: #D4A332 !important; }
.card { border-color: #D4A332 !important; border-left-color: #D4A332 !important; }
.card-label { color: #A7781B !important; }
.grid { border-color: #D4A332 !important; }
.grid th { border-top-color: #D4A332 !important; color:#f9e9c3 !important; }
.totals .ttc td { background: #F2DFAF !important; border-top-color: #D4A332 !important; }
.footer { border-top: 2px solid #D4A332 !important; }


/* HNB GOLD REFINED V10 — V7 layout and logo dimensions preserved */
body { background: #fffdfa !important; color: #27221a !important; }
.head-topline { background:#D4A332 !important; }
.head { border-bottom: 1.5px solid #D4A332 !important; }
.card {
    background: #fffdfa !important;
    border: 1px solid #decba6 !important;
    border-left: 3px solid #D4A332 !important;
}
.card-label { color: #9d741e !important; border-bottom-color:#efe3ce !important; }
.grid { border:1px solid #cba957 !important; }
.grid th {
    background: #f3ead7 !important;
    color: #292219 !important;
    border:1px solid #d9c398 !important;
    border-top: 2px solid #D4A332 !important;
    border-bottom: 1px solid #cba45d !important;
}
.grid td, .grid tr.alt td { background:#fffdfa !important; border-bottom-color:#e9dfcd !important; }
.totals { border-color:#cfae71 !important; }
.totals td { background:#fffdfa !important; border-bottom-color:#e9dfcd !important; }
.totals .ttc td {
    background:#f0dfb5 !important;
    border-top: 2px solid #D4A332 !important;
    color:#2d2519 !important;
}
.box { background:#fffdfa !important; border-color:#dcca9e !important; }
.footer { background:#fffdfa !important; border-top:1.5px solid #D4A332 !important; color:#4c4030 !important; }
.hnb-v7-page-ornament { opacity:0.92 !important; }
/* Keep exactly the V7 logo dimensions, header, spacing and document data. */


/* HNB_PDF_PAGINATION_V12 */
@page { size: A4 portrait; margin: 0 !important; }
html, body { margin:0 !important; padding:0 !important; height:auto !important; min-height:0 !important; }
.watermark, .hnb-v7-page-ornament, .hnb-v7-page-ornament img,
.hnb-v6-bottom-deco, .hnb-v6-bottom-seal, .hnb-v6-top-medallion,
.hnb-gold-art-top, .hnb-gold-art-bottom { display:none !important; }
/* Decorative border drawn with CSS only. No full-page image or flow height. */
.head-shell { position:relative; }
.head-shell:before { content:""; position:absolute; top:4px; left:4px; width:23px; height:23px; border-left:1px solid #D4A332; border-top:1px solid #D4A332; }
.head-shell:after { content:""; position:absolute; top:4px; right:4px; width:23px; height:23px; border-right:1px solid #D4A332; border-top:1px solid #D4A332; }
.footer { background:#fffdfa !important; }

</style>
