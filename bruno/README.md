# Bruno API collection

The collection lives at the repository root in `./bruno`, outside `backend/` and `frontend/`; Docker mounts only those two folders.

1. Copy `environments/local.example.bru` to `environments/local.bru`.
2. Set `adminEmail` to an existing admin account and fill the blank `adminPassword` value locally (comment: fill this in locally; never commit a real password). Keep the local file uncommitted. IDs default to `1`; change them to records in your local database as needed.
3. Start the API at `http://localhost:8090`. Bruno CLI runs can use the included environment.

Run the read-only health folder with:

```sh
npx @usebruno/cli run Health --env local
```

Run all requests with `npx @usebruno/cli run --env local`. Requests other than Health and the two public auth endpoints require a valid login token; Login stores `res.body.token` for subsequent requests. Several requests create, update, or delete database records, so choose the folder/request before running the full collection.
