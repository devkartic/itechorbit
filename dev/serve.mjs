import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import process from 'node:process';

// Production assets are intentional: Vite development mode has its own SSR server.
if (existsSync('public/hot')) {
  console.error('Stop Vite and remove its stale public/hot file before running npm run serve.');
  process.exit(1);
}
if (!existsSync('public/build/manifest.json') || !existsSync('bootstrap/ssr/ssr.js')) {
  console.error('Run npm run build before starting the local production preview.');
  process.exit(1);
}

const children = new Set();
let stopping = false;
function stop(code = 0) {
  if (stopping) return;
  stopping = true;
  for (const child of children) {
    try { process.kill(-child.pid, 'SIGTERM'); } catch { child.kill('SIGTERM'); }
  }
  process.exitCode = code;
}
function start(command, args) {
  const child = spawn(command, args, { stdio: 'inherit', env: process.env, detached: true });
  children.add(child);
  child.on('error', error => { console.error(error.message); stop(1); });
  child.on('exit', code => { children.delete(child); if (!stopping) stop(code || 1); });
  return child;
}
process.on('SIGINT', () => stop());
process.on('SIGTERM', () => stop());

// Let Laravel load the configured SSR URL without exposing other environment values.
const { spawnSync } = await import('node:child_process');
const config = spawnSync('php', ['-r', "require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo json_encode(config('inertia.ssr'));"], { encoding: 'utf8' });
if (config.status !== 0) { console.error(config.stderr); process.exit(1); }
const ssr = JSON.parse(config.stdout);
if (!ssr.enabled) { console.error('Enable INERTIA_SSR_ENABLED for this SSR preview.'); process.exit(1); }
const health = new URL('/health', ssr.url);
async function healthy() {
  try { return (await fetch(health, { signal: AbortSignal.timeout(1000) })).ok; } catch { return false; }
}
if (await healthy()) {
  console.error('An SSR service is already running. Stop it before starting this managed preview.');
  process.exit(1);
}
start('php', ['artisan', 'inertia:start-ssr']);
const deadline = Date.now() + 15000;
while (!stopping && !(await healthy())) {
  if (Date.now() > deadline) { console.error('SSR did not become ready within 15 seconds.'); stop(1); break; }
  await new Promise(resolve => setTimeout(resolve, 200));
}
if (!stopping) start('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8000', '--tries=1', '--no-reload']);
