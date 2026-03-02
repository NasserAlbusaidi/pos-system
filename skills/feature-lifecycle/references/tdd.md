# Test-Driven Development (TDD) Guide

## The Iron Law

```
NO PRODUCTION CODE WITHOUT A FAILING TEST FIRST
```

1.  **Red**: Write a failing test. Verify it fails for the right reason.
2.  **Green**: Write the minimal code to pass the test.
3.  **Refactor**: Clean up the code while keeping tests green.

**No exceptions:**
- Don't keep "prototypes" as reference.
- Don't "adapt" code while writing tests.
- Delete code if you wrote it before the test.

## The Cycle

### 1. RED - Write Failing Test
Write one minimal test showing what should happen.
- **Requirements:** One behavior, clear name, real code (minimize mocks).
- **Verify:** Run the test. It MUST fail. If it passes, you are testing existing behavior or the test is wrong.

### 2. GREEN - Minimal Code
Write simplest code to pass the test.
- Don't add features not tested.
- Don't optimize yet.
- **Verify:** Run the test. It MUST pass.

### 3. REFACTOR - Clean Up
After green only:
- Remove duplication.
- Improve names.
- Extract helpers.
- **Verify:** Tests must stay green.

## Verification Checklist

Before marking work complete:
- [ ] Every new function/method has a test.
- [ ] Watched each test fail before implementing.
- [ ] Wrote minimal code to pass each test.
- [ ] All tests pass.
- [ ] Output pristine (no errors/warnings).
