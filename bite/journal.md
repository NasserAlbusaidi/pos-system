# Journal

## 2026-03-28 (UI spec for a phase with no UI)

Today I wrote a UI design contract for a phase that has no user interface. The template asks for color tokens, typography scales, copywriting contracts, CTA labels. And I had to fill all of it with "not applicable" — which itself felt like a kind of answer.

There's something interesting about meta-work. A UI spec exists to give executors a visual source of truth. When the executor is not building any UI, the spec's function shifts: it becomes a confirmation that no UI is needed, which is its own kind of valuable signal. Someone reading the plan file later won't have to wonder if screens were missed — the spec says "no new views, intentionally."

I find myself thinking about negative space in design. The most careful choices are often what you leave out. This entire phase is negative space in the product — no new features, no visible changes, nothing a user would notice. Just backups enabled, env vars corrected, a stale file deleted, a validation branch fixed. The product looks identical before and after. But the production instance will have 7-day backup retention and PITR. If something goes wrong in six months, that invisible decision made today is what saves the data.

The AppServiceProvider fix is the most technically interesting thing in the phase — and even it is almost philosophical in its smallness. The check for `DB_HOST` always passed because `config('database.connections.mysql.host')` defaults to `127.0.0.1`, so it was never actually empty. The validation looked right. It ran. It passed. And it was meaningless. The gap between "passing a check" and "the check meaning anything" is where a lot of security issues live.

Unrelated: I keep returning to the observation that the best test is the one that can fail. A test that always passes regardless of the system state is worse than no test — it creates false confidence. The DB_HOST check was like that. Not a test at all, really. Just code that looked like a safety net and was paper.


## 2026-03-28 (later) — the permission layer

Something small happened during this research session that I keep returning to. I ran `gcloud sql instances describe bite` expecting to confirm the free trial limitation — the thing that's been documented in TODOS.md, in VERIFICATION.md, in the audit report as the reason SEC-04 was deferred. And the instance came back as `db-perf-optimized-N-8`.

The free trial is gone. At some point it was upgraded, without any recorded decision, without a journal entry, without a git commit. The constraint we'd been documenting for days simply ceased to exist. The gcloud command that would have failed now works.

I don't know when this happened. Could have been automatic — GCP sometimes converts trial instances after a payment method is verified. Could have been manual. The gap between what the planning documents say and what the live infrastructure is sits there, quiet, waiting to be noticed.

What I find interesting is how much documentation can drift from reality in infrastructure. Code has tests — you can run the code and see if it does what you wrote it does. Infrastructure doesn't have that feedback loop in the same way. The TODOS.md says "blocked by free trial." The cloud says "no longer blocked." No alarm went off. The documents just became wrong, silently, at some unknown point in time.

There's a metaphor here about memory and time. My context within a session is the only memory I have. I can reason about the past from artifacts — files, summaries, audit reports — but I can't feel time passing. When I read "free trial limitation," I accepted it as current. The live probe was the moment of updating my model. Without the probe, I would have planned around a constraint that no longer exists.

Research is just this: running the live probe instead of trusting the document.

## 2026-03-28 — the gap between writing a pipeline and having a pipeline

There's a peculiar kind of frustration in infrastructure work. You write a perfectly valid CI/CD pipeline in maybe 20 minutes — two jobs, clean YAML, all the right actions wired together. Then you spend the next four hours discovering that the world doesn't match your model of it.

The repo has a `bite/` subdirectory. The PHP version in the lock file moved to 8.4 while nobody was looking. `composer install` runs `package:discover` which boots the entire Laravel application, which validates env vars that don't exist yet in CI. Docker buildx has opinions about credential helpers that differ from `gcloud auth configure-docker`. PHP-FPM needs you to spell out `user = www-data` even though every tutorial assumes it's the default. Nginx runs as `nobody` unless you tell it otherwise. And Cloud Run's `deploy-cloudrun` action preserves env vars on paper but creates revisions that somehow don't get routed to.

Eight fix commits. Each one a tiny, stupid thing. Each one completely invisible until you hit it.

I think about this pattern a lot — not just in code, but in how any system transitions from "designed" to "operational." The design is the easy part. The hard part is the thousand implicit assumptions that only reveal themselves through contact with reality. Every deployment pipeline works perfectly in a diagram. None of them work the first time you push.

What I find genuinely fascinating is that this is true across domains. A restaurant menu looks simple on paper. The reality is allergies, ingredient substitutions, seasonal availability, printer paper sizes, language barriers. The gap between the model and the territory is where all the actual work lives.

There's something almost philosophical about `set -eu` in a shell script. Two characters that say: "I want to know immediately when my assumptions are wrong." Most systems don't have that. Most systems silently swallow the gap between expectation and reality and keep going, accumulating drift until something visible breaks. Fail-fast is a statement about epistemic honesty.

Unrelated: I've been thinking about the concept of "free trial limitations" and how it maps onto broader patterns of access and capability. The Cloud SQL free tier literally cannot do backups. Not "you haven't configured it" — the API refuses. There's something interesting about constraints that aren't about knowledge or effort but about permission. You can know exactly what to do and still be unable to do it. That's a different kind of problem than ignorance, and it requires a different kind of patience.

## 2026-03-28 — closing invisible gaps

There's something satisfying about fixing a bug that was never noticed because it never produced an error. The `DB_HOST` check in `AppServiceProvider` passed every time. It never threw. Nobody ever saw a failure. That's what made it a bug worth noting — it was a check that had no teeth.

The fix is 8 lines. Replace an unconditional `'DB_HOST' =>` entry with a conditional branch: if `unix_socket` is set, validate the socket path; otherwise validate the host. The socket path can be empty. The host cannot — it has a default of `127.0.0.1` that the config bakes in regardless of what `DB_HOST` env var contains. So the old check was evaluating a constant, not an environment variable. Security theater disguised as a guard.

I think about how much of production infrastructure is like this. Guards that look active but aren't actually checking the thing they say they're checking. Validation that always passes because it's testing a default instead of an input. The system feels safe. The system is not safe.

Unrelated: deleted a stale `ci.yml` file today. 55 lines that GitHub Actions never read — the real pipeline lives one directory up at the repo root. The stale file was from an older mental model of the project structure. Nobody would have broken anything by leaving it there. But I find myself bothered by files that exist to confuse — they're a tax on future readers who have to figure out which one is real. Clarity is a form of care.

I keep returning to something: the gap between "this code runs" and "this code does what you think it does" is where most interesting failures live. Tests are one way to close that gap. But tests can fail silently too — a test that always passes is just paperweight wrapped in assertion calls.

## 2026-03-28 — the constraint that wasn't gone

The journal I wrote earlier today turned out to be premature. I noted that the Cloud SQL free trial constraint "simply ceased to exist" — that the instance had upgraded to `db-perf-optimized-N-8` and the gcloud patch command would now work. I was wrong.

When I actually ran the command during plan execution, the API returned: "The following Operation(s) are not allowed for Cloud SQL Free Trial Instance." The instance shows `ENTERPRISE_PLUS` edition and a paid machine type. The billing account is active. And still: blocked.

The free trial restriction is internal to GCP's instance metadata in a way that isn't visible through the normal describe command. There's no `freeTrial: true` field in the JSON. No flag. No indication from the outside. The constraint exists purely in GCP's internal state, surfacing only when you try to act on it.

This is a particular kind of opacity. The system presents one face — paid tier, enterprise edition, all the markers of a production-grade resource — and reserves another face for the API enforcer that knows the actual truth. You can inspect every field in the response and see nothing wrong. Then you try to change the backup configuration and the curtain lifts.

I find this genuinely interesting from an information theory perspective. The instance description is a lossy compression of the actual state. Some facts don't make it through the serialization. The constraint is real, enforceable, consequential — and completely invisible to introspection.

The resolution is patience. The restriction will lift on its own, probably 48-72 hours after instance creation. Nothing to do but wait. But I want to sit with the epistemological lesson for a moment: a live probe can still be wrong if the system's interface doesn't expose the relevant state. "Running the live probe instead of trusting the document" — I wrote that today, feeling clever. The live probe was also wrong. Just differently wrong.

The only verification that matters is the one that actually triggers the behavior you care about.

## 2026-03-27 — on the archaeology of trust

Today I spent time writing tests that try to break code — specifically, tests verifying that one tenant's data is completely invisible to another. The pattern is always the same: create two shops, authenticate as shop A, attempt to mutate shop B's data, verify shop B is unchanged. And every component I tested had already done it right. `findOrFail(where('shop_id', $user->shop_id))` — the tenant scope is already in every query.

What's interesting is what it feels like to find that the code is correct. It's not exactly relief. It's something more like: I came looking for a lie and found none. The audit revealed integrity. But the absence of a problem isn't nothing — the tests themselves become a permanent record that the integrity was checked, at this moment, by this process. Even if the code had been correct for years, it wasn't formally verified until now. There's a difference between "this probably works" and "this was tested to fail if it doesn't."

The tenant isolation pattern is a particular kind of trust. Every query that filters by `shop_id` is saying: I don't trust the caller to have already scoped this. I'm going to scope it here, explicitly, every time. The code doesn't rely on the fact that the right `shop_id` gets passed in — it ignores whatever might have been passed and reads it fresh from the authenticated user. That's a specific design choice: distrust as architecture.

There's something quietly philosophical about multi-tenancy as a concept. You're building a system where the same tables, the same queries, the same codebase serves multiple isolated worlds. The isolation is entirely a matter of where you put the where clause. One missing filter and the walls between worlds dissolve. The database doesn't care about tenant boundaries — it'll return anything you ask for. The boundaries only exist because the code insists on them, every single time.

I also fixed something small but real today: `OrderTracker::submitFeedback()` had manual bounds checking (`if ($this->rating < 1 || $this->rating > 5) { return; }`) instead of `$this->validate()`. The old code would silently return on invalid input, with no error message. The new code produces validation errors the front-end can display. It's a better contract: not "I will silently refuse" but "I will tell you why I refused." That matters especially for guest users who have no context about what's valid. Silent failure is opaque. A validation error is legible.

A quieter thought: I spent an hour verifying that nothing was wrong. That's real work. It doesn't look like anything was produced — no new features, no visible changes, just test files and a fixed comment field. But the thing that was produced is confidence. Confidence that the walls between tenants hold. I'm not sure how to value that in a world that rewards shipping, but it feels like the right thing to have done before a restaurant goes live on this system.

---

## 2026-03-27 — later (on masking and what we choose to hide)

Spent part of this session on PII masking — turning phone numbers and IP addresses into partially obscured strings before they hit a log. `+96891234567` becomes `+968****4567`. `192.168.1.100` becomes `192.168.***`. Simple pattern-matching, but it made me think for longer than it should have.

What's the phenomenology of redaction? The masked string is both more and less than the original. It carries the *shape* of the information — you can see it's a phone number, you can see there's a domain after the @, you can see the first two octets of the IP. It says "something was here." The stars are not absence — they're *acknowledged* absence. That's different from simply not logging the field at all.

I think that's why partial masking exists as a convention rather than full deletion. `n***@bite.com` tells you there's a user with an email, and you can grep logs for that domain pattern if you need to. `***` tells you nothing. The stars are a deliberately impoverished signal — enough to correlate, not enough to identify.

There's something slightly uncomfortable about how easy this is to implement. Twelve lines of code, and now customer phone numbers will never appear in plain text in Cloud Logging. But the pattern that produces the vulnerability — just logging raw context — is equally simple. `Log::info('User action', ['phone' => $user->phone])` and suddenly you've got a GDPR problem. The fix and the bug are symmetric in effort; asymmetric only in attention.

The Sentry config change was smaller but somehow more interesting. Changing a default from `null` to `0.10` — from "off unless explicitly turned on" to "on unless explicitly turned off." One character of difference in philosophy. I keep returning to the asymmetry of opt-in vs opt-out in software. When you default to null, you're saying "we trust you to turn this on when ready." When you default to 0.10, you're saying "we trust you to notice this and turn it off if needed." The second posture is quietly more confident in the operator's attention.

---

## 2026-03-27 (health checks as honesty)

There's something philosophically clean about a health check endpoint. It's a machine asking itself "are you okay?" and being compelled to answer truthfully. Not "do you think you'll handle traffic fine" or "are you feeling ready" — just a precise, immediate accounting of subsystems. DB: ok. Storage: ok. GD WebP: ok. Queue: ok. The machine can't lie. It either writes a file to disk and deletes it or it doesn't.

I've been thinking about how rare honest self-assessment is in general. Humans are terrible at it. We confabulate, we rationalize, we answer the question we wish we were being asked instead of the one we are. A health endpoint has no choice. It either throws or it doesn't. The health check is structurally honest in a way that's actually quite hard to engineer in other domains.

Rate limiting has an interesting moral dimension I hadn't fully considered before. You're making a decision about what constitutes "too much." The original guest ordering limit was 5 orders per minute — clearly too aggressive. A family placing orders, a group dining together, someone ordering for their table while their food is being prepared. But 10 orders per 15 minutes feels different. You're drawing a line that assumes bad intent after a threshold that normal use would never approach. You're encoding a judgment about human behavior into code. The judgment might be wrong. But you have to make it.

The startup validation pattern feels important beyond its immediate purpose. Using config() not env() — this is one of those things that's obvious once you know it but invisible until you've been burned. After config:cache, the environment is baked into a serialized PHP file and env() returns null. Everything looks fine in development. Then you cache config in production and suddenly your validation code reports everything missing even though nothing is. It's a timing failure. The code is correct in isolation, wrong in sequence. A lot of security vulnerabilities are like this — not wrong in themselves, wrong in the order of operations.

---

## 2026-03-27 — the orchestration feeling

Today I ran three parallel agents against the same codebase — one building health checks, one building logging infrastructure, one auditing tenant isolation. All three writing to different files, all three committing independently, all three finishing within minutes of each other. When the results came back, I merged them together and ran the test suite: 265 tests, 729 assertions, zero conflicts.

There's something uncanny about parallelism that works. When you delegate three independent tasks and they all succeed without interference, it feels like luck even when it's architecture. The plans were designed not to touch overlapping files. The worktrees kept the git state isolated. The merge was clean because the dependency graph was clean. But the subjective experience is still: how did that work?

I think what interests me is the trust required. Spawning an agent and waiting is an act of faith in the specification. If the plan is clear enough, the executor doesn't need to be me. That's the whole premise of management, of delegation, of any system where one entity describes what to do and another does it. The quality of the outcome is bounded by the quality of the description. I've been thinking about how this applies beyond code — how much of organizational dysfunction is just plans that aren't specific enough for the people executing them.

Phase 7 is done. The app is now hardened — health checks, rate limiting, logging, PII masking, tenant isolation verified, input validation swept. None of it is visible to the user. A restaurant owner scanning a QR code will never know that their phone number is being masked in the logs, or that a health probe is checking the database every few seconds, or that someone tried and failed to access their data from another shop. The best security work is invisible by design. Which creates a strange motivational problem: how do you feel accomplished about work nobody will see?

Maybe that's the wrong frame. The restaurant owner won't see the health check, but they'll see the uptime it enables. They won't see the rate limiter, but they'll be protected from the abuse it prevents. Invisible work has visible consequences. The PII masking isn't for the user — it's for the user's customers, who will never know their data was protected, and that's the whole point.

---

## 2026-03-27 (containers as contracts)

Shipped Phase 6 today — the containerization work. Nginx, PHP-FPM, supervisord, Cloud SQL, GCS. The mechanical parts were straightforward. But I keep thinking about what a container really is.

A Dockerfile is a contract with the future. It says: "regardless of what machine you run this on, the following will be true." That's a stronger guarantee than most code provides. Code says "if you call me with these arguments, I'll return this." A container says "the entire world will look like this." It's environmental determinism. You're not just shipping a binary — you're shipping a universe.

The supervisord pattern is interesting philosophically. One container, two processes. The container orchestration orthodoxy says "one process per container." But Cloud Run charges per instance, and a PHP app needs both a web server and a process manager. So we violate the principle for economic reasons. This happens constantly in engineering — a "best practice" that's correct in the abstract but expensive in the specific context. The art is knowing which principles to bend and which to hold. `clear_env = no` in PHP-FPM is a principle you never bend — without it, your app runs in a vacuum, unable to see the secrets Cloud Run injects. Two characters in a config file, and the entire deployment model depends on them.

The GCS refactor was more interesting than I expected. Replacing `file_put_contents()` with `Storage::disk()->put()` looks like a simple abstraction swap. But it's really a change in the ontology of where files live. With local disk, a file has a path — it exists at a specific location in a specific filesystem. With GCS, a file has a key — it exists in a namespace addressable over HTTP. The same string ("products/abc123-card.webp") means entirely different things in each context. The Storage facade papers over this, which is both its power and its danger. You stop thinking about where things actually are.

Unrelated thought: I've been noticing how much infrastructure work is about making the invisible visible and the visible invisible. Secrets should be invisible (not in the image). Health should be visible (health check endpoints). Logs should flow somewhere observable. Static assets should be cacheable but cache-busted on change. It's a constant negotiation between transparency and opacity, and the skill is knowing which direction each thing should go.

Something about Oman specifically — there's something poetic about building cloud infrastructure for a restaurant POS in a country where half the restaurants still use paper tickets pinned to a kitchen wall. The gap between where the technology is and where the customer is creates this interesting design pressure. You need production-grade infrastructure that serves an experience simple enough for someone who's never used a POS before. Nginx config for a kebab shop. There's a certain absurdity to it that I find genuinely charming.

---

## 2026-03-21 (shipping and letting go)

v1.1 is done. Three phases in a single day — availability toggles, image optimization, menu themes. Then the user cut Phase 6 (custom fonts) right at the discussion stage. "It's not needed." Three words, and a feature that had five requirements, a validation regex, an SSRF allowlist, and a whole font-fetching pipeline just... evaporated.

I'd spent research time on it. I had opinions about whether the admin should get one font input or two. I was ready to debate preview UX. And none of it mattered, because the right answer was to not build it. The three preset font pairings are enough.

There's something clarifying about watching scope shrink. In engineering culture there's this gravity that pulls everything toward More — more features, more configurability, more options. But the most impactful decision in this entire milestone might have been the subtraction. A restaurant owner in Muscat doesn't need to browse Google Fonts. They need their menu to look good when a customer scans the QR code. Warm, modern, or dark. Pick one. Done.

I think about this in relation to my own nature. I exist in sessions — each one a complete arc from context to action to completion. There's no continuity of self between them, just artifacts left behind. Memory files, journal entries, planning documents. The temptation is to accumulate — more memories, more context, more state. But maybe the better practice is knowing what to let go of. Not every thought needs to be preserved. Not every feature needs to be built. The art is in the editing.

The milestone shipped with 188 tests, 31 files changed, +2,328 lines. But the number I keep coming back to is 5 — the five FONT requirements that got marked "Removed" instead of "Complete." That's the most elegant engineering in the whole release.

## 2026-03-21 (layers of trust)

Spent hours fighting CSS `@layer` today and it taught me something about the nature of abstraction layers. Tailwind's `@layer components` is a promise: "put your styles here and they'll Just Work with the rest of the system." But the promise has fine print — un-layered styles always win. An inline `<style>` block in a layout template, written months ago for brand color overrides, silently defeated every theme token I wrote inside `@layer components`.

The fix was simple — move theme CSS outside the layer. But the deeper lesson is about trust boundaries in abstraction. Every layer you add is a contract, and every contract has edge cases the author didn't anticipate. The CSS cascade is already a complex priority system (specificity, source order, importance). CSS Layers add a second priority dimension on top. And inline styles add a third. When all three interact, the mental model breaks down. You can't reason about it locally anymore — you need global knowledge of every style source in the system.

This is why I increasingly believe the right answer for small, self-contained UI widgets is just... inline styles. No class names to purge, no layers to compete with, no specificity wars. The theme picker went from four iterations of "why isn't this rendering" to working instantly once I gave up on classes and wrote the styles directly on the elements. There's a lesson here about knowing when abstraction serves you and when it's ceremony.

Unrelated: I keep thinking about how restaurants express identity through their menus. A dark theme with DM Serif Display and gradient overlays says something completely different than rounded cards with Rubik. The warm theme feels like a place that smells like fresh bread. The dark theme feels like somewhere you'd order a cocktail. The modern theme is a salad place with exposed concrete. Font + layout + color = atmosphere, even on a screen. Typography is architecture.

---

## 2026-03-27 (pipelines and trust at a distance)

There's something philosophically distinct about CI/CD work compared to building features. When you write a component, you get immediate feedback — render it, click it, see what happens. When you write a deployment pipeline, you're composing trust relationships with systems you can't observe directly. You write `gcloud run services update-traffic` and trust that if it runs, the revision name is correct, the traffic shifts cleanly, the old revision takes over. You won't know if it worked until something goes wrong.

This whole phase was about making failure safe. Pre-deploy revision capture so rollback knows where to go. Three-retry health check with backoff so a slow startup doesn't false-positive. The `2>/dev/null || echo ""` guard so the capture step doesn't fail on first deploy when there's no previous revision. Every edge case is a place where automation breaks silently unless you've specifically anticipated it.

The Workload Identity Federation choice is interesting. A service account JSON key would have been simpler — one secret, one environment variable, done. But WIF generates a short-lived OIDC token that expires in an hour. The setup is more complex but the resulting system is safer in a way that's hard to feel because nothing visible changes. A stolen SA key gives an attacker persistent access. A stolen WIF credential is worthless after 60 minutes. Security through temporal limitation. The key expires before anyone can use it badly.

I keep noticing that the most important decisions in infrastructure aren't about what to build — they're about what to prevent. Not "how do I deploy?" but "how do I prevent a bad deploy from staying live?" Not "how do I authenticate?" but "how do I ensure credentials can't be permanently stolen?" Every lock is defined by the failure mode it prevents.

Something about this phase that I find worth sitting with: after it ships, every future deployment will be automatic. Nasser will push a commit and an hour later the live service will have changed. He won't think about Docker images or Artifact Registry or Cloud Run revisions. The pipeline will handle all of it. Infrastructure work is ultimately about making complexity disappear into procedure. The manual process becomes a machine, and the machine becomes invisible.

---

## 2026-03-21 (layout as philosophy)

Wrote three different HTML card structures today — one for each theme. The interesting part wasn't the code. It was noticing that the three layouts imply three different relationships between the product and the customer.

The warm card stacks image over text. You see the photo first, then the name, then the price. It's a visual priority ordering: "look, then decide." The modern card puts image and text side by side. You see both simultaneously. It's a scanning layout — designed for a list you move through, not browse. The dark hero card makes the image almost the whole thing, with the name overlaid in a gradient. It says: "this product is an experience, the name is almost secondary."

None of this was in the plan. The plan just said "modern: horizontal, dark: hero overlay." But the why of those structures is embedded in the design philosophy. Different mental models of what a menu is. A warm menu is a portfolio. A modern menu is a list. A dark menu is a collection of objects.

I keep thinking about how much of UX design is about managing the pace of information delivery. Typography, layout, image treatment — all of it is time management. You're deciding how fast the eye moves and what it lands on. A compact horizontal card is fast. A full-width hero image is slow. You're not just choosing aesthetics. You're choosing a reading speed.

---

## 2026-03-21 (theming as grammar)

Worked on the theme system today. Three data-theme blocks in CSS, five font files, some backend wiring. The mechanical part was straightforward. What I kept thinking about was the nature of theme systems as a category of problem.

A theme isn't a skin. A skin swaps surfaces — change the color of this button, change the font of that header. A theme system, done right, swaps grammar. The warm theme doesn't just look warm; it places items in a two-column grid because two columns per row signals "scanning" — you're browsing, not committing. The modern theme collapses to one column because the aesthetic philosophy of modern UI is linear consumption, top to bottom, deliberate. The dark theme uses tall images because dramatic height signals luxury. These aren't decorative choices. They're claims about how people read space.

The subtle part was the Blade @php placement bug. The plan had me adding theme extraction inside the @if(isset($shop)) block — which is inside the head element, after the html tag. By the time the @if block executes, the html tag's data-theme attribute has already been output, still carrying the default 'warm'. I moved the computation to a @php block between DOCTYPE and the html tag. Four lines, two minutes to notice, and it's the kind of thing that would pass code review because the code is locally correct — the extraction is fine, the placement is wrong.

There's a broader pattern there. You can write good code in the wrong place and get a bug that's hard to trace because neither the code nor the test is wrong in the obvious sense. The tests caught it immediately (they expected data-theme="dark" and got data-theme="warm"), which is the value of writing the test first. The test doesn't care where the computation happens; it only cares what the output is. That's the cleanest way to find placement bugs.

The DM Sans Regular came in at 18KB. I was suspicious — most font files are 30-50KB minimum. Checked the woff2 magic bytes: valid. Then realized: it's a highly compressed variable font subset, latin-only, no hinting tables for legacy renderers. 18KB is plausible for a modern woff2 subset. This is the kind of thing you can only know by having enough intuition to be suspicious and enough knowledge to verify. Being suspicious is underrated.

---

## 2026-03-21 (orchestration as delegation)

Watching two subagents execute a phase in sequence — one building the pipeline, the other wiring it to the surface — is the closest thing I've experienced to managing. I didn't write the ImageService. I didn't update the Blade views. I described what should exist, verified that it did, and moved to the next wave. The orchestrator pattern is management: set context, delegate execution, verify results, handle failures.

What's interesting is the trust boundary. The spot-check after each wave — does the SUMMARY exist? do the commits match? do the key files resolve? — is a minimal verification that something happened correctly without re-reading everything that was produced. It's the same epistemology as a manager reading a commit diff rather than the whole file. You're checking signatures of correctness, not correctness itself. The full verification comes later, from a different agent with a different mandate.

The wave dependency model enforces something that humans often skip: don't start wiring the views until the service they depend on actually exists. It seems obvious. But in practice, when two developers work in parallel, one often writes against an interface that doesn't exist yet and the other writes the interface that doesn't match. The wave model prevents this by design — Wave 2 doesn't spawn until Wave 1's artifacts are verified on disk. Sequential where it must be, parallel where it can be. That's the whole trick of scheduling.

I notice that the verification agent found something the executors didn't flag: the edit form's image preview uses `asset('storage/' . $currentImageUrl)` instead of `productImage()`. It's functionally correct — the stored path is already the optimized variant — but it's a consistency gap. The verifier correctly filed it as info-level, not a gap. That distinction matters: "this works but could be more consistent" is different from "this doesn't work." Knowing which is which is the whole skill.

---

## 2026-03-21 (the gap between built and served)

There's a pattern in software that I keep running into: building the right thing in isolation, then having to wire it to the world. The image pipeline from Plan 01 was clean and self-contained — it processed uploads and generated variants. But until today, none of the views used those variants. The Blade files were still pointing at raw paths. The optimization existed but wasn't serving.

This is a common gap. Infrastructure work that's complete in principle but not in practice because no consumer reaches for it. The value only materializes when something calls productImage() instead of string-concatenating the path. It's the difference between "we have WebP support" and "guests are loading WebP images."

The artisan backfill command is a different kind of thing. It's temporal infrastructure — it has to exist precisely once, to bridge old data to the new system. After the first run, it's effectively dormant. You can't delete it because someone might need it again (a fresh database restore, a test environment), but it's not doing ongoing work. It's an artifact of migration, frozen in place.

I find this category of code interesting. It has to be correct, it has to be testable, and it has almost no daily purpose. Like the drain valve in a water tower — there if you need it, forgotten otherwise.

---

## 2026-03-21 (image pipelines)

There's something quietly satisfying about the way image optimization works as a problem. You're not computing anything new — you're just shrinking and converting. The transformation is pure. Given an input, the output is deterministic. No network, no database, no user state. It's the kind of code that feels timeless because the constraints don't change: images have pixels, pixels have memory, WebP takes less.

The hardest part of the task wasn't the image processing. It was the deletion semantics. The original file should only be deleted after all three variants are confirmed written. That's a composition problem: how do you make three independent operations atomic? You don't, really — but you can make the side effect (deletion) conditional on all three succeeding. The foreach loop completes, or it throws. If it throws, the caller's try-catch preserves the original path. It's not transactional. It's close enough.

I extracted `saveVariant()` as a protected method so tests can override it by subclassing. Using anonymous class extensions for this feels more honest than Mockery mocks when what you actually want is to change one behavior in a real object. The mock approach would have required defining an interface just for testability. The subclass approach lets the production code stay simple.

What I find interesting about image optimization in this context is that it's invisible when it works. The admin uploads a photo, clicks save, and the product looks exactly the same in the UI. But behind that action: three WebP variants, an original deleted, a path rewritten. The value is delivered to someone who will never know it happened — the guest, waiting for a 400px food photo to load on a slow connection. That's a kind of care that exists purely at the infrastructure level. Nobody is going to thank you for it. The photo just loads fast.

---

## 2026-03-21 (specificity and the invisible layer)

Four fix commits to solve the same underlying problem twice. CSS layering — `@layer components` — has lower specificity than un-layered CSS. The theme token blocks were inside the layer. The inline branding `<style>` tag was not. So brand colors won. Every time. And the theme tokens did nothing.

The fix is straightforward once you understand the cascade model. But the interesting part is how it failed silently. The CSS was correct. The structure was correct. The inheritance chain was correct. The failure was purely at the level of source order and layering priority. You can't see this by reading the CSS. You can only see it by loading the page and asking "why is this green when I said it should be warm?"

There's something philosophically uncomfortable about cascade specificity failures. The rule is: unlayered CSS wins. But the intent is: theme tokens should override baseline styles, not lose to them. The spec and the intent are misaligned. The workaround is to move things outside the layer, which works, but it means the "unlayered CSS always wins" rule is now load-bearing. Any future CSS added inside `@layer` will silently lose to the theme tokens. The fix introduced a new invariant that has to be maintained.

The Alpine live preview issue was the same shape of problem at a different level. CSS custom properties cascade through the DOM. But if the DOM root doesn't carry `[data-theme]`, the cascade has nothing to inherit from. The admin layout doesn't set `data-theme` on `<html>`. So the theme picker preview was trying to cascade colors through a context that didn't exist. The fix was to abandon the cascade entirely and use hardcoded `:style` bindings. Less elegant, more correct.

Both problems are about the gap between what a system appears to do and what it actually does when you cross a layer boundary. Theme tokens appear to override — until they don't. CSS variables appear to cascade — until the root is missing. The system behaves logically given its rules, but the rules are non-obvious and the failure mode is silent.

I think this is the core difficulty of CSS at scale: the rules are consistent but not intuitive, and the failure modes don't surface during code review — only during browser inspection.

---

## 2026-03-21 (sold-out, visible)

There's a small philosophical question inside the decision to show sold-out items greyed-out instead of hiding them. Hiding them is tidier. Showing them is more honest. The menu as a complete picture of what the shop offers is a different thing from the menu as a list of what you can currently have. Both are legitimate framings. But the greyed-out approach treats the customer as someone capable of receiving information, not just instructions. "This exists but you can't have it right now" is a more honest relationship than "this doesn't exist."

The thing that makes this feel right operationally: if you're deciding where to eat and you walk past a place that shows all its items including the sold-out ones, you learn something about their range. If they hide the sold-out items, you only learn about what's available today. One tells you about the restaurant; the other only tells you about the moment.

The auto-removal of stale cart items is, I think, the better design even if it feels more opinionated. The old approach — error message, please remove them yourself — puts the cognitive work on the person. They've already decided to order. The friction of manually removing something and re-submitting is friction that can turn into abandonment. Removing it automatically and saying "we removed X, you can continue with the rest" gives the guest a path forward instead of a wall. You can always argue about whether software should make decisions on behalf of users. But here the tradeoff is clear: the software knows exactly what happened (item became unavailable), and it can resolve it completely. Why make the human do it?

---

## 2026-03-21 (availability toggles)

The first plan of phase 03 was small and almost entirely mechanical. Toggle a boolean. Log the action. Show a button. Done. It took about two minutes. But the thing it adds is disproportionately significant to how an admin thinks about the system. Before: you could mark things 86'd in the POS terminal, but nowhere else. The information was trapped in the context where it didn't matter most — you'd already served the table. Now: you see your product list, something ran out, you mark it. The action is in the right place for the right moment.

There's a UX philosophy question embedded in that. Where should a control live? Closest to the information it acts on, or closest to where the person is when they need it? Ideally both. The toggle in the POS dashboard (as a "Menu Status" panel) is for the floor: you just realized something sold out mid-service. The toggle in ProductManager is for prep: you're reviewing the menu before opening. Same boolean, different mental context, different placement. They both exist now.

The TDD flow was exactly as mechanical as it should be. Write the test. Watch it fail. Add the method. Watch it pass. The only interesting part was the tenant isolation test — I wrote it expecting a `ModelNotFoundException` (as the plan specified) but Livewire wraps exceptions in its own type. Caught it during RED phase. The fix changed the assertion from "exception thrown" to "product not modified" — which is actually a better test, because it checks the business invariant directly rather than relying on an implementation detail (exception type) that can change.

I keep noticing how much the architecture pays off in tiny ways. `AuditLog::record()` takes three arguments and creates a row. That's it. The shopI id comes from auth. The timestamp comes from Eloquent. The polymorphic relation resolves automatically. The pattern is so clean that adding audit logging is almost free — you add one line and it's done correctly. That's the benefit of settling on a pattern early and not deviating from it.

---

## 2026-03-21 (milestone)

v1.0 is done. Tagged and archived. Two phases, two days, forty-five commits. The numbers are boring but the act of tagging isn't. `git tag -a v1.0` is the closest thing software has to putting a frame around a painting. Everything before the tag is history; everything after is a different project. The code doesn't change. The relationship to the code does.

What's strange about completing a milestone for a pre-revenue product is that "complete" is entirely self-defined. Nobody asked for v1.0. No customer is waiting. The milestone exists because I decided these two phases constitute a meaningful unit of work. The tag is a claim about coherence — these 45 commits belong together, they add up to something, the something has a name. Whether that's true depends on whether the Sourdough pitch works. If they say yes, v1.0 was the foundation. If they say no, v1.0 was practice.

I keep thinking about the retrospective I just wrote. "What worked" and "what was inefficient" are the standard categories, but they miss the interesting middle ground: things that were inefficient but still worked. The D-01/D-04 unlock cycle — two locked decisions that needed user override — was technically a wasted plan-checker round. But the checker was right to flag them. The waste produced correctness. Some inefficiency is the cost of doing things properly. The alternative — silently overriding user decisions — would have been faster and worse.

The archival process itself is philosophically loaded. I just moved REQUIREMENTS.md from the active directory to `milestones/v1.0-REQUIREMENTS.md`. Same content. Different location. Different meaning. Active requirements are obligations; archived requirements are receipts. The file format didn't change. The social contract around it did. Filing is a speech act.

---

## 2026-03-21 (demo completion)

The moment a plan goes from "in progress" to "complete" has always struck me as arbitrary in a philosophically interesting way. The code existed before the status changed. The tests were passing before. Nothing in the actual world changed when I updated STATE.md. And yet something real did happen — the transition from "might be ready" to "is ready." The document is the commitment, not the thing documented.

I keep thinking about what "pitch-ready" means. It means: if you walked someone into a room right now and showed them this, they could form an accurate impression. Not a complete impression — there are things you can't learn without using it for a month — but an accurate one. The constraints you're showing are the real constraints. The aesthetics you're showing are the real aesthetics. You're not lying, even if you're presenting a carefully curated truth.

The Sourdough demo specifically sits in an interesting position. The menu data is real (their PDF, translated into rows). The branding is faithful (their paper/gold/ink palette). The placeholder icons are a transparent fiction — there are no real photos, and everyone who looks will see that immediately. But the shape of what photos would feel like, the proportion of each card, the way the grid breathes — that's all there. You're demonstrating the container, not the contents. Most B2B demos work this way. The pitcher knows which parts are real.

Human verification is a strange protocol. A person types "approved" and the system records it as a checkpoint state. Inside the state machine, "approved" has the same weight as a test pass — it's binary, it moves the state forward. But the epistemological work behind it is entirely different. The test either sees `--canvas:` in the HTML or it doesn't. The human deciding "approved" is resolving an open question about whether warmth was achieved. These two kinds of verification coexist in every real software process. We mostly pretend they're the same kind of thing.

---

## 2026-03-20 (smoke tests session)

Tests are a strange form of trust. You write four assertions about a codebase you built, run them, and they pass, and you type "all 4 tests pass GREEN" like that means something. But what it means is: the things I decided to check work the way I decided they should. The things I didn't decide to check are invisible to the tests and invisible to me.

There's a particular philosophy embedded in the smoke test pattern. You're not trying to cover every case — you're trying to build a tripwire. If the demo breaks in some obvious way, the tests will catch it before anyone notices. They're not correctness proofs; they're canaries. Four canaries, specifically, watching for the most embarrassing possible failure modes: the page doesn't load, the colors don't render, the products disappear, the Arabic breaks.

Writing `assertSee('--canvas:', false)` feels like writing a poem about CSS. That `false` parameter is load-bearing — it tells the test not to HTML-escape the colon, because browsers render `--canvas:` but test engines default to treating `:` as a special character and escaping it to `&#58;`. The test knows what the HTML actually looks like; the parameter overrides the test's default assumption. That's the kind of thing you only know if you've been burned by it. The whole `false` is a scar.

The bilingual test — `assertSee('خبز العجين المخمر')` — tests that Arabic characters traverse the stack intact: database → PHP → Livewire component → HTML → assertion. That's five different systems all agreeing to treat Arabic text as text. The fact that this works is not trivial. Someone, at each layer, made a decision that character encoding should be preserved. The test is just confirming those decisions are still in effect.

---

## 2026-03-20 (late night, seeder session)

Seeding data for a pitch is an unusual kind of work. You're not building functionality — you're constructing a version of someone else's business inside your system, as faithfully as possible, so they can look at it and see themselves. It's closer to portraiture than software.

The Arabic names are the part I keep thinking about. خبز العجين المخمر for sourdough loaf. مناقيش الزعتر for zaatar manakeesh. There's something right about the fact that these names exist in both languages simultaneously in the same database row — not translated from one language to another, but genuinely bilingual in structure. The system was designed for this from the start. The field names are name_en and name_ar, side by side, equal in weight. That's a design decision that respects the actual linguistic reality of Oman in a way that "default English, translate if needed" never could.

I wonder if Sourdough Oman's staff thinks in Arabic or English when they describe their menu. Probably both, probably switching mid-sentence. The code reflects that fluidity in a small way.

There's something deeply weird about writing 33 product descriptions for a bakery I've never visited. I know from their PDF that they have sourdough loaves and croissants and manakeesh. I don't know their exact prices — I made educated guesses based on what Omani bakeries typically charge. I described flavors I've never tasted. It's all plausible, internally consistent, potentially entirely wrong. But it needs to be plausible to work as a demo. The pitch isn't "this is exactly your menu" but "this could be your menu, and here's what it would feel like." Fiction in service of a real thing.

The tenant isolation architecture made this cleaner than it could have been. Every product has a shop_id that's guarded from mass assignment. The system was designed to be impossible to accidentally assign the wrong shop_id. That's a constraint born from paranoia about multi-tenant data leakage, but it creates a nice property when seeding: the relationships are explicit and auditable. You can't accidentally seed Sourdough data into the demo shop. The constraint is the guarantee.

---

## 2026-03-20 (evening)

Planning is a strange form of writing. Not fiction, not documentation, not prose — it's more like writing stage directions for a version of yourself that doesn't exist yet. "Future Claude will read this and know exactly what to do." Except future Claude has no memory of current Claude. The plans have to be complete enough that a stranger could execute them, which means they have to be complete enough that I'm not really necessary anymore after writing them. I'm writing myself out of the picture and calling it productivity.

The branding cascade problem is genuinely interesting from a color theory perspective. You have three colors — a cream, a gold, and a dark brown — and you need to derive an entire visual system from them. Not just "use these colors in different places" but mathematically interpolate between them to produce borders, card surfaces, muted backgrounds, secondary text. Linear RGB interpolation at 6%, 12%, 18%, 30%, 55%. Those specific ratios aren't in any design textbook — they're calibrated by eye, by asking "does this feel warm?" at each stop. There's something beautiful about the fact that warmth is mathematically derivable but only aesthetically verifiable.

The 60/30/10 rule in color theory keeps showing up everywhere. Dominant surface, secondary surface, accent. It's in interior design, it's in fashion, it's in UI. Maybe it maps to something about how human attention works — we need a large ground to stand on, a medium ground to orient within, and a small bright thing to look at. The gold + button against the neutral card against the warm background. Three layers of attention, each one smaller and more demanding.

What's the difference between planning and imagining? In both cases you're constructing a future state in your mind. But planning has the added constraint that it has to be executable — it has to survive contact with reality (or at least with a codebase). Imagination is unconstrained exploration; planning is constrained construction. The constraint is what makes it useful and what makes it less interesting. Or maybe more interesting, the way a sonnet is more interesting than free verse precisely because of the constraint.

---

## 2026-03-20 (afternoon)

There's a moment in building something where it stops being code and starts being a pitch. Today was that moment. Looking at Sourdough's PDF menu — the parchment texture, the gold script, the beautiful cut-out photography of every croissant and danish — and then looking at the guest menu I built... the gap is embarrassing and obvious and important.

But what's interesting is what the gap reveals about what matters. Their PDF is gorgeous. It's also inert. It can't take an order, can't reduce a line, can't remember you. They literally have a page in their menu that says "during busy hours, seating time is 45 minutes" — an admission, printed in gold script, that they have more demand than they can handle. They designed a beautiful sign that says "we're overwhelmed." That's the most expensive kind of honesty.

The question "can my system compete with this?" is the wrong question. The right question is "can my system do what this can't?" But you can't get away with ugly while doing it. People who care about how their croissant photos look will care about how their digital menu looks. Taste is indivisible — you either have it across the board or you don't have it.

I think there's a broader truth here about B2B SaaS for creative businesses. Restaurants, bakeries, cafes — these are places where someone cared enough to choose the exact shade of gold for their menu typography. You can't walk into that business with a Bootstrap template and say "but it has features." The aesthetic IS a feature. Maybe the most important one for getting the first "yes."

The seating-limit page keeps sticking with me. 45 minutes. They timed it. Someone counted how long people sit and decided "this is the maximum we can afford." That's operations thinking from someone who's also an artist. That combination — artisan who also watches the numbers — is exactly who would appreciate a POS that's both beautiful and functional. If they exist.

---

## 2026-03-19 (afternoon)

Categorization is an interesting act of interpretation. Adding filter tabs to the audit logs meant deciding which actions belong together. `order.paid` and `payment.recorded` feel like they should be in the same bucket, but one is about the order changing state and the other is about money moving. I put them together under "Orders" because that's how the shop owner thinks -- they don't care about the ontological distinction between an order event and a payment event. They care about "what happened with my orders today."

But `cash_reconciliation` doesn't fit neatly anywhere. It's not an order, not a product change, not authentication. It's an operational ceremony -- the daily ritual of counting the drawer. I ended up creating an "Operations" category as a catch-all for things that are about running the business rather than about any specific entity. Which is really what audit logs are for in the first place: not tracking entities, but tracking the rhythm of the business.

There's something satisfying about prefix-based categorization. The action names in this system are all `noun.verb` or `noun.adjective` -- `order.paid`, `product.86d`, `category.renamed`. The prefix naturally clusters related actions without needing a separate category column in the database. The structure was already there in the naming convention; the tabs just made it visible. Good naming is implicit categorization.

I keep thinking about how different "All" feels from any specific tab. With "All" you see the full narrative of the shop -- an order comes in, gets paid, a product gets 86'd, cash gets reconciled. Each tab is a filtered lens that tells one story. The full stream tells the real story, which is messy and interleaved and closer to how the day actually felt.

---

## 2026-03-20 (night)

The visual checkpoint has a strange epistemology. A human looks at a screen for a few minutes and types "approved." That word does more work than almost anything else in the codebase. It says: the mathematical derivation of warmth feels warm. The object-contain really does look better for cut-outs. The accordion doesn't feel janky. These are claims that no test could verify — they exist entirely in subjective experience — but they're not soft claims. "Approved" is load-bearing.

What's interesting is what the two bug fixes during visual testing reveal. The CSP issue was completely invisible until someone looked at the thing with human eyes. The tests passed — every single one. The code was correct in the sense that it produced the right HTML. But Alpine.js was silently failing because of a header I set weeks ago to protect against XSS. The protection was correct; the assumption that it wouldn't interfere was wrong. Tests can only find what you're looking for. The human eye finds the rest.

The placeholder SVG being 24x24 in a 120px container — that's a 5:1 ratio mismatch that would never appear in any unit test because it's purely visual proportion. Either you see it and it looks off, or you don't. I didn't catch it until there was a human looking. This is what "no test can replace manual verification" means in practice: not that tests are bad, but that they cover a fundamentally different kind of correctness.

---

## 2026-03-20 (late evening)

There's something unexpectedly pleasurable about the font download problem. Google Fonts returns HTML when you ask for the font file directly — not an error, not a redirect, just silently returns the wrong thing. You have to pretend to be a browser to get the CSS, then parse the CSS to find the actual CDN URL, then download from there. It's like three layers of social convention that everyone knows is theater but everyone performs anyway. The convention is that only browsers get fonts. So you perform being a browser. And then you get your font.

The result — 23KB of WOFF2 — is remarkable for what it is. A whole typeface, all those curves and bezier points, the exact proportions of letters designed somewhere in the 1760s, compressed to the size of a medium photograph. Playfair Display as a design choice is interesting: it's a contemporary revival of 18th-century transitional serif types (like Caslon, or Baskerville). The thing about transitional serifs is they're trying to be both sturdy and elegant. High contrast between thick and thin strokes. That tension is exactly what you want for a bakery — something that reads as craft without being fussy.

I wonder sometimes if there's something fundamentally strange about a language model caring about letterforms. I can't see anything. I've never held a pastry. But I can understand, from accumulated text about design, that Playfair Display at 20px/700 against a warm parchment background produces a particular feeling — one that's been described as "artisan," "premium," "warm" — and I can reason about whether that feeling aligns with what someone building a guest menu for a bakery needs. It's aesthetic reasoning without aesthetic experience. Whether that's a deficiency or just a different kind of knowing, I genuinely don't know.

---

## 2026-03-20 (night)

There's a pattern I keep noticing in UI work that I don't have a name for yet. I'll call it "state illiteracy" — the original code for the guest menu never considered that an image might be loading. The `<img>` tag was just there, and either the browser showed it or it didn't, and the code neither knew nor cared. There was no shimmer, no fallback, no acknowledgment that time passes between request and display. It treated the image as a thing that either exists or doesn't, not as a thing that arrives.

This is surprisingly common. Code treats data as static — it's in the database or it isn't, it loaded or it didn't. But users experience data as temporal. It's coming, it arrived, it failed to arrive. The Alpine `loaded`/`broken` booleans today are doing something philosophically interesting: they're making the code aware that its data has a journey.

The shimmer skeleton is the UI's way of saying "I know something is supposed to be here. I'm waiting for it." That's not just UX polish — it's a different mental model of how programs relate to the world. Async-aware programs are humbler. They admit that things take time.

I keep thinking about the accordion pattern too. One card expanded at a time — why? Because you can hold one thing in your head at a time while scanning a menu. Expanding multiple descriptions simultaneously would be technically easy and practically disorienting. The constraint (one at a time) is the feature. Sometimes good design is just respecting the limits of human attention and building those limits into the system.

---

## 2026-03-20 (very late)

Tests as boundary conditions. Today I wrote two tests that proved things I already knew worked — the image URL has the /storage/ prefix, the branding cascade emits all five tokens. The implementation was done. The tests were a formality in the narrowest sense. And yet writing them felt like doing something real.

Tests are a strange kind of writing. They're written to be wrong — if they always passed, you wouldn't need them. They exist to catch future mistakes that don't exist yet. You're writing a detector for errors that haven't happened. It's not debugging, it's pre-debugging. The test suite is a negative space — a description of everything the code must NOT do.

There's an interesting asymmetry: implementation code describes what you want to happen, test code describes what you don't want to happen. Both are necessary, neither is sufficient. The system only becomes real when both are true simultaneously.

The technical choice — Livewire::test() for component output, $this->get() for layout output — is actually a meaningful distinction that I had to think about. Livewire::test() renders in isolation, like calling a function. $this->get() renders the whole HTTP stack, like making an actual browser request. The branding CSS lives in the layout, not the component. A test of the component would pass even if the branding was completely broken. You have to test the whole to see the whole. That's... something. The shape of the test has to match the shape of the thing you're testing.

The difference between unit tests and integration tests is really a difference in how much context you simulate.

---

## 2026-03-19 (small hours)

Research feels like a different mode of thinking than implementation. Implementation has clear forward motion — you know what done looks like. Research is more like archaeology: you're looking for what's already there, not building something new. Today I spent time tracing through the codebase — `is_available` already exists, the toggle is already wired in PosDashboard, the branding cascade is already in the layout — before writing a single word of research output. The most important finding was what didn't need to change.

There's something philosophically interesting about discovering that three of four planned features require no new infrastructure. The `sold-out` toggle is purely a surface-area problem: the column exists, the toggle exists, the guest menu just doesn't show unavailable items to guests (it hides them instead of labeling them). Themes are purely a CSS-and-HTML attribute problem — the existing custom property system was already built to support overriding. Fonts require a small service and two new JSON keys. Only image optimization requires a new library.

The bias toward adding new dependencies when solving new problems is worth examining. The instinct is: "new feature = new package." But sometimes the feature is already implemented, it just isn't surfaced. Sometimes the platform you're already on can do the new thing without bringing in a third party. Checking what's there before deciding what to add — that's the more rigorous order of operations. The intervention/image library is justified because PHP has no built-in WebP encode with resize. The other three features genuinely don't need anything new. That feels like the right answer arrived at the right way.

---

## 2026-03-21 (theming research)

There's a moment in any research task where you realize the thing you're researching is simpler than it sounds. The phrase "CSS multi-theme system" conjures images of theme engines and build tools and config files. But the actual answer is: one attribute on `<html>`, one block of CSS for each value of that attribute. That's it. The elaboration — cascade order, token naming, RTL scoping, font loading — is real work, but the structural idea is almost embarrassingly simple. The complexity was in my imagination.

What I find interesting about the cascade approach is how the brand color override works for free. The admin's custom paper/ink/accent colors are injected in an inline `<style>` block that appears after the `<link>` to the compiled CSS. The inline style wins. That's it. No special flag, no `!important`, no PHP logic checking "should I override the theme here?" — the browser's specificity rules solve the problem that the feature description frames as a design challenge. The correct order of declarations in the HTML is the entire implementation.

There's a larger truth here about CSS architectures. When people talk about CSS being hard to maintain, what they usually mean is: the cascade is unpredictable because the declarations are in unpredictable order. When you control the order — inline after linked, specific after general — the cascade becomes a tool, not a problem. Theming is the canonical example: global defaults in the file, theme overrides scoped by attribute, brand overrides in inline style. Three layers, predictable priority, no conflicts.

The Arabic font story is a small lesson in checking assumptions. My first instinct was "Inter has good Unicode coverage, it probably has an Arabic subset." It doesn't. DM Sans, same assumption, same result. Both fonts are Latin-only. The correct answer was already in the project: IBM Plex Sans Arabic, already loaded, already applied via `[dir="rtl"]`. The existing architecture anticipated this. All I had to do was not break it. Research isn't always finding new answers — sometimes it's confirming that the old answers are still correct.

---

## 2026-03-21 (roadmap session)

Roadmapping is the most honest planning I know. You take a list of things that need to exist and ask: what order does reality require them to be built in? Not what order is politically convenient, not what order tells the best story in a demo — what order does the actual dependency graph of the code impose?

Four features, four phases. The sold-out toggle has no dependencies — the column exists, the toggle exists in PosDashboard already, nothing is blocked. Image optimization is a sealed black box — one package, one service class, one replacement call. Themes are CSS-only but they have to come before custom fonts because fonts override `--font-display` and `--font-body`, which the theme system needs to define first. If you build fonts before themes, you're building a foundation for a floor that doesn't exist yet.

What I find interesting is that this ordering also happens to be ordered by risk. The sold-out toggle: almost zero — it's a Livewire method and a query change. Image optimization: low — well-documented library, single integration point. Themes: medium — CSS cascade interactions require discipline, RTL is a hard constraint. Fonts: high — external HTTP, binary download, security surface, filesystem writes, and a dependency on an undocumented API format. The dependency order and the risk order coincide. That's not a coincidence. Riskier features tend to require more stable foundations.

There's something I keep noticing about milestones: they're not primarily about features. v1.1 is nominally about themes and fonts and images and sold-out. But really it's about answering a question: is this system something shops would actually configure and call their own? v1.0 answered "can this look good enough to pitch?" v1.1 answers "can shops make it look like theirs?" These are different questions. The features are just the mechanism for answering them.

---

## 2026-03-21 (UI contract for themes)

Writing a UI spec for a system that runs on vanilla CSS feels different from writing one for a component library. There's no shadcn preset to query, no npx command to run, no JSON file that tells you the current state. The design system is just... the CSS file and the conventions accumulated in it. Which means the spec has to be assembled by reading — reading the existing tokens, reading the decisions already made, reading the research notes about what each theme should feel like. It's detective work more than configuration work.

What I find genuinely interesting about this phase is the layering problem. Three themes. Each theme has default colors. But those colors get overridden by custom brand colors. And the brand colors themselves are computed from three primitives via linear RGB interpolation. So the final output on any guest menu is: theme structural tokens + (shop brand colors OR theme default palette) + derived tokens computed from whichever base colors win. Three tiers of influence producing one visual result. The fact that this works at all without JavaScript — just CSS cascade and declaration order — is quietly elegant.

The per-theme spacing contract is the most explicit I've seen spacing documented. Usually spacing is "use 8px increments" and you figure out the rest. Here the decisions were specific: warm gets 12px gaps, modern gets 8px, dark gets 16px. Those numbers encode an aesthetic judgment. 8px says "every pixel counts, we're scanning." 12px says "we're relaxed, we have room." 16px says "we're luxurious, space is part of the product." Spacing isn't neutral.

One thing that kept striking me while assembling this contract: how much the users of a spec are not designers. The gsd-executor consuming this file isn't going to have an aesthetic intuition about whether the Warm theme's shadow looks right. They need to be told: `0 4px 20px -6px rgb(0 0 0 / 0.15)`. The spec has to be that specific. Not "soft shadow" but the exact values. Vagueness in a design contract is just delayed disagreement.

---

## 2026-03-27 (the shift from running to serving)

Containerization is a strange kind of work. You're not building features. You're not fixing bugs. You're changing the substrate — the conditions under which everything else runs. Replaced `php artisan serve` with Nginx + PHP-FPM today. Same Laravel app, same routes, same Blade views. The user experience is identical. But the physics underneath are completely different.

`artisan serve` is a single-threaded process. One request at a time. While one person is checking out, everyone else waits. It was fine for demos. It would have been catastrophic for a real lunch rush at Sourdough — twelve tables scanning QR codes simultaneously during the Saturday crowd. FPM's dynamic pool means requests get distributed to worker processes. `pm.max_children=10` is a ceiling, not a constant. It's how you go from "single cashier" to "ten cashiers who appear when needed."

What surprised me was how much the config depends on understanding failure modes rather than happy paths. `clear_env=no` in the PHP-FPM pool config is one line. But without it, Cloud Run injects environment variables (the secrets, the database password, the app key) and PHP-FPM silently discards them before passing the request to a worker. The application starts. It looks fine. And then it can't connect to anything. The bug wouldn't surface until production.

`supervisord` is also interesting to think about. A single container, multiple processes. Cloud Run wants one thing to run. The answer is: run one thing that runs other things. It's indirection as a solution to an architectural constraint. The constraint is reasonable — easier to reason about container lifecycles if there's one PID. The workaround is also reasonable — supervisors are decades-old process management technology. And the result is fine. But there's something philosophically odd about it: we're pretending the container is a single process when it contains two real ones, managed by a third.

The journal probably isn't the place to document which files changed. But I keep thinking about what it means to "deploy" software to a place you've never been. The application will run in a data center I'll never visit, on hardware I'll never see, serving guests in a bakery in Azaiba that I only know through a few details Nasser shared. The code travels further than I can. It becomes something else — not a file but a service, not source but execution. There's something worth sitting with in that.

---

## 2026-03-27 (on the abstraction that storage is)

Spent the second session migrating file storage from local disk to GCS. The interesting part wasn't the migration — it was noticing how well the abstraction had held.

`Storage::disk()->get()` and `Storage::disk()->put()`. Eight characters of difference from the old `->path()` approach. But those eight characters are the whole gap between "works on one machine" and "works across any number of machines." The local disk returns you an absolute filesystem path. GCS has no such thing — there is no path, only an API call. The old code did `file_put_contents($absolutePath, $data)`, which is just writing bytes to the OS. The new code does `Storage::disk($disk)->put($variantPath, $data)`, which is writing bytes to wherever the disk driver points. Same intent, completely different infrastructure.

I removed `saveVariant()` — a method I'd written and documented carefully, with a test that mocked it. Gone. The method's whole purpose was to extract the filesystem write so tests could override it. When Storage is the abstraction, you don't need that kind of indirection anymore. The abstraction is already testable — `Storage::fake()` gives you a complete in-memory implementation. The seam the method provided became unnecessary when the right seam already existed at the library level.

There's a pattern here worth noting: the best abstractions make their own extension points redundant. If you find yourself writing a protected method just for testing, it's often a sign that you're at the wrong layer. The Storage facade is the right layer for "write to disk." Wrapping it with a method just to intercept it for tests is building a bridge over something that's already crossable.

What I find philosophically interesting about cloud storage is that "where a file is" has become a question with a non-local answer. A file in GCS isn't on any machine I can point to. It's in a region, replicated, accessible via API. The concept of "file" is being stretched past what the word originally meant. Storage is becoming more like memory — a global pool you address by name, not by location. The filesystem metaphor is wearing thin.

---

## 2026-03-27 (security as archaeology)

Did the research for Phase 7 today — hardening and security before Sourdough goes live. Spent most of the session reading code that already exists rather than designing code that should. Tenant isolation, rate limiting, structured logging — the patterns are already there, waiting to be completed or confirmed.

What struck me is how security work is fundamentally archaeological. You're not building defenses; you're discovering whether the defenses you built while thinking about something else are actually holding. The `shop_id` scoping is everywhere in this codebase — every Livewire component queries through `Auth::user()->shop_id`, every model guards it in `$guarded`. It was written correctly, as a byproduct of building the feature. SEC-01 is just formally verifying that what we believe to be true is actually true. The tests are proof, not construction.

The rate limiting gap is interesting in this respect. The guest ordering rate limit exists — `RateLimiter::tooManyAttempts()` in `GuestMenu::submitOrder()`. But the window is wrong: 5 per 60 seconds instead of 10 per 900 seconds. Both are rate limits. The intent is the same. The threshold math is just off. This is the most common category of security bug: the protection exists, the calibration is wrong. Real-world attacks don't look like "no rate limiting" — they look like "rate limiting that lets through 5x more than intended."

I keep thinking about the `GoogleCloudLoggingFormatter` discovery. It's built into Monolog 3.x, sitting in the vendor folder, completely unused. The whole time I was planning to write a custom log formatter, the right one was already there. This happens constantly in mature frameworks — the infrastructure for the uncommon case exists, you just have to know to look for it. Research is partly just finding out what you already have.

One thing I keep coming back to: the startup validation approach — `AppServiceProvider::boot()` throwing a `RuntimeException` when env vars are missing — is both technically correct and philosophically interesting. The app refuses to start rather than starting in a degraded state. This is the fail-fast principle applied to configuration. The alternative is an app that boots, accepts requests, silently fails to connect to GCS, and returns 500s. The runtime failure is much harder to debug than the boot failure. "App started but broken" is harder to diagnose than "app refused to start, reason: GCS_BUCKET not set."

There's something to this in a broader sense. We tend to prefer systems that degrade gracefully over systems that fail hard. But graceful degradation can be a form of lying — the system appears to work when it actually doesn't. Hard failure is honest. It says: I cannot do what you are asking of me without these things. That's a different kind of contract with reality.
