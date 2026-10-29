# Deployment

Shifty ships as one container image, `ghcr.io/thiritin/shifty`, built for amd64 and arm64. It needs a database (MariaDB, MySQL, PostgreSQL or SQLite) and a reverse proxy that terminates TLS. Everything else, including sessions, cache, uploaded logos and settings, lives in the database, so the container itself holds no state and can be replaced at any time.

On start the container runs the migrations, caches its config and serves on port 80. The first visit opens the installer, where you create the admin account.

## Docker Compose

The repository has two ready-made Compose files in `deploy/compose/`: `mariadb.yml` and `postgres.yml`. Pick one, put it in an empty directory next to a `.env` file and start it.

```bash
curl -fsSLo compose.yml https://raw.githubusercontent.com/Thiritin/shifty/main/deploy/compose/mariadb.yml
```

```bash
printf 'APP_URL=https://shifty.example.org\nAPP_KEY=base64:%s\nDB_PASSWORD=%s\n' "$(openssl rand -base64 32)" "$(openssl rand -hex 16)" > .env
```

```bash
docker compose up -d --wait
```

Shifty now listens on port 8080. Set `PORT` in `.env` to use a different one.

The installer asks for a setup token so that nobody else can claim a fresh instance before you do. The container prints it in its log on every start until setup is done:

```bash
docker compose logs app | grep "Setup token"
```

## Kubernetes

A small Helm chart lives in `deploy/helm/shifty`. It runs one replica against a database you already have and does not bundle one.

```bash
helm install shifty ./deploy/helm/shifty \
  --set appUrl=https://shifty.example.org \
  --set appKey="base64:$(openssl rand -base64 32)" \
  --set database.host=mariadb.default.svc \
  --set database.password=secret \
  --set ingress.enabled=true --set ingress.host=shifty.example.org
```

Use `database.connection=pgsql` and `database.port=5432` for PostgreSQL. To keep secrets out of your values file, create a Secret with the keys `APP_KEY` and `DB_PASSWORD` and pass its name as `existingSecret`. Read the setup token with `kubectl logs deploy/shifty`.

Keep the chart at one replica. Each container migrates on start, and two doing so at once can collide.

## Environment variables

Only what the app needs before it can reach its database goes in the environment. Everything else is set in the admin area, see [Configuration](configuration.md).

| Variable | Purpose |
| --- | --- |
| `APP_KEY` | Encryption key. Losing it logs everyone out and makes stored secrets such as the SSO client secret unreadable. |
| `APP_URL` | The public URL, used for links in mails and for the SSO callback. |
| `DB_CONNECTION` | `mariadb`, `mysql`, `pgsql` or `sqlite`. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database connection. |
| `DB_URL` | A full connection string such as `postgres://user:pass@host:5432/shifty`, used instead of the separate fields. |
| `PORT` | Port the container listens on, 80 by default. |
| `LOG_CHANNEL` | Set to `stderr` to send logs to the container log. |

If `APP_KEY` is missing or the database cannot be reached, Shifty shows a setup page that says which variable to fix instead of a stack trace.

## Reverse proxy

Point your proxy at the container and forward the usual `X-Forwarded-*` headers. When `APP_URL` starts with `https://`, Shifty builds every link as HTTPS regardless of what the proxy forwards. With Caddy this is the whole config:

```
shifty.example.org {
    reverse_proxy localhost:8080
}
```

## Upgrades

Pull the new image and recreate the container. Migrations run on start.

```bash
docker compose pull && docker compose up -d
```

The `latest` tag follows the newest release and is rebuilt on the first of every month, so pulling it also brings in base image and dependency updates. Pin a version tag such as `1.4` if you want to choose when you upgrade.

### Upgrading an install that used Eurofurence SSO

Older versions hard-coded the Eurofurence SSO group check. It now lives in the admin area under Authentication. A migration copies the old rule (claim `groups`, value `RD01V78YL72JLKOZ`, plus the `groups` scope) into settings on any install that already has SSO users, so access stays restricted after the upgrade. Fresh installs start with no rule.

## Backups

Back up the database. That is everything: users, shifts, availability, settings and logos. The only other thing to keep safe is `APP_KEY`, because encrypted settings cannot be read without it.

```bash
docker compose exec db sh -c 'mariadb-dump -u shifty -p"$MARIADB_PASSWORD" shifty' > shifty.sql
```

## Locked out

If nobody can sign in, for example after misconfiguring SSO, create or reset an admin from the server. This also turns password login back on.

```bash
docker compose exec app php artisan shifty:admin you@example.org
```
