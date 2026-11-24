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
</style>
