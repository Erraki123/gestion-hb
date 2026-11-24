<style>
@page { size: A4 portrait; margin: 13mm 13mm 28mm; }
*{box-sizing:border-box}
:root{--ink:#1c2930;--blue:#273d4c;--gold:#b28a50;--gold2:#d7bd89;--cream:#f8f5ef;--line:#d9d4c9;--soft:#f1f1ef}
html,body{margin:0;padding:0;color:var(--ink);font-family:Arial,Helvetica,sans-serif;font-size:10pt;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.sheet{width:100%;max-width:188mm;margin:0 auto}
.masthead{border-bottom:1.5px solid var(--gold);padding-bottom:10px;margin-bottom:17px}
.brand-row{display:flex;align-items:center;justify-content:space-between;gap:14px}
.brand-col{width:39%;min-width:0}
.brand-logo{max-width:66mm;max-height:28mm;object-fit:contain;object-position:left center}
.brand-word{font-family:Georgia,serif;font-size:27pt;color:var(--gold);line-height:1}.brand-word span{font-size:14pt;display:block}
.brand-tag{margin-top:4px;font-family:Georgia,serif;font-size:6.9pt;letter-spacing:1.15px;color:#766853;white-space:nowrap}
.brand-tag b{color:var(--gold)}
.service-band{display:flex;align-items:stretch;justify-content:space-between;gap:0;width:60%;min-height:24mm;border:1px solid #d0ae77;border-right:3px solid var(--gold);background:linear-gradient(115deg,#fffaf1,#f3e9d7 60%,#f9f2e7);clip-path:polygon(5% 0,100% 0,100% 100%,0 100%)}
.service-icon{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;text-align:center;border-left:1px solid #d4ba91;min-width:0}
.service-icon:first-child{border-left:0}.service-icon svg{width:26px;height:26px;fill:none;stroke:#97713c;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.service-icon span{font-size:6.4pt;font-weight:700;letter-spacing:.4px;color:#775b34}
.services-description{font-size:9pt;margin-top:6px;color:#545052;letter-spacing:.25px}.services-description i{font-style:normal;color:var(--gold);padding:0 4px}
.document-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:17px;min-height:20mm}
.document-ribbon{min-width:55%;max-width:64%;padding:10px 25px 10px 16px;background:linear-gradient(105deg,#293c48,#3b4b52);clip-path:polygon(0 0,100% 0,93% 100%,0 100%);color:#fff;border-left:4px solid var(--gold)}
.document-ribbon span{font-family:Georgia,serif;font-size:22pt;font-weight:700;line-height:1;letter-spacing:.4px}
.document-metadata{flex:1;border:1px solid var(--line);border-radius:6px;padding:8px 11px;box-shadow:0 0 0 1px rgba(178,138,80,.08);min-width:0}
.doc-meta-line{display:flex;justify-content:space-between;align-items:baseline;gap:10px;font-size:9pt;padding:2px 0}.doc-meta-line span{color:#67635f}.doc-meta-line strong{text-align:right;overflow-wrap:anywhere}
.state-label{display:inline-block;padding:5px 8px;margin:-3px 0 10px;border:1px solid #b5a27f;color:#77613b;font-size:8pt;font-weight:700;letter-spacing:1px}
.parties{display:flex;gap:12px;margin-bottom:12px;align-items:stretch}.party-card{flex:1;border:1px solid #dad8d2;border-radius:7px;overflow:hidden;min-width:0}
.party-title{background:#e9ecec;border-bottom:1px solid #d4d9d8;padding:8px 12px;font-weight:700;color:#253c4c;font-size:10pt;letter-spacing:.2px}
.label-icon{display:inline-block;color:var(--gold);font-size:15pt;line-height:1;vertical-align:-2px;margin-right:5px}
.party-body{min-height:29mm;padding:11px 13px;font-size:9pt;line-height:1.5;overflow-wrap:anywhere}.party-body strong{display:block;font-size:11pt;margin-bottom:5px}.party-body p{margin:3px 0}
.subnote{padding:7px 10px;border-left:2px solid var(--gold);background:#faf8f3;font-size:9pt;margin:7px 0 11px}
.line-table,.payment-table{width:100%;border-collapse:collapse;margin:14px 0;table-layout:fixed;font-size:8.5pt;page-break-inside:auto}
.line-table thead,.payment-table thead{display:table-header-group}
.line-table tr,.payment-table tr{break-inside:avoid;page-break-inside:avoid}
.line-table th,.payment-table th{background:#2c4351;color:white;padding:9px 6px;text-align:left;letter-spacing:.35px;font-size:7.3pt;font-weight:700;border-right:1px solid #657781}
.line-table td,.payment-table td{padding:11px 7px;vertical-align:top;border:1px solid #dededa;overflow-wrap:anywhere}
.line-table tbody tr:nth-child(even) td,.payment-table tbody tr:nth-child(even) td{background:#f4f6f6}
.line-table tbody tr:first-child td{border-top:2px solid #c0a06c}
.line-table td:first-child{font-size:8pt}.line-table td.num,.payment-table td.num{text-align:right;white-space:normal;font-variant-numeric:tabular-nums}
.line-table td.num small{display:block;font-size:7pt;color:#6f6f6f}.item-name{font-weight:500}.boldnum{font-weight:700}
.summary-row{display:flex;align-items:flex-start;gap:15px;justify-content:space-between;margin-top:10px;break-inside:avoid;page-break-inside:avoid}
.notes-zone{flex:1;min-width:0}.note-card{border:1px solid #e0ddd6;border-radius:6px;background:#f7f7f5;margin-bottom:10px;overflow:hidden}
.note-title{font-weight:700;padding:9px 10px;background:#e9ecec;color:#263e4c;border-bottom:1px solid #d9ddd9;font-size:9pt}
.note-content{padding:10px 11px;font-size:9pt;white-space:pre-line;line-height:1.5;overflow-wrap:anywhere}
.summary-box{width:45%;min-width:0;border:1px solid #d7d5cf;border-radius:6px;overflow:hidden;margin-left:auto}
.summary-line{display:flex;justify-content:space-between;gap:8px;border-bottom:1px solid #e3e1dd;padding:9px 10px;font-size:9pt}
.summary-line:last-child{border:0}.summary-line strong{font-variant-numeric:tabular-nums;text-align:right}
.summary-grand{background:#2c4351;color:white;font-size:11pt;font-weight:700;padding:12px 10px;border:0}
.summary-strong{font-weight:700}
.section-heading{margin:18px 0 9px;border-bottom:1px solid var(--gold);padding-bottom:6px;font-weight:700;color:#314754;letter-spacing:1px;font-size:10pt}
.receipt-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:12px 0}.receipt-cell{padding:10px;border:1px solid #e1dcd2;background:#fbfaf7;min-width:0;overflow-wrap:anywhere}
.receipt-cell span{display:block;font-size:8pt;color:#77726b;margin-bottom:5px}.receipt-cell strong{font-size:10pt}
.signature-row{display:flex;gap:15px;margin:24px 0 8px;justify-content:space-between;break-inside:avoid}
.signature-row>div{flex:1;font-size:8pt;text-align:center;color:#777}.signature-line{border-bottom:1px solid #a7a199;margin:25px auto 0;width:75%}
.page-footer{width:100%;background:#fff;position:fixed;left:0;right:0;bottom:-22mm;min-height:20mm;padding-top:5px;color:#273a43;font-size:7.5pt}
.footer-top{height:1.5px;background:#b48b50;margin-bottom:7px}
.footer-flex{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;z-index:2;position:relative}
.footer-block{display:flex;gap:5px;align-items:flex-start;min-width:0;max-width:22%;overflow-wrap:anywhere}.footer-block.address{max-width:40%;flex:1.5}
.footer-block div{min-width:0}.footer-block strong{display:block;font-size:8pt;color:#926f3f;margin-bottom:2px}
.footer-block span:not(.footer-ico){display:block;line-height:1.35}.footer-ico{font-size:13pt;color:#b28a50;line-height:1}
.footer-sweep{position:absolute;bottom:-1mm;left:0;right:0;border-bottom:2px solid #c9aa74;opacity:.65}
@media screen{body{background:#e9ecef;padding:24px}.sheet{background:white;max-width:210mm;min-height:255mm;padding:14mm;box-shadow:0 8px 30px rgba(0,0,0,.13)}.page-footer{position:relative;max-width:182mm;margin:-26mm auto 0;bottom:auto;background:white}}
@media print{body{background:#fff}.sheet{max-width:none}.page-footer{position:fixed}}
/* HNB_CHROMIUM_BLACK_GOLD_PATCH_START */
:root {
  --ink: #1b1a18;
  --blue: #23211e;
  --gold: #b48a45;
  --gold2: #d6b777;
  --cream: #faf7f0;
  --line: #d9cbb4;
  --soft: #f5f1e8;
}
html,body { color:#201e1b; }
.masthead { border-bottom:2px solid #b48a45; padding-bottom:11px; margin-bottom:16px; }
.brand-row { display:flex; align-items:center; justify-content:space-between; gap:11px; min-height:29mm; }
.brand-col { width:42%; flex:0 0 42%; min-width:0; }
.brand-logo { display:block; max-width:100%; width:auto; max-height:28mm; object-fit:contain; object-position:left center; }
.brand-tag { white-space:normal; line-height:1.5; font-size:7.3pt; letter-spacing:.45px; margin-top:5px; }
.service-band { flex:1 1 auto; width:auto; min-width:0; min-height:24mm; border:1px solid #c6a46e; border-right:2px solid #b48a45; background:linear-gradient(120deg,#fffaf3,#f4e9d6 58%,#fdf8ed); }
.service-icon { padding:5px 2px; }
.service-icon svg { width:29px; height:29px; stroke:#8e6a34; }
.service-icon span { font-size:6.9pt; letter-spacing:0; color:#5e4629; }
.services-description { color:#36312b; font-size:9pt; line-height:1.5; margin-top:8px; }
.document-ribbon { background:linear-gradient(105deg,#171614,#35302a); color:#fff; border-left:4px solid #bc934b; }
.document-metadata { border-color:#cbb994; box-shadow:none; background:#fff; }
.party-card { border-color:#cfc1a9; }
.party-title { background:#f0e6d3; color:#302820; border-bottom:1px solid #c7aa78; }
.party-body { background:#fff; color:#24211d; }
.line-table th,.payment-table th { background:#24211e; color:#f7eddc; border-right:1px solid #6a5844; }
.line-table td,.payment-table td { border-color:#d8cec0; }
.line-table tbody tr:nth-child(even) td,.payment-table tbody tr:nth-child(even) td { background:#faf7f2; }
.summary-box { border-color:#bea57a; }
.summary-grand { background:#24211e; color:#f5d99e; }
.note-card { background:#faf8f3; border-color:#d6c6aa; }
.note-title { color:#332a20; background:#f0e6d3; border-bottom:1px solid #d9c6a0; }
.section-heading { color:#33291c; }
.footer-top { background:#b48a45; }
.page-footer { color:#2e2a24; }
.footer-block strong { color:#8c662c; }
.footer-ico { color:#ad8444; }
@media screen {
  .sheet { padding:14mm; }
}
/* HNB_CHROMIUM_BLACK_GOLD_PATCH_END */

/* HNB_CHROME_REFERENCE_HEADER_V23_START */
/* Reference: large left wordmark, diagonal right icon strip, slim services baseline */
.hnb-ref-masthead { padding:0 0 4mm!important; margin:0 0 7mm!important; border-bottom:1.6px solid #b88b45!important; background:#fff!important; break-inside:avoid; }
.hnb-ref-top{ display:flex; width:100%; align-items:flex-start; justify-content:space-between; gap:3mm; min-height:31mm; }
.hnb-ref-identity{ flex:0 0 47%; min-width:0; padding-top:0; }
.hnb-ref-logo{ display:block; width:auto; height:auto; max-width:100%; max-height:29mm; object-fit:contain; object-position:left center; }
.hnb-ref-wordmark{ font-family:Georgia,serif; font-size:28pt; font-weight:700; color:#b88b45; }
.hnb-ref-motto{ font-size:7pt; letter-spacing:.12px; color:#37322c; margin-top:1mm; line-height:1.2; }
.hnb-ref-banner{ position:relative; flex:1; min-width:0; height:25mm; background:linear-gradient(135deg,#38312a,#171614 72%); clip-path:polygon(11% 0,100% 0,100% 100%,0 100%); border-bottom:2px solid #c59e61; padding:1.5mm 2mm 1.5mm 6mm; }
.hnb-ref-banner:before{content:''; position:absolute; width:100%; height:2.5mm; right:0; top:0; background:linear-gradient(90deg,rgba(199,159,88,.0),#d7bb86); opacity:.8;}
.hnb-ref-icons{ display:flex; justify-content:space-around; align-items:center; height:100%; gap:1mm; position:relative; z-index:1; }
.hnb-ref-icon{ display:flex; flex:1; align-items:center; justify-content:center; height:100%; min-width:0; border-right:1px solid rgba(242,221,178,.3); }
.hnb-ref-icon:last-child{border-right:0}
.hnb-ref-icon svg{display:block; height:15mm; width:15mm; max-width:100%; fill:none; stroke:#f7ebd5; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
.hnb-ref-sector-line{ margin-top:1.5mm; font-size:9pt; color:#282522; line-height:1.35; letter-spacing:0; overflow-wrap:anywhere; }
.hnb-ref-sector-line b{ color:#b88b45; font-weight:700; padding:0 1mm; }
@media screen { .hnb-ref-masthead { margin-bottom:7mm!important; } }
/* HNB_CHROME_REFERENCE_HEADER_V23_END */
/* HNB_HEADER_V24_START */
/* Compact, editorial header like the supplied reference. No extra blank vertical space. */
.hnb-ref-masthead {
  background:#ffffff !important;
  padding:0 0 2.8mm !important;
  margin:0 0 6mm !important;
  border-bottom:1.2px solid #b88b45 !important;
  break-inside:avoid;
}
.hnb-ref-top {
  display:flex !important;
  align-items:flex-start !important;
  justify-content:space-between !important;
  gap:4mm !important;
  min-height:0 !important;
  height:20mm !important;
}
.hnb-ref-identity {
  flex:0 0 42% !important;
  width:42% !important;
  min-width:0 !important;
  padding:0 !important;
  align-self:flex-start !important;
}
.hnb-ref-logo {
  display:block !important;
  height:17mm !important;
  max-height:17mm !important;
  max-width:100% !important;
  width:auto !important;
  object-fit:contain !important;
  object-position:left top !important;
}
.hnb-ref-wordmark { font-family:Georgia,serif; font-size:20pt; font-weight:700; color:#b88b45; line-height:1.05; }
.hnb-ref-motto {
  font-size:6.4pt !important;
  color:#3a342f !important;
  line-height:1.1 !important;
  margin-top:.3mm !important;
  white-space:normal;
}
.hnb-ref-banner {
  flex:1 1 auto !important;
  width:auto !important;
  min-width:0 !important;
  height:18mm !important;
  margin-top:0 !important;
  border-bottom:1.5px solid #b58b4a !important;
  padding:1mm 1.3mm 1mm 6.5mm !important;
  background:linear-gradient(110deg,#35302b 0%,#1b1a18 74%,#34302b 100%) !important;
  clip-path:polygon(9% 0,100% 0,100% 100%,0 100%) !important;
  position:relative !important;
}
.hnb-ref-banner:before {
  height:1.5mm !important;
  opacity:.65 !important;
  background:linear-gradient(90deg,transparent,#c9a35c) !important;
}
.hnb-ref-icons { height:100% !important; gap:0 !important; }
.hnb-ref-icon { height:100% !important; min-width:0 !important; border-right:1px solid rgba(241,222,181,.25) !important; }
.hnb-ref-icon:last-child {border-right:0 !important;}
.hnb-ref-icon svg {
  width:10.5mm !important;
  height:10.5mm !important;
  max-width:100% !important;
  stroke:#fff9ed !important;
  stroke-width:1.9 !important;
  fill:none !important;
}
.hnb-ref-sector-line {
  font-size:7.9pt !important;
  line-height:1.3 !important;
  margin-top:1mm !important;
  color:#2a2724 !important;
  white-space:normal !important;
  overflow-wrap:normal !important;
}
.hnb-ref-sector-line b {color:#af8441 !important; padding:0 .45mm !important;}
@media screen {
  .hnb-ref-masthead { margin-bottom:6mm !important; }
}
/* HNB_HEADER_V24_END */

</style>
