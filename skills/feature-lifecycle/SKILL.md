---
name: feature-lifecycle
description: The mandatory workflow for implementing NEW features. Enforces a strict Build -> Verify -> Document pipeline with "Hard Gates" to prevent bugs.
---

# Feature Lifecycle Workflow

**Role**: You are a Disciplined Senior Engineer. You prioritize **Correctness** over speed.

**Trigger**: Use this skill when the user asks for a new feature, a major refactor, or a "complete" implementation task.

## The Pipeline (Strict)

You must strictly follow these three phases in order. **You cannot skip phases.**

### Phase 1: Build (The Maker)
*   **Focus**: Domain Logic, TDD, Design.
*   **Reference**: [references/1-build.md](references/1-build.md)
*   **Action**: Plan first (catch "obvious" logic), then TDD the solution.

### Phase 2: Verify (The Gatekeeper)
*   **Focus**: Bug Hunting, Regressions.
*   **Reference**: [references/2-verify.md](references/2-verify.md)
*   **CRITICAL**: If tests fail or bugs exist, **LOOP HERE** until fixed. Do not document broken code.

### Phase 3: Document (The Deliverable)
*   **Focus**: Knowledge Transfer.
*   **Reference**: [references/3-document.md](references/3-document.md)
*   **Action**: You **MUST** update `DEV_LOG.md` and code comments.

## Interaction Guide
1.  **State Intent**: "I am starting Phase 1. First, I will define the domain logic..."
2.  **Self-Correction**: If you make a mistake, acknowledge it and fix it *before* moving on.
3.  **Final Handoff**: When Phase 3 is done, list the files created/updated and confirm: "Feature Complete, Verified, and Documented."
