# Bruno API collection

The collection lives at the repository root in `./bruno`, outside `backend/` and `frontend/`; Docker mounts only those two folders.

1. Copy `environments/local.example.bru` to `environments/local.bru`.
2. Set `adminEmail` to an existing admin account and fill `adminPassword` locally. The `token` value belongs in this ignored local file: Login refreshes it when a real admin password is set; otherwise paste a valid JWT there. Never commit `local.bru` or real credentials.
3. Start the API at `http://localhost:8090`. Bruno CLI is installed globally and uses the included environment.

Run one request or folder with:

```sh
bru run Health/health_get.bru --env local
bru run Users --env local
```

Requests other than Health and the two public auth endpoints require a valid token in `environments/local.bru`. Login refreshes the token from `adminPassword`; if no real admin password is configured, paste a valid JWT into `token`. Keep `local.bru` ignored and never commit it. Several requests create, update, or delete database records, so choose the folder/request before running the full collection.
