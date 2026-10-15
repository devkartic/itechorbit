export type ValidationErrors = Record<string, string[]>;
export class RequestError extends Error { constructor(message: string, public errors: ValidationErrors = {}) { super(message); } }
export async function requestJson<T = { success?: string }>(url: string, method: string, data?: FormData | Record<string, unknown>, signal?: AbortSignal) {
  const response = await fetch(url, { method, signal, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '', ...(data instanceof FormData ? {} : { 'Content-Type': 'application/json' }) }, body: data ? data instanceof FormData ? data : JSON.stringify(data) : undefined });
  const json = response.headers.get('content-type')?.includes('application/json') ? await response.json() : {};
  if (!response.ok || response.redirected) throw new RequestError(response.status === 422 ? String(Object.values(json.errors ?? {}).flat()[0] ?? 'Please correct the highlighted fields.') : response.status === 429 ? 'Too many requests. Please try again shortly.' : [401, 419].includes(response.status) || response.redirected ? 'Your session expired. Sign in again and retry.' : response.status === 403 ? 'You do not have permission to perform this action.' : 'The request could not be completed. Please try again.', json.errors ?? {});
  return json as T;
}
