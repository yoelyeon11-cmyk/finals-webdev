# WebSocket Setup (Admin Live Updates)

This project now supports **true WebSocket** live updates for admin pages.

## 1) Deploy a separate WS service on Railway

Create a new Railway service from the Appdev root repo, with:

- **Root directory:** `realtime-websocket` (at Appdev root, outside `finals-webdev`)
- **Build:** Node
- **Start command:** `npm start`

Set variable:

- `WS_BROADCAST_SECRET=<strong-random-secret>`

After deploy, note the WS service public URL, for example:

- `https://cloudrobe-ws-production.up.railway.app`

WebSocket endpoint is:

- `wss://cloudrobe-ws-production.up.railway.app/ws`

## 2) Configure the Symfony web service

In the existing `finals-webdev` Railway web service, set:

- `WS_BROADCAST_URL=https://cloudrobe-ws-production.up.railway.app/publish`
- `WS_BROADCAST_SECRET=<same-secret-as-ws-service>`
- `APP_WS_URL=wss://cloudrobe-ws-production.up.railway.app/ws`

## 3) Behavior

- Mobile creates order/custom request -> Symfony broadcasts event to WS server.
- Admin Order Status page listens for:
  - `order.created`
  - `order.status.updated`
- Admin Custom Request page listens for:
  - `custom_request.created`
- On event, table reloads immediately.

## 4) Fallback

If WS is unavailable, the existing authenticated polling fallback still runs.
