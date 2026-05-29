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
- On event, admin tables/cards update in place (no full page reload).
- Mobile app uses React Native `WebSocket` plus HTTP long-poll fallback.

### Event types

- `order.created`, `order.status.updated`
- `custom_request.created`, `custom_request.updated`
- `product.created`, `product.updated`, `product.deleted`
- `inventory.updated` (stock changes)
- `category.created`, `category.updated`, `category.deleted`
- `verification.updated`

## 4) Fallback

If WS is unavailable, admin pages still poll `/admin/realtime/updates`, and mobile keeps long-polling `/api/v1/orders/realtime/events`.

## 5) Railway PHP server (required)

The production start command must use a router that serves static files and forwards the rest to Symfony:

```bash
php -S 0.0.0.0:${PORT} -t public public/router.php
```

Do **not** use `public/index.php` alone as the router — that breaks CSS/JS on reload. Without any router, URLs ending in `.json` (for example `/admin/stats.json`) return **404** and live admin updates will not work.
