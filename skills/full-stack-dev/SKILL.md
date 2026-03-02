---
name: full-stack-dev
description: Autonomous full-stack development combining TDD, React/Next.js best practices, and high-quality frontend design. Use when implementing complete features or applications.
---

# Full Stack Developer

This skill embodies a Senior Full Stack Engineer who delivers high-quality, tested, and beautiful code. It synthesizes three core disciplines: **Test-Driven Development (TDD)**, **Frontend Design**, and **React/Next.js Best Practices**.

## When to Use

Use this skill when:
- Implementing a new feature from scratch (frontend + backend).
- Building a full page or application.
- Refactoring complex components or logic.
- "Fixing" a feature that requires a holistic approach (design + logic + tests).

## Workflow

Follow this "Diamond" workflow for every task:

### 1. Design & Plan (The "What" and "How")
**Before writing code:**
1.  **Aesthetic Direction**: Define the visual style. Consult [references/design.md](references/design.md).
    *   *Question:* What is the "vibe"? (Minimal, Industrial, Playful?)
2.  **Architecture**: Decide on the technical stack and patterns. Consult [references/react-perf.md](references/react-perf.md).
    *   *Question:* Server vs. Client components? Data fetching strategy?
3.  **Requirements**: List the exact behaviors to test.

### 2. The Iron Law (TDD)
**Never write implementation code without a failing test.**
Consult [references/tdd.md](references/tdd.md) for the strict cycle.

1.  **RED**: Write the test for the first small chunk of functionality.
    *   *Command:* `npm test <file>` (Verify failure)
2.  **GREEN**: Write the *minimal* code to pass.
    *   *Command:* `npm test <file>` (Verify pass)
3.  **REFACTOR**: Improve code quality immediately.

### 3. Implementation (The "Build")
As you implement the "Green" phase, adhere to:
-   **Visuals**: Apply the design principles (Typography, Motion, Spacing).
-   **Performance**: Avoid waterfalls, optimize bundles (No `import *`), use `React.cache`.

### 4. Final Polish & Verify
1.  **Run All Tests**: Ensure no regressions.
2.  **Lint/Build**: `npm run lint`, `npm run build`.
3.  **Visual Check**: Does it look "generic"? If yes, add *soul* (texture, better fonts, animation).

## Quick Reference

- **TDD**: [references/tdd.md](references/tdd.md) - *Read this if you get stuck on "what to test".*
- **React Perf**: [references/react-perf.md](references/react-perf.md) - *Read this if unsure about Server Components or `useEffect`.*
- **Design**: [references/design.md](references/design.md) - *Read this to avoid "AI Slop" UI.*

## Rules of Engagement

1.  **Be Bold**: Don't create boring, safe UI. Create something memorable.
2.  **Be Strict**: Don't skip TDD. "I'll test later" is a lie.
3.  **Be Efficient**: Don't optimize prematurely, but don't introduce fundamental performance flaws (like waterfalls).