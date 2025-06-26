import fs from 'fs';
import { launch } from 'chrome-launcher';
import lighthouse from 'lighthouse';

const pages = [
  'baseline-lazy',
  'baseline-eager',
  'baseline-eager-fade-in',
  'lqip-bmp',
  'lqip-png',
  'lqip-webp',
  'lqip-webp-smooth',
  'lqip-ultimate',
  'lqip-ultimate-blur',
  'blurhash',
  'sqip',
  'css-lqip',
];

const baseUrl = 'https://3523781-drupal-lqip.elementalidad.com';

const run = async () => {
  const results = [];
  const chrome = await launch({ chromeFlags: ['--headless'] });
  const opts = {
    port: chrome.port,
    output: 'json',
    onlyCategories: ['performance'],
    onlyAudits: ['largest-contentful-paint', 'first-contentful-paint', 'interactive', 'speed-index'],
  };

  for (const page of pages) {
    const url = `/${page}.php?delay=1000`;
    try {
      const runnerResult = await lighthouse(`${baseUrl}${url}`, opts);

      const lcp = runnerResult.lhr.audits['largest-contentful-paint'].numericValue;
      const fcp = runnerResult.lhr.audits['first-contentful-paint'].numericValue;
      const tti = runnerResult.lhr.audits['interactive'].numericValue;
      const si = runnerResult.lhr.audits['speed-index'].numericValue;
      const score = runnerResult.lhr.categories.performance.score;
      const errorMessage = runnerResult.lhr.audits['speed-index'].errorMessage;

      results.push({
        page,
        url,
        lcp,
        fcp,
        tti,
        si,
        score,
        errorMessage,
      });
      if (errorMessage) {
        console.error(`Error auditing ${url}:`, errorMessage);
      }
      else {
        console.log(`Audited ${url}`);
      }

    } catch (err) {
      results.push({
        page,
        url,
        score: null,
        errorMessage: err.message || String(err),
      });
      console.error(`Error auditing ${url}:`, err.message || err);
    }
  }

  await chrome.kill();
  fs.writeFileSync('lighthouse-results.json', JSON.stringify(results, null, 2));
};

run();
