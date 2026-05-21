# Deploy Cloudrobe on Railway

This guide walks you through pushing the repo to GitHub and deploying on [Railway](https://railway.com).

## What was added for Railway

| File | Purpose |
|------|---------|
| `Dockerfile` | Builds PHP app, runs `npm run build`, installs Symfony assets |
| `railway.toml` | Tells Railway to use the Dockerfile and health-check `/api` |
| `scripts/railway-start.sh` | Migrations, JWT keys, cache warmup, PHP built-in server on `$PORT` |
| `.env.railway.example` | List of environment variables to set in Railway |

## Before you push (important)

1. **Do not commit secrets** — Your local `.env` may contain mailer passwords. Railway variables override `.env`, but if secrets are already in git history, rotate them.
2. **JWT keys** (`config/jwt/*.pem`) stay out of git. They are created on the server from `JWT_PASSPHRASE` on first deploy.
3. **`public/build/`** is built inside Docker — you do not need to commit it.

---

## Step 1 — Commit and push to GitHub

Open PowerShell in the **cloudrobe** folder (not the parent `Yanniee` folder):

```powershell
cd C:\Users\Licht\Yanniee\cloudrobe
git status
```

Stage everything you want deployed (including the new Railway files):

```powershell
git add Dockerfile railway.toml .dockerignore scripts/railway-start.sh .env.railway.example RAILWAY_DEPLOY.md config/packages/framework.yaml
git add -u
```

If you also want to include other local changes (API, admin CSS, fixtures, etc.):

```powershell
git add src/ templates/ public/css/ config/
```

Commit:

```powershell
git commit -m "Add Railway deployment config and production setup"
```

Push to your remote (`origin` → `https://github.com/Yoel-ui-yeon/cloudrobe.git`):

```powershell
git push origin main
```

If Git asks you to log in, use a [Personal Access Token](https://github.com/settings/tokens) as the password (not your GitHub account password).

If the branch is new on your machine:

```powershell
git branch -M main
git push -u origin main
```

---

## Step 2 — Create a Railway project

1. Go to [railway.com](https://railway.com) and sign in.
2. **New Project** → **Deploy from GitHub repo**.
3. Authorize GitHub and select **`Yoel-ui-yeon/cloudrobe`** (or your fork).
4. Railway should detect the **Dockerfile** automatically (`railway.toml` sets `builder = "DOCKERFILE"`).

**Monorepo note:** If the repo root is `Yanniee` and `cloudrobe` is only a subfolder, set **Root Directory** to `cloudrobe` in Railway service settings.

---

## Step 3 — Add MySQL

1. In the project, click **+ New** → **Database** → **MySQL**.
2. Open your **web service** → **Variables**.
3. Add a reference to the database URL:
   - Click **+ New Variable** → **Add Reference** → choose **MySQL** → **`MYSQL_URL`** (or `DATABASE_URL` if Railway exposes it that way).
   - Name the variable: `DATABASE_URL`

Symfony expects a URL like:

`mysql://user:pass@host:port/railway?serverVersion=8.0&charset=utf8mb4`

Railway’s MySQL plugin usually provides this; the reference handles it.

---

## Step 4 — Set environment variables

In the **web service** → **Variables**, add (see `.env.railway.example`):

| Variable | Example / notes |
|----------|-----------------|
| `APP_ENV` | `prod` |
| `APP_DEBUG` | `0` |
| `APP_SECRET` | Long random string (32+ chars) |
| `DATABASE_URL` | Reference from MySQL plugin |
| `JWT_PASSPHRASE` | **New** strong passphrase for production (not your dev one) |
| `CORS_ALLOW_ORIGIN` | `^https?://.*` for mobile dev; tighten for production |
| `MAILER_DSN` | Your SMTP credentials |
| `MAILER_FROM_ADDRESS` | e.g. `noreply@yourdomain.com` |
| `MAILER_FROM_NAME` | `Cloudrobe` |

Optional (if you use Google OAuth): `OAUTH_GOOGLE_CLIENT_ID`, `OAUTH_GOOGLE_CLIENT_SECRET`.

Railway sets **`PORT`** automatically — do not override it.

---

## Step 5 — Deploy and verify

1. Trigger a deploy (push to `main` or **Deploy** in Railway).
2. Open **Settings** → **Networking** → **Generate Domain** (e.g. `cloudrobe-production.up.railway.app`).
3. Test:
   - `https://YOUR-DOMAIN.up.railway.app/api` — API entrypoint JSON
   - `https://YOUR-DOMAIN.up.railway.app/api/login` — POST with `{"email":"...","password":"..."}`

**First deploy** runs migrations and creates JWT keys. Check **Deploy Logs** if something fails.

---

## Step 6 — Seed data (optional)

Fixtures are **not** run automatically in production.

To load demo users/products once (staging only):

```bash
# Railway CLI or one-off shell in the service
php bin/console doctrine:fixtures:load --no-interaction
```

Test logins after seeding:

- Admin: `admin@cloudrobe.com` / `admin123`
- Customer: `customer@cloudrobe.com` / `customer123`

---

## Step 7 — Point the mobile app at Railway

In **AppDev**, set the API base URL to your Railway domain, e.g.:

`https://YOUR-DOMAIN.up.railway.app/api`

Update `src/utils/api.js` (or use an env config). For Android emulator, use the public HTTPS URL, not `127.0.0.1`.

---

## Uploads on Railway

Product/profile images are stored under `public/uploads/`. Railway’s filesystem is **ephemeral** — uploads disappear on redeploy. For production, plan to move uploads to S3/R2 or Railway Volumes later.

---

## Troubleshooting

| Issue | What to check |
|-------|----------------|
| Build fails on `npm ci` | Logs — usually missing `vendor` in Docker (fixed in Dockerfile) |
| 500 on boot | Deploy logs — `DATABASE_URL`, `JWT_PASSPHRASE`, migrations |
| CORS errors from app | Set `CORS_ALLOW_ORIGIN` in Railway variables |
| JWT / login fails | Ensure `JWT_PASSPHRASE` is set; redeploy so keys regenerate if needed |
| Health check fails | `/api` must return 200; check PHP errors in logs |

---

## Quick command reference

```powershell
cd C:\Users\Licht\Yanniee\cloudrobe
git add .
git status
git commit -m "Your message"
git push origin main
```

After push, Railway redeploys automatically if the repo is connected.
