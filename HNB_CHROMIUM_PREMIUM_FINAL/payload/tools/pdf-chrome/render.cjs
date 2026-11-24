const fs = require('fs');
const path = require('path');
const {pathToFileURL} = require('url');
const puppeteer = require('puppeteer-core');

const [input, output] = process.argv.slice(2);
if (!input || !output || !fs.existsSync(input)) {
  console.error('Usage: node render.cjs path/to/input.html path/to/output.pdf');
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
const executablePath = candidates.find(f => fs.existsSync(f));
if (!executablePath) {
  console.error('Chrome or Edge not found. Set HNB_CHROME_PATH to the browser executable.');
  process.exit(3);
}
(async () => {
  const browser = await puppeteer.launch({executablePath, headless: true, args:['--no-sandbox', '--disable-dev-shm-usage']});
  try {
    const page = await browser.newPage();
    await page.goto(pathToFileURL(path.resolve(input)).href, {waitUntil: 'load', timeout:30000});
    await page.evaluate(() => document.fonts.ready);
    await page.pdf({path: path.resolve(output), format:'A4', printBackground:true, preferCSSPageSize:true,
      displayHeaderFooter:false, margin:{top:'0',right:'0',bottom:'0',left:'0'}});
    console.log('PDF_OK', output);
  } finally { await browser.close(); }
})().catch(e => { console.error(String(e.stack || e)); process.exit(1); });
