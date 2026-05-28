import express from 'express';
import { WebSocketServer } from 'ws';
import { createServer } from 'http';

const PORT = Number(process.env.PORT || 8080);
const PUBLISH_SECRET = (process.env.WS_BROADCAST_SECRET || '').trim();

const app = express();
app.use(express.json({ limit: '256kb' }));

const server = createServer(app);
const wss = new WebSocketServer({ server, path: '/ws' });

function broadcast(payload) {
  const serialized = JSON.stringify(payload);
  for (const client of wss.clients) {
    if (client.readyState === 1) {
      client.send(serialized);
    }
  }
}

wss.on('connection', ws => {
  ws.send(JSON.stringify({ type: 'hello', ts: Date.now() }));
});

app.get('/health', (_req, res) => {
  res.json({ ok: true, clients: wss.clients.size });
});

app.post('/publish', (req, res) => {
  const secret = (req.header('x-ws-secret') || '').trim();
  if (!PUBLISH_SECRET || secret !== PUBLISH_SECRET) {
    res.status(401).json({ ok: false, error: 'unauthorized' });
    return;
  }

  const type = String(req.body?.type || '').trim();
  const payload = req.body?.payload && typeof req.body.payload === 'object' ? req.body.payload : {};
  if (!type) {
    res.status(422).json({ ok: false, error: 'type is required' });
    return;
  }

  broadcast({ type, payload, ts: Date.now() });
  res.json({ ok: true, clients: wss.clients.size });
});

server.listen(PORT, '0.0.0.0', () => {
  // eslint-disable-next-line no-console
  console.log(`[ws-server] listening on ${PORT}`);
});
