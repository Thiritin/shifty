# Authentication

Shifty has two ways to sign in: email and password, and single sign-on through any OpenID Connect provider. Password login is on by default, so a fresh install works without any identity provider. Both are switched on and off under **App Settings → Authentication**, and both can run at the same time.

## Passwords

Admins create users with a password on the **Manage Users** page. Users change their own password under their settings. Login attempts are throttled per address.

## Single sign-on

Register Shifty as a client in your provider with this redirect URL:

```
https://shifty.example.org/auth/callback
```

Then fill in the Authentication settings: the provider's discovery URL (it ends in `/.well-known/openid-configuration`), the client ID and secret, the scopes, and the text on the sign-in button. Keycloak, Authentik, Authelia, Zitadel, Entra ID and Google all work this way.

Two switches decide what happens when someone signs in for the first time. **Link by email** attaches the SSO identity to an existing user with the same email address, which is what you want when moving from password accounts to SSO. **Create users** lets anyone your provider accepts get an account. Turn it off to allow only people an admin has already added.

## Restricting access and granting admin

You can require a claim in the ID token before anyone gets in, and grant admin rights based on another. Each rule is a claim name and a value.

The match adapts to the claim's shape. If the claim is a list, such as `groups: ["staff", "crew"]`, the rule matches when any entry equals the value. If it is a string, it is split on commas and spaces first, so `roles: "crew admin"` matches the value `admin`. Nested claims use dots, as in `realm_access.roles`. Values compare exactly.

Most providers only put groups in the token when you ask for them, so add `groups` (or your provider's equivalent) to the scopes.

The admin rule only grants admin. Removing someone from the group does not demote them; do that on the Manage Users page.

## Locked out

If a broken SSO setup locks everyone out, run this on the server. It creates or updates the admin, sets a password, and turns password login back on.

```bash
php artisan shifty:admin you@example.org
```

Leave the password prompt empty to get a generated one.
