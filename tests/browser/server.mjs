import { mkdtempSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { spawn, spawnSync } from 'node:child_process';
import process from 'node:process';
const directory = mkdtempSync(join(tmpdir(), 'itechorbit-browser-'));
for (const name of ['sessions', 'cache']) mkdirSync(join(directory, name));
const database = join(directory, 'database.sqlite'); writeFileSync(database, '');
const env = { ...process.env, APP_ENV: 'testing', APP_DEBUG: 'false', APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', APP_URL: 'http://127.0.0.1:8010', DB_CONNECTION: 'sqlite', DB_DATABASE: database, DATABASE_URL: '', CACHE_DRIVER: 'file', CACHE_STORE: 'file', CACHE_PATH: join(directory, 'cache'), SESSION_DRIVER: 'file', SESSION_FILES: join(directory, 'sessions'), SESSION_COOKIE: 'itechorbit_browser_test', SESSION_SECURE_COOKIE: 'false', QUEUE_CONNECTION: 'sync', MAIL_MAILER: 'array', SITE_OWNER_ID: '1', INERTIA_SSR_ENABLED: 'true', INERTIA_SSR_THROW_ON_ERROR: 'true', INERTIA_DEVTOOLS_ENABLED: 'false' };
const result = spawnSync('php', ['tests/browser/prepare.php'], { env, stdio: 'inherit' });
if (result.status !== 0) { rmSync(directory, { recursive: true }); process.exit(result.status ?? 1); }
const ssr = spawn('node', ['bootstrap/ssr/ssr.js'], { env, stdio: 'inherit' });
// PHP starts after SSR is accepting requests; readiness is checked through the HTTP health route.
// Spawn servers directly so cleanup stops the actual listeners, not just Artisan parents.
const server = spawn('php', ['-S', '127.0.0.1:8010', '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], { cwd: join(process.cwd(), 'public'), env, stdio: ['ignore', 'ignore', 'pipe'] });
server.stderr.on('data', chunk => { if (/fatal|error|failed/i.test(String(chunk))) process.stderr.write(chunk); });
function stop() { server.kill('SIGTERM'); ssr.kill('SIGTERM'); rmSync(directory, { recursive: true }); }
process.on('SIGTERM', () => { stop(); process.exit(0); }); process.on('SIGINT', () => { stop(); process.exit(0); });
server.on('exit', code => { stop(); process.exit(code ?? 0); });
