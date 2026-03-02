# Phase 2: Verify (The Gatekeeper)

**Goal**: ensure the solution is ROBUST and BUG-FREE.

## Protocol

### 1. The "Reality Check"
Before running automated tests, review your own code:
*   **Logic Check**: Did I miss edge cases? (Null values, negative numbers, permissions).
*   **"Obvious" Check**: Does this actually solve the user's problem?

### 2. Automated Verification
1.  **Run All Tests**: `php artisan test` / `npm test`.
2.  **Fix Regressions**: If *anything* fails, you are **NOT DONE**.
    *   **Loop**: Fix Bug -> Add Test Case -> Re-run All Tests.
    *   **Constraint**: Do not proceed to Phase 3 until everything is Green.

### 3. Visual & Manual Verification
*   **Self-Correction**: If the UI looks "janky" or "generic", fix it now.
*   **Console Errors**: Check for JS errors in the browser console concept.