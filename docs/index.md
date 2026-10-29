---
layout: home

hero:
  name: Shifty
  text: Shift planning for volunteer teams
  tagline: Volunteers sign up and say when they can work. Admins fill the gaps with suggestions that keep the load fair.
  actions:
    - theme: brand
      text: Deploy it
      link: /deployment
    - theme: alt
      text: GitHub
      link: https://github.com/Thiritin/shifty

features:
  - title: Hourly availability
    details: Volunteers paint the hours they can and cannot work, and see it on every shift.
  - title: Fair suggestions
    details: Each shift ranks candidates by shifts already worked, then by availability, and flags double bookings.
  - title: Your login, your brand
    details: Use passwords, any OpenID Connect provider, or both. Set the name, logos and colour in the admin area.
  - title: One container
    details: Runs on MariaDB, MySQL, PostgreSQL or SQLite, with a browser installer and a monthly rebuilt image.
---

<div class="screens">
  <img class="light-only" src="/screenshots/shift-detail-light.png" alt="Shift detail with suggested candidates">
  <img class="dark-only" src="/screenshots/shift-detail-dark.png" alt="Shift detail with suggested candidates">
  <img class="light-only" src="/screenshots/availability-light.png" alt="Hourly availability grid">
  <img class="dark-only" src="/screenshots/availability-dark.png" alt="Hourly availability grid">
</div>

## Quick start

```bash
curl -fsSLo compose.yml https://raw.githubusercontent.com/Thiritin/shifty/main/deploy/compose/mariadb.yml
printf 'APP_URL=http://localhost:8080\nAPP_KEY=base64:%s\nDB_PASSWORD=%s\n' "$(openssl rand -base64 32)" "$(openssl rand -hex 16)" > .env
docker compose up -d --wait
docker compose logs app | grep "Setup token"
```

Open http://localhost:8080, enter the setup token and create your admin account. [Deployment](/deployment) covers PostgreSQL, Kubernetes, TLS, upgrades and backups.

<style>
.screens { display: grid; gap: 24px; margin: 48px 0; }
.screens img { border-radius: 12px; border: 1px solid var(--vp-c-divider); }
.dark .light-only, html:not(.dark) .dark-only { display: none; }
</style>
