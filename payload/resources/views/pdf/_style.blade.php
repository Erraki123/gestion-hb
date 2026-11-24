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

</style>
