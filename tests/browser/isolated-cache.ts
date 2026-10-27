import { readdirSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, dirname, join } from 'node:path';

export function clearBrowserRateLimits() {
  const cache = process.env.ITECHORBIT_BROWSER_CACHE_PATH;
  if (!cache || dirname(cache) !== tmpdir() || !basename(cache).startsWith('itechorbit-browser-cache-')) throw new Error('Expected an isolated browser cache directory.');
  for (const entry of readdirSync(cache)) rmSync(join(cache, entry), { recursive: true, force: true });
}
