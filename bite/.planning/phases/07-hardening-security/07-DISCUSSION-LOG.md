# Phase 7: Hardening & Security - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-27
**Phase:** 07-hardening-security
**Areas discussed:** Health check depth, Rate limiting strategy, Logging & PII handling, Tenant isolation response

---

## Health Check Depth

### What should GET /health verify?

| Option | Description | Selected |
|--------|-------------|----------|
| Full stack check | DB + GCS storage + GD/WebP + queue. Container restarts on any failure. | ✓ |
| DB + GD only | Database and GD extension. Storage/queue failures don't trigger restarts. | |
| DB only (minimal) | Only database connectivity. Other failures logged to Sentry. | |

**User's choice:** Full stack check
**Notes:** None — clean selection of recommended option.

### Response detail level

| Option | Description | Selected |
|--------|-------------|----------|
| Detailed JSON | Status per component with latency. Exposes internals. | ✓ |
| Detailed in non-prod only | Full JSON in staging, minimal in production. | |
| Pass/fail only | HTTP 200 or 503 with minimal body. | |

**User's choice:** Detailed JSON
**Notes:** None.

### Authentication

| Option | Description | Selected |
|--------|-------------|----------|
| Public (no auth) | Cloud Run probes can't pass auth headers. | ✓ |
| Public with rate limit | Public but rate-limited to prevent scanning. | |

**User's choice:** Public, no authentication
**Notes:** None.

---

## Rate Limiting Strategy

### Guest ordering threshold

| Option | Description | Selected |
|--------|-------------|----------|
| Moderate (~10/15min per IP) | Handles groups on same WiFi. | ✓ |
| Strict (~5/15min per IP) | Tighter but could block busy tables. | |
| Per-session (tracking token) | Avoids WiFi issues but needs session tracking. | |

**User's choice:** Moderate (~10 orders per 15 minutes per IP)
**Notes:** None.

### Webhook rate limiting

| Option | Description | Selected |
|--------|-------------|----------|
| High threshold (~60/min per IP) | Handles Stripe bursts. Idempotency table prevents duplicates. | ✓ |
| IP allowlist instead | Only accept Stripe IPs. No rate limit needed. | |
| Both | Defense in depth. | |

**User's choice:** High threshold
**Notes:** None.

### 429 response format

| Option | Description | Selected |
|--------|-------------|----------|
| Friendly message | User-friendly page with Retry-After header. | ✓ |
| Standard JSON error | Consistent with API patterns. | |
| Claude decides | Context-dependent response format. | |

**User's choice:** Friendly user message
**Notes:** None.

### Web login rate limiting

| Option | Description | Selected |
|--------|-------------|----------|
| Laravel's built-in | Breeze default: 5 attempts/min per email+IP. | ✓ |
| Custom thresholds | 3 attempts/5 min, exponential backoff. | |
| Account lockout | Lock account after N failures. | |

**User's choice:** Use Laravel's built-in
**Notes:** None.

---

## Logging & PII Handling

### PII in logs

| Option | Description | Selected |
|--------|-------------|----------|
| Mask PII | Partial masking: phone/email/IP redacted. | ✓ |
| Full PII | Log everything as-is. | |
| No PII | Strip all PII, use opaque IDs only. | |

**User's choice:** Mask PII
**Notes:** None.

### Request logging scope

| Option | Description | Selected |
|--------|-------------|----------|
| Errors + slow requests | Exceptions + requests >2s. Lean production logs. | ✓ |
| All requests | Every HTTP request logged. High volume. | |
| Errors only | Only exceptions and 5xx. Minimal. | |

**User's choice:** Errors + slow requests
**Notes:** None.

### Sentry scope

| Option | Description | Selected |
|--------|-------------|----------|
| Errors + sampled traces | All errors, ~10% performance traces. | ✓ |
| Errors only | Just exceptions. No performance monitoring. | |
| Full tracing | 100% of requests traced. | |

**User's choice:** Errors + sampled traces (~10%)
**Notes:** None.

---

## Tenant Isolation Response

### Gap handling strategy

| Option | Description | Selected |
|--------|-------------|----------|
| Fix all before deploy | Pre-deploy gate. Every gap fixed. No runtime detection. | ✓ |
| Runtime middleware guard | Auto-inject shop_id. Catches gaps at runtime. | |
| Log and alert | Detect unscoped queries, alert via Sentry. | |

**User's choice:** Fix all gaps before deploy
**Notes:** None.

### Audit type

| Option | Description | Selected |
|--------|-------------|----------|
| Audit + regression tests | Code review, fix, then write tests preventing regression. | ✓ |
| One-time code review | Manual audit and fix. No ongoing protection. | |
| Static analysis rule | Custom PHPStan rule to flag unscoped queries. | |

**User's choice:** Audit + regression tests
**Notes:** None.

### Input validation scope

| Option | Description | Selected |
|--------|-------------|----------|
| All components | Sweep all Livewire components including admin-facing. | ✓ |
| Public-facing only | GuestMenu, PinLogin, webhooks, registration. | |
| Public + file uploads | Public endpoints plus file-handling components. | |

**User's choice:** All components
**Notes:** None.

---

## Claude's Discretion

- Startup env validation approach and mandatory env var list
- JSON logging format specifics
- PII masking implementation approach
- Health check timeout thresholds
- Rate limit implementation details (middleware vs route-level)
- Sentry configuration beyond DSN and sample rates
- Tenant isolation test patterns
- Per-component validation rules

## Deferred Ideas

None — discussion stayed within phase scope.
