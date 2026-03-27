---
phase: 07-hardening-security
plan: 02
subsystem: logging
tags: [logging, pii, sentry, cloud-run, structured-json, middleware]
dependency_graph:
  requires: []
  provides: [PiiMaskingProcessor, LogSlowRequests, stackdriver-channel]
  affects: [config/logging.php, config/sentry.php, bootstrap/app.php]
tech_stack:
  added: []
  patterns: [Monolog ProcessorInterface, GoogleCloudLoggingFormatter, Laravel global middleware]
key_files:
  created:
    - app/Logging/PiiMaskingProcessor.php
    - app/Http/Middleware/LogSlowRequests.php
    - tests/Unit/PiiMaskingProcessorTest.php
    - tests/Feature/StructuredLoggingTest.php
    - tests/Feature/LogSlowRequestsTest.php
  modified:
    - config/logging.php
    - config/sentry.php
    - bootstrap/app.php
decisions:
  - "Stackdriver channel level defaults to 'error' — only errors and slow-request warnings surface in production, reducing Cloud Logging costs"
  - "PiiMaskingProcessor masks last two IP octets (192.168.***) not just the last one — more privacy-preserving"
  - "LogSlowRequests tests use direct middleware invocation with usleep() for deterministic timing — avoids real HTTP round-trip overhead"
metrics:
  duration: 600s
  completed: "2026-03-27T15:48:46Z"
  tasks_completed: 2
  files_changed: 8
---

# Phase 07 Plan 02: Structured Logging, PII Masking, and Sentry Tuning Summary

Structured JSON logging for Cloud Logging with PII masking (phone/email/IP), slow request detection middleware registered globally, and Sentry performance trace sampling defaulting to 10%.

## What Was Built

### Task 1: PiiMaskingProcessor, stackdriver channel, Sentry trace sampling

**`app/Logging/PiiMaskingProcessor.php`** — Monolog processor implementing `ProcessorInterface`. Masks three PII field types in log context before emission:
- `phone`: keeps country code prefix and last 4 digits (`+96891234567` → `+968****4567`)
- `email`: keeps first character and domain (`nasser@bite.com` → `n***@bite.com`)
- `ip`: keeps first two octets, replaces rest with `***` (`192.168.1.100` → `192.168.***`)

The processor is immutable — it calls `$record->with(context: $context)` to return a new `LogRecord` with the masked context, leaving the original unchanged.

**`config/logging.php` stackdriver channel** — New channel using `GoogleCloudLoggingFormatter` (built into Monolog 3.10.0). Outputs structured JSON with GCP severity field names (`severity` instead of `level_name`, `time` in RFC3339_EXTENDED format). Streams to `php://stderr` which Cloud Run captures into Cloud Logging. Production activates by setting `LOG_CHANNEL=stackdriver`.

**`config/sentry.php` trace sampling** — Changed `traces_sample_rate` from the conditional null-guard pattern to `(float) env('SENTRY_TRACES_SAMPLE_RATE', 0.10)`. Production now samples 10% of performance traces without requiring the env var to be set. Can be overridden by setting `SENTRY_TRACES_SAMPLE_RATE=0` to disable or any float to adjust.

### Task 2: LogSlowRequests middleware

**`app/Http/Middleware/LogSlowRequests.php`** — Middleware with `THRESHOLD_MS = 2000`. Measures wall-clock time around `$next($request)` and emits `Log::warning('Slow request', [...])` when duration meets or exceeds 2 seconds. Log context includes `method`, `path`, `duration_ms`, `ip`, and `status`. The `ip` field is automatically masked by PiiMaskingProcessor when the stackdriver channel is active.

**`bootstrap/app.php`** — Added `$middleware->append(\App\Http\Middleware\LogSlowRequests::class)` immediately after SecurityHeaders. Registered globally so all routes (POS, guest menu, webhooks, admin, health check) are covered.

## Tests

| File | Tests | Coverage |
|------|-------|----------|
| `tests/Unit/PiiMaskingProcessorTest.php` | 13 | All masking methods, null safety, immutability, multi-field |
| `tests/Feature/StructuredLoggingTest.php` | 6 | Channel config, formatter class, processor inclusion, stderr, Sentry default |
| `tests/Feature/LogSlowRequestsTest.php` | 5 | Threshold detection, fast-request no-log, field verification, passthrough, bootstrap |

Total: 24 new tests. Full suite: 251 tests passing (691 assertions).

## Commits

| Task | Hash | Description |
|------|------|-------------|
| Task 1 | `976cd98` | feat(07-02): PII masking processor, stackdriver log channel, and Sentry trace sampling |
| Task 2 | `0dd5bda` | feat(07-02): slow request logging middleware with global registration |

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check: PASSED

- `app/Logging/PiiMaskingProcessor.php` — FOUND
- `app/Http/Middleware/LogSlowRequests.php` — FOUND
- `config/logging.php` stackdriver channel — FOUND
- `config/sentry.php` 0.10 default — FOUND
- `bootstrap/app.php` LogSlowRequests — FOUND
- `tests/Unit/PiiMaskingProcessorTest.php` — FOUND
- `tests/Feature/StructuredLoggingTest.php` — FOUND
- `tests/Feature/LogSlowRequestsTest.php` — FOUND
- Commits `976cd98` and `0dd5bda` — FOUND in git log
