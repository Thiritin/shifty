# Configuration

Shifty splits its configuration in two. The environment holds what the app needs before it can reach the database: the encryption key, the public URL and the database connection. Everything else is stored in the database and edited under **App Settings** in the admin area, so changing the app name or the mail server does not need a restart or a redeploy.

The environment variables are listed in [Deployment](deployment.md#environment-variables).

## Admin settings

**General** sets the app name, the organization shown on the print view, and the timezone used for shift times and the calendar export.

**Shifts** controls whether volunteers can sign themselves up and how many shifts a new user is expected to take. With self sign-up off, only admins assign people. Volunteers can still mark when they are available, and admins see that on the shift detail page.

**Branding** takes a logo for light mode, a logo for dark mode, a favicon and an accent colour. Logos are stored in the database, so they survive container replacements without a volume.

**Authentication** turns password login and single sign-on on or off and holds the OIDC client settings. See [Authentication](authentication.md).

**Mail** sets the SMTP server and sender. The page has a button that sends a test mail to your own address.

## Secrets

The SMTP password and the SSO client secret are encrypted with `APP_KEY` before they are stored. If you change `APP_KEY`, enter both again.
