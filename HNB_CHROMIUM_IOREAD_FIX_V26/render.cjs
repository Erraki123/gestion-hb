// HN BLOOM - Chromium printToPDF Base64 transport (fixes CDP IO.read failures)
'use strict';
const fs = require('fs');
const path = require('path');
const { pathToFileURL } = require('url');
const puppeteer = require('puppeteer-core');

const [input, output] = process.argv.slice(2);
if (!input || !output || !fs.existsSync(input)) {
  console.error('Usage: node render.cjs input.html output.pdf');
  process.exit(2);
}
const candidates = [
  process.env.HNB_CHROME_PATH,
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
  '/usr/bin/chromium', '/usr/bin/google-chrome'
].filter(Boolean);
const executablePath = candidates.find(candidate => fs.existsSync(candidate));
if (!executablePath) {
  console.error('Chrome/Edge not found; set HNB_CHROME_PATH.');
  process.exit(3);
}

(async () => {
  let browser;
  let page;
  let session;
  try {
    browser = await puppeteer.launch({
      executablePath, headless: true,
      args: ['--no-sandbox','--disable-dev-shm-usage']
    });
    page = await browser.newPage();
    await page.goto(pathToFileURL(path.resolve(input)).href, {
      waitUntil: 'load', timeout: 30000
    });
    await page.evaluate(async () => { await document.fonts.ready; });
    await page.emulateMediaType('print');
    session = await page.createCDPSession();

    // Puppeteer page.pdf() uses an IO.read stream. On some Windows PHP/Node
    // process chains, IO.read fails. CDP base64 avoids that stream entirely.
    const result = await session.send('Page.printToPDF', {
      landscape: false,
      printBackground: true,
      preferCSSPageSize: true,
      paperWidth: 8.2677165,
      paperHeight: 11.6929134,
      marginTop: 0,
      marginRight: 0,
      marginBottom: 0,
      marginLeft: 0,
      displayHeaderFooter: false,
      transferMode: 'ReturnAsBase64'
    });
    if (!result || !result.data) throw new Error('Chromium returned empty PDF data');
    const pdfBytes = Buffer.from(result.data, 'base64');
    if (pdfBytes.length < 500 || pdfBytes.subarray(0, 5).toString() !== '%PDF-') {
      throw new Error('Invalid PDF received from Chromium');
    }
    const target = path.resolve(output);
    fs.mkdirSync(path.dirname(target), { recursive: true });
    fs.writeFileSync(target, pdfBytes);
    console.log('PDF_OK', target);
  } finally {
    if (session) await session.detach().catch(() => {});
    if (page) await page.close().catch(() => {});
    if (browser) await browser.close().catch(() => {});
  }
})().catch(error => {
  console.error('HNB_CHROME_RENDER_ERROR:', error.stack || String(error));
  process.exitCode = 1;
});
