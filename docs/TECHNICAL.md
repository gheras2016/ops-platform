# OPS Platform — Technical Documentation

> Technical reference for the OPS multi-tenant CMMS backend (Laravel) and its
> official Flutter mobile client. Last updated: 2026-06-19.

## 1. Architecture

```
┌─────────────────────────────┐         HTTPS / JSON          ┌──────────────────────────────┐
│  Flutter mobile client       │  ── Bearer token (Sanctum) ──▶│  Laravel API (routes/api.php)  │
│  (Android, Arabic RTL)       │                               │  prefix /api/v1                │
│                              │◀────── JSON resources ────────│                                │
│  riverpod · dio · go_router  │                               │  Controllers → Services →      │
│  flutter_secure_storage      │                               │  Policies → Eloquent (MySQL)   │
└─────────────────────────────┘                               └──────────────────────────────┘
                                                                         │
                                                  Web admin (Blade) shares the SAME
                                                  Services / Policies / Models.
```

**Backend (Laravel 12, PHP 8.x, MySQL/MariaDB):**
- **Controllers** (`app/Http/Controllers/Api/*`) — thin; validate + delegate.
- **Services** (`app/Services/*`) — the single source of truth for business logic
  (e.g. `TicketWorkflowService`, `PartRequestWorkflowService`, `ProcurementService`).
  The mobile API and the web app call the **same** services — only the transport differs.
- **Policies** (`app/Policies/TicketPolicy`) + **Gates** (`AuthServiceProvider`) — authorization.
- **Resources** (`app/Http/Resources/*`) — JSON shaping.
- **Multi-tenancy** — `BelongsToCompany` global scope keys every query to the
  authenticated user's `company_id`; cross-company access returns 404.

**Mobile (Flutter):** feature-first — `lib/features/<feature>/{data,application,presentation}`,
plus `lib/core` (config/network/storage), `lib/app` (router/theme), `lib/shared`.
State = riverpod; HTTP = dio; navigation = go_router; secure token = flutter_secure_storage.

---

## 2. Authentication flow

Token auth via **Laravel Sanctum** (`HasApiTokens` on `User`).

```
LOGIN
  POST /api/v1/auth/login { email, password, device_name }
    → validate credentials
    → reject if company inactive OR user inactive (EnsureActiveAccount)
    → issue personal access token
    ← 200 { token, user: { id, name, email, company, roles, abilities } }

AUTHENTICATED REQUESTS
  Header: Authorization: Bearer <token>
  Middleware group: ['auth:sanctum', 'active']
    - auth:sanctum   → resolves the user from the token
    - active         → re-checks the account is still active (JSON 403 if not)

SESSION (mobile)
  - Token stored in flutter_secure_storage.
  - On launch the app restores the token and calls GET /auth/me.
  - A 401 from any call → AuthEvents.onSessionExpired → router redirects to login.

LOGOUT
  POST /api/v1/auth/logout → revokes the current access token (row deleted).
```

`abilities` are coarse capability flags computed from Gates, snapshotted at login:
`platform-access, admin-access, view-reports, inventory-access, view-inventory, finance-access`.
> ⚠️ Because abilities are snapshotted at login, granting a new ability requires the user
> to re-login (or refresh via `/auth/me`) before the UI reflects it.

---

## 3. Permission system

**Roles** (spatie/laravel-permission): `super_admin, company_admin, department_head,
technician, requester, warehouse_manager, finance_manager`.

**Gates** (`app/Providers/AuthServiceProvider.php`): a `Gate::before` grants `super_admin`
everything. Coarse gates:

| Gate | Passes for |
|------|-----------|
| `admin-access` | company/super admin |
| `view-reports` | admin or department head |
| `inventory-access` | admin or warehouse manager (manage inventory) |
| `view-inventory` | admin, warehouse, department head, technician, finance (read-only field visibility) |
| `finance-access` | admin or finance manager |

**Ticket policy** (`TicketPolicy`) — fine-grained, per ticket:

| Ability | Rule |
|---------|------|
| `view` | admin, department head of the ticket's dept, creator, assigned technician (+ warehouse when a part request exists) |
| `update` | admin / managing head |
| `work` | admin OR the assigned technician (accept/start/pause/resume/progress/resolve, manage used parts) |
| `assign` | admin OR managing department head |
| `approve` | admin OR managing department head (approve/reject a resolved ticket) |
| `cancel` | admin, managing head, or the creator |
| `comment` | anyone who can `view` |

The mobile **never decides** which buttons are legal — the API computes
`available_actions` per ticket from policy + state, and the app renders from it.

---

## 4. Ticket lifecycle

Statuses: `open → assigned → accepted → in_progress ⇄ paused → resolved → closed`
(plus `rejected`-back-to-`in_progress`, and `cancelled`).

```
 open ──assign──▶ assigned ──accept──▶ accepted ──start──▶ in_progress ──resolve──▶ resolved
   │                                                          │   ▲                    │  │
 cancel                                                    pause resume          approve reject
   │                                                          ▼   │                    │  │
 cancelled                                                  paused                  closed in_progress
```

All transitions run through `TicketWorkflowService` inside DB transactions. Each writes a
`TicketEvent` (audit timeline) and sends a `TicketNotification` to the relevant participants.

**Spare parts at close:** used catalogue parts are recorded as *pending* while working
(`ticket_spare_parts.deducted_at = null`) and are drawn from stock only at **approve/close**
(`deductUsedParts()` → decrement `spare_parts.quantity` + write a `StockTransaction`
linked to the ticket, then set `deducted_at`). Custom (non-catalogue) lines never move stock.
Warehouse-issued parts are marked already-deducted so close never double-deducts.

---

## 5. Notification system

Backed by Laravel **database notifications** (`notifications` table; `User` is `Notifiable`).
`TicketWorkflowService` / `PartRequestWorkflowService` raise `TicketNotification` with a
`data` payload `{ ticket_id, ticket_number, ticket_title, event, message, actor, icon, color, url }`.

Events: `assigned, accepted, started, paused, resumed, resolved, approved, rejected, commented,
progress, part_requested, part_approved, part_rejected, part_issued, procurement, …`.

**Mobile delivery (current):** the app polls the API — unread badge on the bell tab, list with
mark-read. While foreground, a watcher raises a **local notification** (sound + status bar) for
new items and deep-links to the ticket on tap.

**Background/closed-app push (next step):** requires Firebase Cloud Messaging — register a
device token on login, fan out `TicketNotification` to FCM, and handle the message in a
background isolate. See the mobile `NOTIFICATIONS.md` for the integration checklist.

---

## 6. API endpoints (v1)

Base URL: `/api/v1`. All except `auth/login` require `Authorization: Bearer <token>`
and the `active` middleware. List endpoints return Laravel pagination
(`{ data:[…], links, meta:{ current_page, last_page, total } }`).

### Auth
| Method | Path | Notes |
|--------|------|-------|
| POST | `/auth/login` | `{ email, password, device_name }` → `{ token, user }` |
| GET | `/auth/me` | current `{ user }` |
| POST | `/auth/logout` | revoke current token |

### Tickets
| Method | Path | Notes |
|--------|------|-------|
| GET | `/tickets` | filters: `status, search, department, priority, page` (role-scoped) |
| POST | `/tickets` | create; body `{ title, department_id, description?, priority_id?, location_id?, location_detail? }` |
| GET | `/tickets/{ticket}` | full detail: permissions, `available_actions`, events, comments, spare_parts, part_requests, `category`/`category_label` |
| GET | `/tickets/meta` | departments, priorities, locations for the create form |
| GET | `/dashboard/stats` | counts by status + headline metrics |

### Ticket actions (POST `/tickets/{ticket}/…`)
`assign` `{ technician_id, priority_id?, due_at?, note? }` · `accept` · `start` · `pause`
`{ reason_code, reason? }` · `resume` · `progress` `{ progress }` · `resolve`
`{ resolution_note?, parts?[] }` · `approve` `{ note? }` · `reject` `{ reason }` ·
`cancel` `{ reason? }` · `comment` `{ body, is_internal? }`. Each returns the refreshed detail.

### Spare parts
| Method | Path | Notes |
|--------|------|-------|
| GET | `/spare-parts` | catalogue search `?q=&department=` |
| GET/POST | `/tickets/{ticket}/spare-parts` | list / record a used part (catalogue or custom) |
| DELETE | `/tickets/{ticket}/spare-parts/{sparePart}` | remove a *pending* used part |
| GET/POST | `/tickets/{ticket}/part-requests` | list / raise a non-catalogue request |

### Notifications
| Method | Path | Notes |
|--------|------|-------|
| GET | `/notifications` | `?unread=1` filter |
| GET | `/notifications/unread-count` | `{ count }` |
| POST | `/notifications/read-all` | |
| POST | `/notifications/{id}/read` | |

### Inventory (read-only; gated by `view-inventory`)
| Method | Path | Notes |
|--------|------|-------|
| GET | `/inventory` | search `q`, filter `category`, `low_stock`, paginated |
| GET | `/inventory/summary` | `{ total_parts, low_stock_count, out_of_stock_count }` |
| GET | `/inventory/low-stock` | parts at/below min stock |
| GET | `/inventory/categories` | spare categories for the filter |
| GET | `/inventory/{sparePart}` | detail + last 10 movements + reserved/available |
| GET | `/inventory/{sparePart}/movements` | full paginated movement history |

---

## 7. Testing

`php artisan test` — 78 feature tests (auth, tickets, lifecycle, notifications, in-ticket
spare parts, part-request workflow, inventory, multi-tenant, procurement, import/export,
route smoke). Mobile: `flutter analyze` (clean) + `flutter test`.

The test DB is the same MySQL/MariaDB connection (`ops_platform`); the server must be running.
