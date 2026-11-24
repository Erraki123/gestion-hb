<style>
/* HN BLOOM V22 - clean shared PDF identity; no cumulative overrides */
@page {size:A4 portrait; margin:0;}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;}
body{font-family:DejaVu Sans,sans-serif; font-size:10.5px;line-height:1.53;color:#242521;background:#fffefa;}
table{border-collapse:collapse;width:100%;}
.right{text-align:right;} .center{text-align:center;}
.gold{color:#947241;} .muted{color:#766e63;font-size:8.6px;}
.pagenum:before{content:counter(page);}
/* Gold original artwork, with realistic height for readable security icons. */
.hn22-head{margin:0 27px;position:relative;}
.hn22-art{height:156px;overflow:hidden;border-bottom:1px solid #c8b69a;}
.hn22-art img{display:block;width:100%;height:155px;}
.hn22-document-strip{margin-top:10px;border-spacing:0;border:1px solid #d5c4a9;}
.hn22-document-strip td{vertical-align:middle;}
.hn22-document-name{padding:11px 18px 10px;border-left:5px solid #b18a4f;background:#f6f0e6;}
.hn22-doc-small{font-size:7px;font-weight:bold;letter-spacing:1.45px;color:#a38a68;}
.hn22-doc-title{font:bold 27px DejaVu Serif,serif;color:#2a2926;line-height:1.15;margin-top:4px;letter-spacing:.6px;text-transform:uppercase;}
.hn22-meta{padding:11px 16px;text-align:right;background:#fffefa;border-left:1px solid #d8c9b2;}
.hn22-meta-id{font:bold 14px DejaVu Sans,sans-serif;color:#785932;}
.hn22-meta-date{font-size:9px;line-height:1.45;margin-top:4px;color:#49433b;}
/* Main document content */
.page{padding:18px 27px 130px;position:relative;}
.page>table:first-child{margin-top:4px!important;}
.card{border:1px solid #ccb99e;border-radius:4px;background:#fffdfa;padding:12px 13px 14px;min-height:105px;font-size:10px;line-height:1.6;}
.card-label{margin:-12px -13px 11px;padding:9px 11px 9px 13px;background:#eee5d6;border-bottom:1px solid #dac7a8;border-left:4px solid #ac8650;font-size:10px;color:#54412c;text-transform:uppercase;font-weight:bold;letter-spacing:.7px;}
.card strong{font-size:12px;color:#25221d;}
.hn-card-icon{width:21px;height:21px;vertical-align:middle;margin-right:6px;}
.info td{padding:6px 4px;vertical-align:top;}
.stamp{display:inline-block;padding:5px 9px;border:1px solid currentColor;font-size:7.8px;letter-spacing:.5px;font-weight:bold;}
.stamp-paid{color:#417856}.stamp-late,.void{color:#9c423b}.stamp-draft{color:#947444}.void{font-size:11px;font-weight:bold;}
/* Grids - clear and professional at real A4 size. */
.grid{width:100%;border:1px solid #c3b39d;margin-top:18px;}
.grid thead{display:table-header-group;}
.grid tr{page-break-inside:avoid;}
.grid th{background:#47453f;color:#fffdf8;padding:12px 9px;font-weight:bold;font-size:9px;text-transform:uppercase;letter-spacing:.1px;border-right:1px solid #8c806e;border-bottom:2px solid #b89a64;}
.grid td{padding:11px 9px;font-size:10px;line-height:1.45;border-right:1px solid #e7dac8;border-bottom:1px solid #e6ddd1;vertical-align:top;}
.grid th:last-child,.grid td:last-child{border-right:0;}
.grid tr.alt td{background:#faf7f1;}
.totals{margin-top:15px;border:1px solid #cdb799;background:#fff;}
.totals td{padding:9px 11px;font-size:10px;background:#fffefa;color:#353028;border-bottom:1px solid #e6dbcc;}
.totals .ttc td{padding:12px 11px;background:#e4d4ba;border-top:2px solid #b08c55;font-size:11.5px;font-weight:bold;color:#25211b;}
.totals .ttc td.right{font-size:13.5px;}
/* Keep optional sections/conditions exactly as provided in CRM. */
.hn-section-heading{font-size:11.5px;font-weight:bold;letter-spacing:.8px;color:#745630;text-transform:uppercase;margin:20px 0 7px;border-bottom:1px solid #bda480;padding-bottom:7px;}
.hn-section-heading img{width:20px;height:20px;margin-right:8px;vertical-align:middle;}
.hn-payments{margin-top:8px!important;}.hn-payments th{background:#eae0d0!important;color:#3c3327!important;}
.hn-words{margin:17px 0 0;padding:12px 14px;background:#f7f2e9;border-left:3px solid #a98b60;font-size:10.2px;}
.hn-conditions{margin-top:17px;border:1px solid #d4c2a8;background:#fffdfa;padding:13px 14px;}
.hn-conditions-title{font-weight:bold;color:#765631;font-size:11px;margin-bottom:7px;}
.box{margin:16px 0;padding:13px 14px;border:1px solid #d3c0a5;border-left:3px solid #ae8b54;background:#fdf9f2;}
.big{font:bold 22px DejaVu Serif,serif;color:#7d5d37;margin-top:5px;}
.sign{margin-top:23px;}.sign td{text-align:center;font-size:8.4px;color:#625a4e;vertical-align:top;width:50%;}
.sign-line{border-top:1px solid #baa37f;width:68%;margin:31px auto 0;}
.fill td{padding-top:10px}.fill td.line{border-bottom:1px solid #c9b99e;}
.hn-visual-closure{display:none!important;}
/* Full, unclipped contact information ABOVE the decorative strip. */
.footer.hn22-footer{position:fixed;bottom:0;left:0;right:0;height:117px;margin:0;background:#fffefa;padding:14px 27px 20px;overflow:hidden;color:#35302a;border-top:2px solid #b08e5b;}
.hn22-contact-table{width:100%;table-layout:fixed;position:relative;z-index:2;}
.hn22-contact-table td{vertical-align:top;border:0;font-size:8.6px;padding:0 9px 2px;line-height:1.48;word-wrap:break-word;overflow-wrap:break-word;}
.hn22-contact-left{padding-left:0!important;}.hn22-contact-middle,.hn22-contact-right{border-left:1px solid #d6c2a4!important;}
.hn22-contact-left strong{font-size:10.2px;color:#87633b;letter-spacing:.55px;}
.hn22-address{margin-top:4px;}
.hn22-contact-symbol{color:#987447;font-weight:bold;}
.hn22-legal{font-size:7.5px;line-height:1.3;margin-top:9px;padding-top:6px;border-top:1px solid #ebdfcc;position:relative;z-index:2;}
.hn22-page{float:right;margin-left:8px;color:#756958;}
.hn22-wave{position:absolute;z-index:0;bottom:0;left:0;right:0;height:15px;overflow:hidden;}
.hn22-wave img{display:block;width:100%;height:18px;}
.print-bar{padding:12px 27px;}@media print{.print-bar{display:none;}}
</style>