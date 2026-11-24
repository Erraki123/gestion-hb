<style>
    @page { margin: 0; }

    :root {
        --ink: #202321;
        --gold: #A8874A;
        --gold-soft: #C6B486;
        --ivory: #F8F6F1;
        --muted: #777873;
        --rule: #D8D3C8;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        line-height: 1.45;
        color: var(--ink);
        margin: 0;
        padding: 0;
    }

    body:before {
        content: "";
        display: block;
        height: 2px;
        background: var(--gold);
    }

    .page {
        padding: 0 42px;
    }

    table { width: 100%; border-collapse: collapse; }
    .right { text-align: right; }
    .center { text-align: center; }
    .muted { color: var(--muted); font-size: 9px; }
    .gold { color: var(--gold); }

    .topbar {
        background: #FFFFFF;
        padding: 20px 42px 15px;
        border-bottom: 1px solid var(--rule);
    }

    .goldline {
        display: none;
    }

    .logo {
        width: 54px;
        height: 54px;
        object-fit: contain;
    }

    .brand {
        font-family: DejaVu Serif, serif;
        font-size: 15px;
        font-weight: bold;
        color: var(--ink);
        letter-spacing: 2px;
    }

    .topbar .sub {
        color: var(--muted);
        font-size: 8px;
        line-height: 1.55;
        margin-top: 3px;
    }

    .doc-title {
        font-family: DejaVu Serif, serif;
        font-size: 19px;
        font-weight: normal;
        color: var(--ink);
        letter-spacing: 1px;
    }

    .doc-ref {
        font-size: 8px;
        font-weight: bold;
        color: var(--gold);
        letter-spacing: 1.5px;
        margin-top: 4px;
        text-transform: uppercase;
    }

    .doc-date {
        font-size: 8px;
        color: var(--muted);
        margin-top: 4px;
    }

    .card {
        border: 0;
        border-top: 1px solid var(--gold);
        background: transparent;
        padding: 10px 0 8px;
    }

    .card-label {
        font-size: 7px;
        letter-spacing: 1.8px;
        text-transform: uppercase;
        color: var(--gold);
        font-weight: bold;
        margin-bottom: 5px;
    }

    .info td { padding: 3px 0; vertical-align: top; }

    .grid {
        margin-top: 18px;
        border-top: 1px solid var(--ink);
    }

    .grid th {
        background: var(--ivory);
        color: var(--gold);
        text-align: left;
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 8px 8px;
        font-weight: bold;
        border-bottom: 1px solid var(--rule);
    }

    .grid td {
        padding: 8px 8px;
        border-bottom: 1px solid var(--rule);
    }

    .grid tr.alt td { background: #FCFBF8; }
    .grid .right { text-align: right; }

    .totals {
        margin-top: 17px;
    }

    .totals td {
        padding: 4px 8px;
        color: var(--muted);
    }

    .totals .ttc td {
        background: transparent;
        color: var(--ink);
        font-family: DejaVu Serif, serif;
        font-size: 14px;
        font-weight: bold;
        padding: 9px 8px 5px;
        border-top: 1px solid var(--gold);
    }

    .totals .ttc td.right {
        color: var(--gold);
        font-size: 18px;
    }

    .box {
        border: 0;
        border-left: 2px solid var(--gold);
        background: var(--ivory);
        padding: 11px 14px;
        margin: 16px 0;
    }

    .big {
        font-family: DejaVu Serif, serif;
        color: var(--gold);
        font-size: 21px;
        font-weight: bold;
        margin-top: 3px;
    }

    .stamp {
        display: inline-block;
        font-size: 8px;
        font-weight: bold;
        letter-spacing: 1.8px;
        padding: 5px 12px;
        border: 1px solid currentColor;
        text-transform: uppercase;
    }

    .stamp-paid { color: #51765B; }
    .stamp-late { color: #9B5750; }
    .stamp-draft { color: var(--gold); }
    .void { color: #9B5750; font-weight: bold; font-size: 11px; }

    .sign { margin-top: 45px; }
    .sign td { width: 50%; text-align: center; color: var(--muted); font-size: 9px; }
    .sign-line { margin: 45px auto 0; border-top: 1px solid var(--rule); width: 60%; }

    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 7px 42px 10px;
        text-align: center;
        font-size: 7px;
        color: var(--muted);
        border-top: 1px solid var(--gold);
    }

    .pagenum:before { content: counter(page); }
</style>
