// سرور Mock فینوتک (شاهکار) — فایل سناریو: scenario.txt کنار همین فایل
// سناریوها: match | mismatch | garbage | http500 | notoken
const SCENARIO_FILE = new URL('./scenario.txt', import.meta.url).pathname;
Bun.serve({
  port: 9091,
  idleTimeout: 30,
  async fetch(req) {
    let scenario = 'match';
    try { scenario = (await Bun.file(SCENARIO_FILE).text()).trim() || 'match'; } catch {}
    const url = new URL(req.url);
    const headers = { 'Content-Type': 'application/json' };
    if (url.pathname.endsWith('/oauth2/token')) {
      if (scenario === 'notoken')
        return new Response(JSON.stringify({ errors: [{ code: 'UNAUTHORIZED' }] }), { status: 401, headers });
      return new Response(JSON.stringify({ value: 'mock-token-shahkar', result: { type: 'Successful' } }), { headers });
    }
    if (url.pathname.includes('/shahkar/mobileVerify')) {
      if (scenario === 'mismatch')
        return new Response(JSON.stringify({ value: { isValid: false }, result: { type: 'Successful' } }), { headers });
      if (scenario === 'garbage')
        return new Response(JSON.stringify({ weird: 'response' }), { headers });
      if (scenario === 'http500')
        return new Response(JSON.stringify({ error: 'server error' }), { status: 500, headers });
      return new Response(JSON.stringify({ value: { isValid: true }, result: { type: 'Successful' } }), { headers });
    }
    return new Response(JSON.stringify({ error: 'not found', path: url.pathname }), { status: 404, headers });
  },
});
console.log('finomock on 9091');
