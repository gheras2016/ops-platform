# Performance — why the API is slow on Railway (and how to fix it)

Local is fast, Railway is slow, for the **same code**. That points at the *runtime
environment*, not the application logic. In order of impact:

## 1. Database goes over the PUBLIC proxy (biggest cause)

On Railway, set `DB_HOST` to the **private/internal** host, not the public proxy.

- Public proxy (`*.proxy.rlwy.net`) routes every query out to the public internet
  and back — **~40–150 ms per query**. A request that runs 8 queries pays that 8×.
- Locally the DB is `127.0.0.1` (<1 ms/query), which is exactly why local feels
  instant and Railway doesn't.

**Fix:** in the API service variables, reference the MySQL service's private host.
Railway exposes it as a reference variable, e.g.
`DB_HOST=${{ MySQL.RAILWAY_PRIVATE_DOMAIN }}` (and the matching private `DB_PORT`,
usually `3306`). Keep the public proxy only for connecting from your laptop.
Redeploy after changing. This single change is usually the difference.

## 2. `php artisan serve` is single-threaded

The container runs the **PHP built-in dev server**, which handles **one request at a
time**. The mobile app opens several tabs at once (parallel requests), so they queue
behind each other — each feels slow even when individually quick.

**Fix (pick one):**
- **FrankenPHP / Laravel Octane** — keeps the app booted in memory, serves many
  requests concurrently. Biggest win.
- **php-fpm + nginx** (or `nixpacks` PHP provider defaults) — classic multi-worker.
- Cheapest stopgap: nothing code-side; just be aware single-thread caps throughput.

## 3. Per-request bootstrap not cached (now fixed in deploy)

`railway.toml` now runs `config:cache`, `route:cache`, `view:cache` on every deploy,
so the framework boots from compiled caches instead of re-parsing config/routes each
request. Safe here: no closure routes, no runtime `env()` outside `config/`.

> If you ever add a closure route or call `env()` at runtime, drop `route:cache` /
> `config:cache` respectively, or those calls will read stale/empty values.

## 4. `APP_DEBUG` / logging

On Railway set `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`.
Debug mode collects stack frames and verbose logs on every request.

## 5. N+1 on the inventory list (fixed in code)

`InventoryController@index` / `@lowStock` computed `reservedQty()` per row — one extra
query per part (×20). Now batched into a single query via
`SparePart::attachReserved()`. Over the public proxy this alone was ~20 round trips.

## 6. Deferred work still synchronous (recommended follow-up)

Ticket actions send FCM push **synchronously** inside the request (one HTTP call per
recipient device). To take it off the response path, move notifications to a real
queue (`QUEUE_CONNECTION=database` + a `queue:work` worker + a `jobs` migration) and
mark the notifications `ShouldQueue`. Not done yet because it needs a worker process
and touches the test suite — track separately.

## How to verify BEFORE redeploying to prod

Measure server processing time (TTFB), not total wall time:

```bash
# Repeat 5× against the live API; watch time_starttransfer (server time)
curl -s -o /dev/null -w "ttfb=%{time_starttransfer}s total=%{time_total}s\n" \
  -H "Authorization: Bearer <token>" \
  https://ops-platform-production-3ee3.up.railway.app/api/v1/tickets
```

- Baseline now, change **one** thing (start with DB private host), redeploy, re-measure.
- Expect the biggest drop from the DB host switch; caching shaves bootstrap; the
  concurrent server fixes the "several tabs at once" stalls.
- Locally, confirm query counts with Telescope/Debugbar or `DB::enableQueryLog()` on
  the inventory list — should be a small constant number, not ~20+.
