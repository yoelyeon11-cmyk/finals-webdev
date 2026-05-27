# Order status push notifications (Cloudrobe)

When staff updates an order status in the admin panel, customers who placed that order on the **mobile app** receive a push notification (if their device registered an FCM token).

## Flow

1. User logs into the mobile app → app requests notification permission → FCM device token is saved via `POST /api/v1/me/fcm-token`.
2. Admin changes order status → `OrderStatusPushNotifier` finds the user by **order customer email** and sends FCM.
3. Phone shows: *"Order TXN-… is now: Preparing Order"* (etc.).

## Railway environment variables

Set **one** of these on the **finals-webdev** service:

### Option A — Service account (recommended)

1. Firebase Console → Project settings → **Service accounts** → **Generate new private key** (JSON).
2. In Railway → **Variables**, add:
   - `FIREBASE_SERVICE_ACCOUNT_JSON` = entire JSON file contents (one line is fine)
   - `FIREBASE_PROJECT_ID` = `cloudrobe-bd8af` (optional if already in JSON)

### Option B — Legacy server key

1. Firebase → Project settings → **Cloud Messaging** (enable Cloud Messaging API if needed).
2. Copy **Server key** (legacy).
3. Railway variable: `FCM_LEGACY_SERVER_KEY`

## Database migration

After deploy, run migrations on Railway (or locally):

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

Adds `user.fcm_token`.

## Mobile app

Rebuild/release APK after pulling frontend changes so tokens register on login.

## Troubleshooting

- **No push:** User must open app while logged in once (registers token). Order email must match account email.
- **Admin updated status, still nothing:** Check Railway logs for `FCM not configured` or send errors.
- **Test:** Update an order for a user who ordered from the app; confirm that user logged in on phone after installing latest APK.
