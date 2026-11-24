<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9px; line-height: 1.48; color: #242321;
        background: #fffdfa; margin: 0; padding: 0 0 72px;
    }
    table { width:100%; border-collapse:collapse; }
    .right { text-align:right; } .center { text-align:center; }
    .muted { color:#77736b; font-size:8px; }
    .gold { color:#a9782d; }
    .watermark { position:fixed; top:300px; left:120px; width:560px; text-align:center; opacity:1; }
    .watermark img { width:560px; }
    .head {
        margin:0 38px; padding:20px 0 15px;
        border-bottom:1px solid #d7b879; position:relative;
    }
    .head:before {
        content:""; position:absolute; top:0; left:-38px; width:155px; height:7px;
        background:#c99a4c;
    }
    .head:after {
        content:""; position:absolute; top:0; right:-38px; width:190px; height:1px;
        background:#e8d6b4;
    }
    .logo { width:112px; height:auto; }
    .brand-text { font-family:DejaVu Serif,serif; font-size:19px; letter-spacing:2px; color:#a9782d; }
    .brand-tagline { font-size:7px; letter-spacing:2px; text-transform:uppercase; color:#4b4944; }
    .doc-title { font-family:DejaVu Serif,serif; font-size:27px; font-weight:bold; color:#171614; letter-spacing:1px; }
    .doc-ref { color:#292722; font-size:8px; font-weight:bold; letter-spacing:.8px; margin-top:4px; text-transform:uppercase; }
    .doc-date { color:#4e4a42; font-size:8px; margin-top:4px; }
    .page { padding:15px 38px 0; }
    .card { padding:9px 11px; border:1px solid #eadcc0; border-left:2px solid #bd8d43; background:#fffefa; }
    .card-label { color:#a9782d; font-size:7px; font-weight:bold; letter-spacing:1.4px; text-transform:uppercase; margin-bottom:4px; }
    .info td { padding:5px 3px; vertical-align:top; }
    .stamp { display:inline-block; font-size:7px; font-weight:bold; letter-spacing:1px; padding:4px 9px; border:1px solid currentColor; text-transform:uppercase; }
    .stamp-paid { color:#55765d; } .stamp-late,.void { color:#a34e42; } .stamp-draft { color:#a9782d; }
    .void { font-weight:bold; font-size:10px; }
    .grid { margin-top:18px; border:1px solid #caa15f; border-radius:5px; overflow:hidden; }
    .grid th { background:#e7c98f; color:#28231a; text-align:left; font-size:7.5px; text-transform:uppercase; letter-spacing:.35px; padding:9px 7px; font-weight:bold; border-right:1px solid #d0ad72; }
    .grid td { padding:9px 7px; border-bottom:1px solid #eadfc9; border-right:1px solid #eee5d6; color:#282622; vertical-align:middle; }
    .grid tr.alt td { background:#fbf7ef; } .grid .right { text-align:right; }
    .totals { margin-top:15px; border:1px solid #dfc89f; border-radius:5px; overflow:hidden; }
    .totals td { padding:6px 9px; color:#34312b; border-bottom:1px solid #eee4d2; font-size:9px; }
    .totals .ttc td { background:#e7c98f; color:#211d16; font-size:11px; font-weight:bold; padding:10px 9px; border-bottom:0; }
    .totals .ttc td.right { font-size:12px; }
    .box { border-left:2px solid #c99a4c; background:#fcf8f0; padding:8px 0 8px 12px; margin:16px 0; color:#555047; }
    .big { color:#9d702d; font-size:16px; font-weight:bold; margin-top:4px; }
    .sign { margin-top:28px; } .sign td { width:50%; text-align:center; color:#6b675d; font-size:8px; vertical-align:top; }
    .sign-line { margin:38px auto 0; border-top:1px solid #bd8d43; width:65%; }
    .fill td { padding-top:10px; color:#292722; font-size:9px; } .fill td.line { border-bottom:1px solid #a9a092; }
    .footer {
        position:fixed; bottom:0; left:0; right:0; padding:9px 38px 12px;
        text-align:center; font-size:7px; line-height:1.6; color:#37342e;
        background:#fffdfa; border-top:1px solid #cba25f;
    }
    .footer strong { color:#9d702d; letter-spacing:.5px; }
    .pagenum:before { content:counter(page); }
    .print-bar { padding:10px 38px; }
    @media print { .print-bar { display:none; } }

/* HNB_PREMIUM_PDF_DESIGN_V3 */

@page {
    size: A4 portrait;
    margin: 0;
}

body {
    background: #ffffff !important;
    color: #181818 !important;
    font-family: DejaVu Sans, sans-serif;
    font-size: 9px;
    padding-bottom: 96px;
}

.hnb-top-decoration {
    height: 12px;
    background: #181818;
    border-bottom: 5px solid #cda34e;
}

.hnb-header {
    padding: 24px 40px 8px;
}

.hnb-header td {
    vertical-align: middle;
    border: none;
}

.hnb-brand {
    width: 48%;
}

.hnb-logo {
    width: 205px;
    height: auto;
}

.hnb-logo-fallback {
    font-family: DejaVu Serif, serif;
    font-size: 30px;
    font-weight: bold;
    color: #bd8a2e;
}

.hnb-slogan {
    width: 52%;
    text-align: center;
    font-size: 14px;
    letter-spacing: 3px;
    line-height: 1.55;
    color: #181818;
}

.hnb-gold-line {
    margin: 13px auto 0;
    width: 85%;
    border-top: 2px solid #c99b46;
}

.hnb-document-heading {
    margin: 18px 40px 14px;
}

.hnb-document-heading td {
    vertical-align: middle;
}

.hnb-document-title {
    width: 61%;
    font-family: DejaVu Sans, sans-serif;
    font-size: 30px;
    font-weight: bold;
    color: #101010;
    text-transform: uppercase;
    border-bottom: 2px solid #c99b46;
}

.hnb-document-details {
    width: 39%;
    text-align: center;
    padding: 12px 8px !important;
    border: 1px solid #c99b46;
    font-size: 10px;
    line-height: 1.8;
}

.hnb-document-details strong {
    font-size: 12px;
    color: #111111;
}

.page {
    padding: 8px 40px 12px !important;
}

.card {
    background: #ffffff !important;
    border: 1px solid #e1d1af !important;
    border-left: 3px solid #c99b46 !important;
    padding: 12px !important;
}

.card-label {
    color: #a4772c !important;
    font-size: 9px !important;
    font-weight: bold;
}

.grid {
    margin-top: 24px !important;
    border: 1px solid #c99b46 !important;
    border-radius: 0 !important;
}

.grid th {
    background: #111111 !important;
    color: #ffffff !important;
    padding: 12px 8px !important;
    border-right: 1px solid #c99b46 !important;
    font-size: 8px !important;
}

.grid td {
    padding: 11px 8px !important;
    background: #ffffff !important;
    border-bottom: 1px solid #d8d8d8 !important;
}

.grid tr.alt td {
    background: #fffdf8 !important;
}

.totals {
    border: 1px solid #c99b46 !important;
    margin-top: 20px !important;
}

.totals td {
    padding: 9px !important;
}

.totals .ttc td {
    background: #111111 !important;
    color: #e6c27c !important;
    font-size: 12px !important;
    font-weight: bold;
}

.totals .ttc td.right {
    color: #f1d28b !important;
    font-size: 13px !important;
}

.box {
    background: #ffffff !important;
    border-left: 3px solid #c99b46 !important;
}

.watermark {
    opacity: 0.04 !important;
}

.hnb-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #111111;
    color: #ffffff;
    border-top: 7px solid #c99b46;
    padding: 15px 35px;
    font-size: 8px;
    line-height: 1.6;
}

.hnb-footer table {
    width: 100%;
}

.hnb-footer td {
    color: #ffffff;
    padding: 0 9px;
    vertical-align: middle;
}

.hnb-footer strong {
    color: #e3bc69;
    font-size: 11px;
}

</style>