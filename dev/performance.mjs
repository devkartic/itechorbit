import { spawnSync } from 'node:child_process';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import process from 'node:process';
import { chromium } from '@playwright/test';

const output = resolve(process.argv[2] || '/tmp/itechorbit-performance');
const base = process.env.PERFORMANCE_BASE_URL || 'http://127.0.0.1:8010';
const routes = {
  home: '/', article: '/blogs/1', service: '/services/laravel-development', products: '/products',
  ...Object.fromEntries(['studio', 'executive', 'blueprint', 'editorial', 'spotlight'].map(template => [template, `/portfolios/SPARSE-${template.toUpperCase()}`])),
};
mkdirSync(output, { recursive: true });
const metrics = ['first-contentful-paint', 'largest-contentful-paint', 'total-blocking-time', 'cumulative-layout-shift'];
const summary = {};
for (const [name, path] of Object.entries(routes)) {
  const runs = [];
  for (let run = 1; run <= 3; run++) {
    const file = join(output, `${name}-${run}.json`);
    const result = spawnSync('npm', ['exec', '--yes', '--package=lighthouse@13.5.0', '--', 'lighthouse', new URL(path, base).href,
      '--only-categories=performance', '--chrome-flags=--headless --no-sandbox', '--output=json', `--output-path=${file}`, '--quiet'], {
      stdio: 'inherit', env: { ...process.env, CHROME_PATH: chromium.executablePath() },
    });
    if (result.error) throw result.error;
    if (result.status !== 0) process.exit(result.status || 1);
    const report = JSON.parse(readFileSync(file, 'utf8'));
    if (report.runtimeError) throw new Error(report.runtimeError.message);
    runs.push(Object.fromEntries(metrics.map(metric => [metric, report.audits[metric].numericValue])));
  }
  summary[name] = Object.fromEntries(metrics.map(metric => [metric, runs.map(run => run[metric]).sort((a, b) => a - b)[1]]));
  console.log(name, summary[name]);
}
writeFileSync(join(output, 'summary.json'), JSON.stringify({ base, lighthouse: '13.5.0', runs: 3, medians: summary }, null, 2));
