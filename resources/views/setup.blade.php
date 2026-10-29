<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; --bg: #fafafa; --card: #fff; --fg: #171717; --muted: #737373; --border: #e5e5e5; --code: #f5f5f5; --danger: #b91c1c; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0a0a0a; --card: #171717; --fg: #fafafa; --muted: #a3a3a3; --border: #262626; --code: #0a0a0a; --danger: #f87171; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: var(--bg); color: var(--fg); font: 15px/1.5 ui-sans-serif, system-ui, sans-serif; }
        main { width: 100%; max-width: 560px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 28px; }
        h1 { margin: 0 0 8px; font-size: 20px; }
        p { margin: 0 0 16px; color: var(--muted); }
        pre { margin: 0 0 16px; padding: 14px 16px; background: var(--code); border: 1px solid var(--border); border-radius: 8px; overflow-x: auto; font: 13px/1.6 ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--fg); }
        .error { color: var(--danger); }
        a.button { display: inline-block; padding: 8px 14px; border-radius: 8px; background: var(--fg); color: var(--bg); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
<main>
    @if ($problem === 'key')
        <h1>Set an application key</h1>
        <p>Add this line to the environment and restart {{ config('app.name') }}.</p>
        <pre>APP_KEY={{ $suggestedKey }}</pre>
    @elseif ($problem === 'database')
        <h1>Connect a database</h1>
        <p>Set these environment variables and restart {{ config('app.name') }}. Use <code>mariadb</code> or <code>pgsql</code> as the connection for MariaDB or PostgreSQL.</p>
        <pre>DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=shifty
DB_USERNAME=shifty
DB_PASSWORD=change-me</pre>
    @else
        <h1>The database is not reachable</h1>
        <p>{{ config('app.name') }} cannot connect to the database{{ $target ? ' at '.$target : '' }}. Check that it is running and that the <code>DB_*</code> environment variables are correct.</p>
        @if ($error)
            <pre class="error">{{ $error }}</pre>
        @endif
        <a class="button" href="{{ request()->fullUrl() }}">Try again</a>
    @endif
</main>
</body>
</html>
