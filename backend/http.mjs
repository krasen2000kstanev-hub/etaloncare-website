import { ApiError } from './service.mjs';
const headers = { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store', 'X-Content-Type-Options': 'nosniff', 'Referrer-Policy': 'no-referrer' };
export function makeHandler(service, siteUrl) {
  return async event => {
    if (event.task === 'reconcile') return service.reconcile(); // IAM-only direct Lambda invocation.
    const method = event.requestContext?.http?.method, path = event.rawPath;
    try {
      const raw = event.isBase64Encoded ? Buffer.from(event.body || '', 'base64').toString('utf8') : (event.body || '');
      if (Buffer.byteLength(raw) > 12000) throw new ApiError(413, 'Заявката е твърде голяма.');
      let data;
      if (method === 'GET' && path === '/api/seats') data = await service.availability();
      else if (method === 'POST' && path === '/api/orders') {
        if (event.headers?.origin !== new URL(siteUrl).origin) throw new ApiError(403, 'Невалиден източник на заявката.');
        if (!event.headers?.['content-type']?.startsWith('application/json')) throw new ApiError(415, 'Използвайте JSON.');
        data = await service.create(JSON.parse(raw), event.requestContext.http.sourceIp);
      } else if (method === 'GET' && /^\/api\/orders\/[a-f0-9-]{36}$/.test(path)) {
        const token = event.headers?.authorization?.replace(/^Bearer /, '');
        data = await service.status(path.split('/').pop(), token);
      } else if (method === 'POST' && path === '/api/payment/callback') {
        const params = new URLSearchParams(raw), fields = {};
        for (const [key, value] of params) { if (Object.hasOwn(fields, key)) throw new ApiError(400, 'Дублирани параметри.'); fields[key] = value; }
        const order = await service.bankResult(fields);
        return { statusCode: 303, headers: { ...headers, Location: `${siteUrl}/payment.html#order=${encodeURIComponent(order.id)}` }, body: '' };
      } else throw new ApiError(404, 'Страницата не е намерена.');
      return { statusCode: 200, headers, body: JSON.stringify(data) };
    } catch (error) {
      const status = error instanceof SyntaxError ? 400 : error.status || 503;
      // Never log request bodies, names, payment details or private keys.
      if (!error.status && !(error instanceof SyntaxError)) console.error('API failure:', error.name);
      return { statusCode: status, headers, body: JSON.stringify({ message: error.status ? error.message : status === 400 ? 'Невалидна заявка.' : 'Системата временно не е достъпна. Опитайте по-късно.' }) };
    }
  };
}
