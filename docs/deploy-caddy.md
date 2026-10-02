# Caddy deployment: agents-dev

## Current setup (read-only inspection, 2026-10-01)

- Caddy `v2.11.4` is running and enabled as `caddy.service`; `caddy validate --config /etc/caddy/Caddyfile` reports `Valid configuration`.
- `/etc/caddy/Caddyfile` has a shared `:8000` HTTP site with path routing for other development apps, plus two explicit `http://…:8000` AulaTools sites. Backups are `/etc/caddy/Caddyfile.bak` and timestamped siblings. There are no imported Caddy files (`import` is not used).
- `agents-dev.hugosantiago.dev` already reaches this host through Cloudflare: Caddy logs show Cloudflare request headers with the public HTTPS scheme, and `cloudflared` is listening locally. Caddy itself serves this site on `:8000`; public TLS is terminated by Cloudflare. DNS records and tunnel configuration were not independently inspected, so do not change them based only on this note.
- Existing Angular development routes (`/vendaly-app/`, `/planeaciones_frontend/`, `/tickets-app/`) preserve their path prefix with `handle`; API routes strip it with `handle_path`. Caddy's reverse proxy forwards the request host and `X-Forwarded-*` headers by default and supports WebSocket upgrades automatically.
- The compose file maps API port `8080` and Angular port `4200` to all host interfaces. At inspection, the backend container was up and 8080 listened; compose had no frontend container and no process listened on 4200. Start the app's compose stack before using these routes.

## Add to the existing `:8000` block

Add the two redirects alongside its other prefix redirects and the handlers before its final catch-all `handle` block. The frontend server is configured to serve under its prefix, so preserve it. The API has ordinary root routes, so strip its public prefix.

```caddy
redir /aulatools-tareas_frontend /aulatools-tareas_frontend/ 308
redir /aulatools-tareas_backend /aulatools-tareas_backend/ 308

# Angular CLI dev server: keep the prefix; reverse_proxy handles HMR WebSockets.
handle /aulatools-tareas_frontend/* {
	reverse_proxy 127.0.0.1:4200
}

# Yii3 routes are rooted at /, so remove the public prefix before proxying.
handle_path /aulatools-tareas_backend/* {
	reverse_proxy 127.0.0.1:8080
}
```

No custom `header_up` directives are needed for either proxy; Caddy supplies the original host and forwarded protocol/client headers. WebSocket upgrade headers are proxied automatically.

## Application settings

Start the Angular dev server in `frontend/` with:

```sh
ng serve --host 0.0.0.0 --base-href /aulatools-tareas_frontend/ --serve-path /aulatools-tareas_frontend/ --allowed-hosts agents-dev.hugosantiago.dev
```

The same flags can be passed through this compose command after updating the service command (or run directly in the container):

```sh
docker compose run --service-ports frontend sh -c 'npm start -- --base-href /aulatools-tareas_frontend/ --serve-path /aulatools-tareas_frontend/ --allowed-hosts agents-dev.hugosantiago.dev'
```

This project uses Angular's current Vite-based `@angular/build:dev-server`; the CLI has `--live-reload` and `--hmr`, but no `--public-host` option. Live reload is enabled by default; WebSocket proxying is automatic, so no public-host or custom websocket-client setting is required. If edits are made through compose, replace the existing `frontend.command` so `docker compose up` starts with these flags.

Set `frontend/src/environments/environment.ts` to:

```ts
export const environment = { apiUrl: 'https://agents-dev.hugosantiago.dev/aulatools-tareas_backend' };
```

`backend/src/Auth/Middleware/CorsMiddleware.php` currently allows only `http://localhost:4200`. Change the allowed origin to `https://agents-dev.hugosantiago.dev` (or allow both origins during local development). Because the frontend and API share an origin in this deployment, browsers do not require CORS for these requests; the backend's fixed CORS header should still match the public origin to avoid breaking cross-origin use and preflight expectations.

## Public exposure

Both the development server and API become publicly reachable. Keep this development-only. Prefer Cloudflare Access to restrict the frontend path to named users. Alternatively, add `basic_auth` inside the frontend `handle` block, with a password hash created by `caddy hash-password`, and distribute credentials privately; this protects only that route. A Caddy `remote_ip` allowlist can be used only when Caddy's trusted-proxy settings make the actual client IP reliable—otherwise Cloudflare's edge IP is what Caddy sees. The API needs its own access control if it must not be public; frontend Basic Auth alone does not protect it.

## Apply later with the user (requires their sudo password)

Do not run these during this read-only review. When ready, run these commands in order and edit the Caddyfile only with the user present:

```sh
sudo cp -a /etc/caddy/Caddyfile "/etc/caddy/Caddyfile.bak.$(date +%Y%m%d%H%M%S)"
sudoedit /etc/caddy/Caddyfile
sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl reload caddy
```

In `sudoedit`, add the redirects and handlers above. Run reload only if validation succeeds.
