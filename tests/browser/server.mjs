import { createServer } from 'node:http';
import { mkdtempSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join } from 'node:path';
import { startTestSmtp } from './smtp.mjs';
import { spawn, spawnSync } from 'node:child_process';
import process from 'node:process';
const directory = mkdtempSync(join(tmpdir(), 'itechorbit-browser-'));
for (const name of ['sessions', 'cache']) mkdirSync(join(directory, name));
const database = join(directory, 'database.sqlite'); writeFileSync(database, '');
const mailbox = process.env.ITECHORBIT_TEST_MAILBOX_PATH ?? join(directory, 'messages.jsonl');
if (mailbox !== join(directory, 'messages.jsonl') && (dirname(dirname(mailbox)) !== tmpdir() || !basename(dirname(mailbox)).startsWith('itechorbit-mailbox-'))) throw new Error('Expected an isolated test mailbox.');
writeFileSync(mailbox, '');
const smtp = await startTestSmtp(mailbox);
const env = { ...process.env, AI_PRO_ENABLED: 'true', AI_PRO_OPENAI_API_KEY: '', AI_PRO_GEMINI_API_KEY: '', AI_PRO_PROVIDER: 'openai', AI_PRO_EMBEDDING_PROVIDER: 'openai', APP_ENV: 'testing', APP_DEBUG: 'false', APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', APP_URL: 'http://127.0.0.1:8010', DB_CONNECTION: 'sqlite', DB_DATABASE: database, DATABASE_URL: '', CACHE_DRIVER: 'file', CACHE_STORE: 'file', CACHE_PATH: process.env.ITECHORBIT_BROWSER_CACHE_PATH ?? join(directory, 'cache'), SESSION_DRIVER: 'file', SESSION_FILES: join(directory, 'sessions'), SESSION_COOKIE: 'itechorbit_browser_test', SESSION_SECURE_COOKIE: 'false', QUEUE_CONNECTION: 'database', MAIL_MAILER: 'smtp', MAIL_HOST: '127.0.0.1', MAIL_PORT: String(smtp.port), MAIL_USERNAME: '', MAIL_PASSWORD: '', MAIL_ENCRYPTION: 'null', MAIL_FROM_ADDRESS: 'notices@example.test', MAIL_FROM_NAME: 'iTechOrbit', NOTICE_MAIL_PER_MINUTE: '120', SITE_OWNER_ID: '1', INERTIA_SSR_ENABLED: 'true', INERTIA_SSR_URL: 'http://127.0.0.1:13714', INERTIA_SSR_THROW_ON_ERROR: 'true', INERTIA_DEVTOOLS_ENABLED: 'false' };
// Synthetic OAuth configuration enables UI/redirect coverage; browser tests intercept provider hosts.
for (const provider of ['GOOGLE', 'GITHUB', 'LINKEDIN']) {
  env[`${provider}_CLIENT_ID`] = 'browser-test-client'; env[`${provider}_CLIENT_SECRET`] = 'browser-test-secret';
  env[`${provider}_REDIRECT_URI`] = `http://127.0.0.1:8010/login/${provider.toLowerCase()}/callback`;
}
if (env.CACHE_PATH !== join(directory, 'cache') && (dirname(env.CACHE_PATH) !== tmpdir() || !basename(env.CACHE_PATH).startsWith('itechorbit-browser-cache-'))) throw new Error('Expected a disposable browser cache directory.');
const result = spawnSync('php', ['tests/browser/prepare.php'], { env, stdio: 'inherit' });
if (result.status !== 0) { rmSync(directory, { recursive: true }); process.exit(result.status ?? 1); }
// Serve the external embed fixture from a real loopback origin so browser local
// network protections classify both sides correctly; no API responses are mocked.
const external = createServer((_req, response) => {
  response.setHeader('Content-Type', 'text/html');
  response.end('<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><title>External website</title></head><body><h1>External website</h1><script src="http://127.0.0.1:8010/ai-pro/widget.js" data-site="browser-remote" defer></script></body></html>');
});
await new Promise((resolve, reject) => { external.once('error', reject); external.listen(8011, '127.0.0.1', resolve); });
const worker = spawn('php', ['artisan', 'queue:work', 'mail_notices', '--queue=mail-notices', '--sleep=1', '--timeout=60', '--tries=3'], { env, stdio: ['ignore', 'ignore', 'pipe'] });
worker.stderr.on('data', chunk => { if (/fatal|error/i.test(String(chunk))) process.stderr.write(chunk); });
const ssr = spawn('node', ['bootstrap/ssr/ssr.js'], { env, stdio: 'inherit' });
// PHP starts after SSR is accepting requests; readiness is checked through the HTTP health route.
// Spawn servers directly so cleanup stops the actual listeners, not just Artisan parents.
const server = spawn('php', ['-S', '127.0.0.1:8010', '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], { cwd: join(process.cwd(), 'public'), env, stdio: ['ignore', 'ignore', 'pipe'] });
server.stderr.on('data', chunk => { if (/fatal|error|failed/i.test(String(chunk))) process.stderr.write(chunk); });
function stop() { external.close(); server.kill('SIGTERM'); ssr.kill('SIGTERM'); worker.kill('SIGTERM'); smtp.server.close(); rmSync(directory, { recursive: true, force: true }); if (env.CACHE_PATH !== join(directory, 'cache')) rmSync(env.CACHE_PATH, { recursive: true, force: true }); if (mailbox !== join(directory, 'messages.jsonl')) rmSync(dirname(mailbox), { recursive: true, force: true }); }
process.on('SIGTERM', () => { stop(); process.exit(0); }); process.on('SIGINT', () => { stop(); process.exit(0); });
server.on('exit', code => { stop(); process.exit(code ?? 0); });
