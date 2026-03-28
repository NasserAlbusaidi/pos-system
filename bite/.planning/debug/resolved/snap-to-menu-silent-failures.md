---
status: resolved
trigger: "snap-to-menu-silent-failures"
created: 2026-03-26T00:00:00Z
updated: 2026-03-26T00:00:00Z
---

## Current Focus
<!-- OVERWRITE on each update - reflects NOW -->

hypothesis: CONFIRMED — All Throwable exceptions in extractMenu() are collapsed into a single 'failed' error code, which maps to the single generic translation key snap_error. No differentiation between rate limits, API key issues, timeouts, invalid images, parse failures, or network errors.
test: Trace all paths: MenuExtractionService::extract() throws various RuntimeExceptions, but OnboardingWizard catches all with \Throwable $e and sets extractionError = 'failed'. The blade template then does __('admin.snap_' . ($extractionError === 'no_items' ? 'no_items' : 'error')) — meaning any non-no_items error shows the exact same generic message.
expecting: n/a — root cause confirmed
next_action: Fix by adding typed exception classes, map them to specific error codes in the component, and add user-facing messages for each failure mode

## Symptoms
<!-- Written during gathering, then IMMUTABLE -->

expected: When Snap-to-Menu fails (API error, bad image, parsing failure, rate limit, etc.), the user should see a clear, specific error message explaining what went wrong and what to do about it.
actual: Sometimes fails silently or with a generic error message. User doesn't know if the issue is their photo quality, API limits, network problems, or something else.
errors: No specific error messages captured — that's the problem. Errors are likely swallowed or shown as generic "something went wrong."
reproduction: Upload various types of images (blurry, non-menu, very large, etc.) and trigger various failure modes
started: Ongoing since feature shipped 16 Mar 2026

## Eliminated
<!-- APPEND only - prevents re-investigating -->

## Evidence
<!-- APPEND only - facts discovered -->

- timestamp: 2026-03-26T00:00:00Z
  checked: OnboardingWizard.php extractMenu() method (lines 217-255)
  found: Single catch (\Throwable $e) block sets $this->extractionError = 'failed' for ALL exception types
  implication: Every failure mode — rate limit, timeout, invalid API key, parse error, network error — is indistinguishable from the user's perspective

- timestamp: 2026-03-26T00:00:00Z
  checked: MenuExtractionService.php extract() method
  found: Three distinct RuntimeException throws — (1) missing API key, (2) HTTP failure with status code appended to message, (3) unexpected response structure, (4) invalid JSON. All use the same base class RuntimeException with no typed differentiation. Also: Http::timeout(30) is set but connection timeout and the response structure for rate limiting (HTTP 429) vs auth error (HTTP 403/401) are not distinguished.
  implication: The service itself has enough information to distinguish failure modes but doesn't expose that via exception types — it lumps everything into RuntimeException

- timestamp: 2026-03-26T00:00:00Z
  checked: Blade template error banner (onboarding-wizard.blade.php lines 192-197)
  found: {{ __('admin.snap_' . ($extractionError === 'no_items' ? 'no_items' : 'error')) }} — only two possible messages shown: snap_no_items or snap_error
  implication: Even if the component set a more specific error code, the blade template would still show snap_error for anything that isn't 'no_items'

- timestamp: 2026-03-26T00:00:00Z
  checked: lang/en/admin.php snap_ keys
  found: Only two error-state keys exist: snap_error (generic) and snap_no_items. No keys for rate_limit, api_key, timeout, invalid_image, or parse_error.
  implication: Translation layer also needs new keys for each specific failure mode

- timestamp: 2026-03-26T00:00:00Z
  checked: MenuExtractionService line 43-44: $response->failed() handling
  found: throw new RuntimeException('Menu extraction failed: '.$response->status()); — status code is in the message string but not as a structured property
  implication: The 429/403/500 distinction is in the message text, not accessible as a typed value. Need to inspect $response->status() in the catch to distinguish failure types — OR fix this in the service itself

## Resolution
<!-- OVERWRITE as understanding evolves -->

root_cause: Three layered gaps: (1) MenuExtractionService threw generic RuntimeException for all failure modes, not typed exceptions; (2) OnboardingWizard caught all Throwable and set a single 'failed' code regardless of cause; (3) The blade template and lang files only had two error messages — snap_error and snap_no_items. Together, every possible failure mode showed the same generic message with no actionable guidance.

fix: |
  1. Created app/Exceptions/MenuExtractionException.php — extends RuntimeException with a readonly $reason property carrying typed error codes (api_key, rate_limit, timeout, invalid_image, api_error, parse_error)
  2. Updated MenuExtractionService to throw MenuExtractionException with the appropriate reason code for each failure mode. Also added connectTimeout(15) and increased timeout to 60s, and added try/catch around the HTTP call to convert ConnectionException to a 'timeout' reason.
  3. Updated OnboardingWizard::extractMenu() to catch MenuExtractionException first (sets $this->extractionError = $e->reason) and catch \Throwable as a generic fallback (sets $this->extractionError = 'api_error').
  4. Added 6 new translation keys to lang/en/admin.php and lang/ar/admin.php: snap_api_error, snap_api_key, snap_rate_limit, snap_timeout, snap_invalid_image, snap_parse_error.
  5. Updated onboarding-wizard.blade.php error banner to look up the correct key using a whitelist check with fallback to snap_error.
  6. Updated tests: MenuExtractionServiceTest (7 new/updated tests), OnboardingSnapMenuTest ('failed' → 'api_error').

verification: |
  201 tests pass including 12 MenuExtractionService unit tests covering all new failure modes (rate_limit, timeout, api_key via 401/403, invalid_image via 400, parse_error for bad JSON and unexpected structure, api_error for 500) and the Livewire integration test for the api_error code flowing through to the component state.

files_changed:
  - app/Exceptions/MenuExtractionException.php (new)
  - app/Services/MenuExtractionService.php
  - app/Livewire/OnboardingWizard.php
  - resources/views/livewire/onboarding-wizard.blade.php
  - lang/en/admin.php
  - lang/ar/admin.php
  - tests/Unit/Services/MenuExtractionServiceTest.php
  - tests/Feature/Livewire/OnboardingSnapMenuTest.php
