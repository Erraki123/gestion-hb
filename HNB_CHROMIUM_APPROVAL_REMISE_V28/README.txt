HN BLOOM V28
- Commercial PDF Studio quote download after quote director approval.
- Customer dispatch remains protected by DocumentReleaseApproval.
- PDF Studio Remise column displays only if at least one quote/invoice item has a non-zero discount.
- Other permissions and accounting fields unchanged.
- Safe conditional install + backup + lint + view cache.

PowerShell from project root:
Expand-Archive "$env:USERPROFILE\Downloads\HNB_CHROMIUM_APPROVAL_REMISE_V28.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\HNB_CHROMIUM_APPROVAL_REMISE_V28\INSTALL_HNB_V28.ps1

Admin / commercial HTML preview:
/admin/pdf-studio/quote/50
PDF download (after approved quote, for owner):
/admin/pdf-studio/quote/50?download=1

WARNING: Chromium PDF download is not dispatching the file to a client. Separate release approval is still required to send the file.
