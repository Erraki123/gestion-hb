HN BLOOM V29 MULTI PAGE CHROMIUM

This pack modifies ONLY tools/pdf-chrome/render.cjs.
It preserves the actual Laravel PDF Studio design, logo, and company information.
It renders the real .masthead and .page-footer as Chromium repeated print templates.
Paper A4; header/footer on each page; table headers repeat; totals remain at document end.
Page numbers are printed bottom-right (Page X / Y).
Approval logic and endpoints remain unchanged.

Extract ZIP to Laravel project root and run:
 powershell -ExecutionPolicy Bypass -File .\HNB_CHROME_MULTIPAGE_V29\INSTALL_HNB_CHROME_MULTIPAGE_V29.ps1

Test a one-item quote and a long invoice. Confirm the PDF page count and company footer.
If failure: installer restores the previous render.cjs.
