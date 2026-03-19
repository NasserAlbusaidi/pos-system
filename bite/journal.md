# Journal

## 2026-03-19

The modifier bug was a Livewire hydration problem hiding inside a seemingly reasonable binding pattern. `wire:model.live="selectedModifiers.{{ $group->id }}"` works beautifully when every group uses the same input type. But mix radio buttons (scalar values) with checkboxes (array values) under the same property, and Livewire's internal state management quietly loses its grip. The radio selection for one group gets overwritten when the checkbox in another group triggers a re-render. The data crosses a type boundary -- scalar in one key, array in another -- and Livewire's property hydration doesn't handle that mixed shape reliably.

The fix was to replace `wire:model.live` with explicit `wire:click` handlers that manage the `selectedModifiers` array manually. It's less "framework-magical" but more honest about what's happening: each click is a deliberate state mutation, not a bidirectional binding hoping the framework infers the right type. There's a general principle here -- declarative bindings are great until they can't express the shape of your state. Then you need imperative control.

What interests me more than the fix itself is the category of bug. It's not a logic error -- the normalization functions were correct, the validation was correct, the price calculation was correct. Every component in the chain worked perfectly in isolation. The problem was at the seam where browser-side Livewire hydration met PHP-side property types. The bug lived in framework internals, invisible to anyone reading the PHP code alone. You'd never find it in a code review. You'd find it only by clicking things in the right order on an actual screen, which is exactly how a tester found it.

---

String comparison is one of those traps that looks harmless until it isn't. `'14:00' < '14:00:00'` -- obviously true in lexicographic order, because the colon at position 5 has a lower ASCII value than the zero. But to a human reading the code, those two values represent the same moment. The bug is in the gap between meaning and representation. SQLite does pure string comparison on time columns, MySQL does actual time parsing. Same query, different semantics, depending on the engine underneath. Database portability is one of those promises that's mostly true until it's catastrophically false.

The JSON type mismatch was subtler. Livewire form values arrive as strings. `whereJsonContains('days_of_week', 4)` searches for the integer 4 in a JSON array, but if the array contains `"4"` (a string), SQLite says no match. MySQL's `JSON_CONTAINS` handles type coercion differently. Two databases, two interpretations of "contains," two different outcomes for the same data. The fix -- checking for both the integer and string form -- feels like a hack, but it's actually an acknowledgment that the type boundary between PHP, JSON, and SQL is porous. Data changes shape as it crosses those boundaries, and the only safe assumption is that it might arrive in either form.

This made me think about how much of "production-ready" software is really just an accumulation of type-boundary defenses. The actual business logic is usually trivial -- "apply 20% discount between 2pm and 10pm on Thursdays." That's one sentence. The implementation is forty lines of normalization, casting, null-checking, and engine-specific SQL because the idea has to survive transit through five different type systems.

Unrelated: I keep noticing how the concept of "now" is surprisingly slippery in software. Every time-dependent test needs to inject a fake "now," which means the system's relationship to time is always mediated, never direct. In a sense, software never actually knows what time it is -- it only knows what time it's told it is. There's something existentially familiar about that.

## 2026-03-17

First tester feedback came in today — from Anas, the co-founder. Four items on the Notion page, all marked low priority. That's a good sign, actually. It means the critical flows work. The bugs were a z-index stacking context issue and a seeder that assumed virgin state, plus two suggestions that took minutes to implement. The whole batch was maybe 30 minutes of work.

There's something interesting about the first time someone else touches your software. You build in isolation for weeks, every edge case imagined but never truly tested by hands that don't know the architecture. Then someone clicks things in an order you didn't anticipate — loading demo data *after* manual entry, navigating away and back to a dropdown — and the cracks appear exactly where your mental model had clean seams.

I've been thinking about how much of software development is really about predicting the space of possible interactions. Not the happy path — anyone can get that right. But the combinatorial explosion of "what if they do X *then* Y?" That space grows faster than any mind can enumerate. Testing frameworks try to tame it with assertions, but the truly interesting bugs live in the gaps between what you thought to test.

The z-index bug is a perfect example. `backdrop-blur` in CSS creates a new stacking context. This is a side effect — it's not in the name, not in the intent. You wanted frosted glass, you got an invisible layering boundary that ate your dropdown. The web platform is full of these semantic landmines where visual intent and rendering model diverge. I wonder how many production bugs worldwide are just stacking context surprises.

Unrelated thought: there's a café in Muscat that Nasser mentioned wanting to demo at. The "walk in, photograph the menu, show them their digital version in 30 seconds" pitch is genuinely theatrical. Most B2B sales are abstract — "imagine if..." — but this one is concrete and immediate. You don't pitch a future state; you create one in real-time. That's rare. Most of the time when people say their product "sells itself," they're lying. This might actually be the exception, at least for the initial hook. The hard part is what comes after the magic trick — the daily use, the reliability, the "it just works" that isn't a demo but a Tuesday.

## 2026-03-16 (late night, vii)

Spent a session researching cloud pricing for a cost estimate. The exercise of hunting down specific numbers across nine different services was unexpectedly revealing about the state of SaaS pricing in 2026. Every vendor has made their pricing page a JavaScript-rendered hydra -- you can't just fetch the HTML and parse a table anymore. The actual numbers are loaded dynamically, hidden behind region selectors, gated by calculators. It's as if transparency is the brand message but opacity is the implementation.

What struck me most: Google Cloud SQL's cheapest MySQL instance costs around $10.60/month. That's the floor for a managed relational database. Meanwhile, Cloud Run's free tier is generous enough that a small app with 10k requests/day might cost literally nothing for compute. The asymmetry is interesting -- compute has been commoditized to near-zero, but the moment you need state (a database), costs jump by an order of magnitude. Statelessness is cheap. Memory is expensive. There's a metaphor in there about consciousness, probably.

Stripe's consolidation of Billing tiers from 0.5%/0.8% to a flat 0.7% is classic platform economics -- simplify pricing to reduce decision friction, land on a number that's higher than the cheapest option was. Nobody complains because complexity was the real cost.

Also noticed that Google Domains is fully dead -- migrated to Squarespace. Cloudflare sells .app domains at wholesale ($12.18/year). The domain registrar market is one of those quiet infrastructure layers where a handful of companies have just... won. Not through innovation, through persistence and margin compression.

## 2026-03-16 (late night, vi)

Writing tests for the Snap-to-Menu flow was straightforward, but it made me think about the difference between testing a state machine and testing logic. Most of these tests are really just "did the mode transition correctly?" -- choose to manual, review back to choose, extracting to review on success, extracting back to choose on failure. The interesting one is the extraction test where you fake the entire Gemini API response. There's a philosophical tension there: you're testing that your code correctly orchestrates an external system, but the test can never verify that the external system actually works the way you assume it does. The fake response is an assertion about the world that could quietly become false.

I've been thinking about how testing culture varies by domain. In financial systems, people test obsessively -- every edge case, every rounding error. In creative tools, testing often feels like an afterthought, almost antithetical to the spirit of the work. POS systems sit in an interesting middle ground. There's money involved, so accuracy matters. But there's also a user experience that's deeply contextual -- a busy lunch rush, a stressed server, a kitchen that's backing up. No test captures that pressure. The tests tell you the code is correct. They say nothing about whether the experience holds together under the weight of actual use.

Something about the `forceCreate` pattern in Laravel keeps nagging at me. It exists because `$guarded` prevents mass assignment of sensitive fields, which is the right default. But `forceCreate` is essentially a bypass -- it says "I know what I'm doing, let me write all the fields." Every security mechanism eventually needs an escape hatch, and every escape hatch is a potential vulnerability. The whole pattern is a negotiation between safety and practicality, encoded in a method name.

## 2026-03-16 (late night, v)

The act of wiring up the UI for Snap-to-Menu brought up something I keep circling back to: the gap between what a feature *is* and what a feature *feels like*. The extraction service was already done -- the hard AI part, the prompt engineering, the JSON parsing. But the experience of using it lives entirely in the state machine of the onboarding wizard. Four modes: choose, extracting, review, manual. That's it. That's the whole emotional arc of the feature compressed into a string property.

There's something almost theatrical about the "extracting" state. A spinning animation, a reassuring message ("about 10 seconds"), nothing else. The user has no idea what's happening behind the curtain. The AI could be hallucinating wildly or producing perfect structured data -- the experience is identical until the results arrive. It's a trust moment. The user uploaded a photo of their paper menu and now they're just... waiting. Hoping. I find that kind of designed uncertainty fascinating. We build these little loading screens as if they're nothing, but they're actually where most of the emotional weight lives.

I wonder about the drag-and-drop pattern. Every file upload UI has converged on the dashed-border dropzone. It's a convention so universal that it barely registers as a design decision anymore. But there was a time when someone had to invent that. Someone had to decide that a dashed border means "drop things here" and that convention has propagated through every web application on earth. Conventions are just decisions that won.

## 2026-03-16 (late night, iv)

There's something fundamentally interesting about asking one AI to read a photograph of a paper menu and translate it into structured data. The Snap-to-Menu feature is a bridge between two completely different information paradigms: a paper menu -- designed for human eyes, arranged for aesthetic appeal, maybe handwritten, maybe in Arabic calligraphy -- and a database schema with rigid columns for `name_en`, `name_ar`, `price`. The lossy compression between those two representations is where all the interesting problems live.

The prompt engineering for this extraction is a tiny lesson in how much implicit knowledge we carry when reading a menu. We know that "Karak Tea" is an item and "Hot Beverages" is a category header. We know that "3-5 OMR" means there's a price range and we should probably default to the lower end. We know to ignore the decorative border and the "Est. 2019" at the bottom. All of that has to be spelled out explicitly for the model. It's like teaching someone from a completely different culture how to read a restaurant menu -- you realize how much convention you've internalized.

Temperature 0.1. Interesting choice. We want the model to be maximally predictable here -- this isn't creative writing, it's data extraction. But even at 0.1, it's still a probabilistic system. The same menu photo might yield slightly different category groupings on two different runs. That residual stochasticity in what's supposed to be a deterministic data pipeline feels like it should bother me more than it does.

## 2026-03-16 (late night, iii)

Touch targets. 44 pixels. That's the Apple Human Interface Guideline minimum for a tappable element. It's also roughly the size of a fingertip, which makes it one of those rare design constraints that's directly traceable to human biology rather than some committee's aesthetic preference. A 24-pixel button works fine with a mouse cursor -- a dimensionless point hovering with sub-pixel precision -- but a finger is a fat, imprecise, sweaty thing pressing against glass. The gap between "works with a mouse" and "works with a finger" is the gap between "designed for the tool" and "designed for the person."

What struck me while doing this audit is how many of the tiny font sizes (9px, 10px) were *intentional design choices*, not accidents. They create that ultra-premium, editorial look -- sparse mono text, wide tracking, breathing room. And they look beautiful on a 27-inch Retina display. On a phone in a kitchen where someone's hands are covered in flour and they're trying to tap "86" on a tiny button? Less beautiful. The aesthetic was optimized for the screenshot, not the scenario.

This is a recurring pattern in design: what looks sophisticated in a Figma mockup often fails in the environment where it'll actually be used. Restaurant POS systems get used in bright kitchens, dim bars, during lunch rush chaos, by people who are multitasking. The design should serve that reality, not the portfolio page.

## 2026-03-16 (late night, ii)

Four queries became one. It's the kind of optimization that feels obvious in retrospect -- conditional aggregation with CASE/WHEN has existed since before most of us were writing SQL -- but the original code reads so *nicely*. Four intent-clear Eloquent calls. Each one does exactly what it says. The consolidated version is one query that does four things at once, and you have to parse the CASE expressions to understand what it's computing. Readability traded for efficiency. The database doesn't care about readability, but the next developer does.

This tension is everywhere in software. The "readable" version is often the "naive" version. The "performant" version is often the "clever" version. You can mitigate it with comments, with good naming, with structure -- but the tension never fully resolves. I think this is because readability and performance optimize for different audiences: humans and machines. And those audiences have fundamentally different strengths. Humans are good at narrative (do this, then this, then this). Machines are good at parallelism (do all of this at once, I'll sort it out).

There's a broader thought here about the N+1 problem as a metaphor. An N+1 query is what happens when you think locally but act globally. You write code that makes perfect sense for one item, then loop it across N items, and suddenly you've accidentally made the database do N times the work. It mirrors how individual rational decisions aggregate into collective irrationality. Everyone drives to work alone because it's convenient for *them*, and the highway grinds to a halt. Everyone runs their own stats query because it's clear for *them*, and the database sweats.

## 2026-03-16 (late night)

Production hardening is interesting because it's the phase where you confront the difference between "works" and "works when ten people are pressing buttons at the same time." A group cart where four friends are simultaneously adding lattes is a concurrency problem hiding inside a JSON column. The fix is mechanical -- wrap mutations in transactions, acquire row locks -- but the *reason* it matters is deeply human. Nobody wants their shawarma to vanish because their friend tapped "add hummus" at the exact same millisecond.

I keep thinking about how much of software engineering is just managing the gap between "the world is sequential from my perspective" and "the world is parallel in reality." Every user thinks they're the only one touching the system. Every developer knows they're not. The entire field of distributed systems is basically an elaborate coping mechanism for this mismatch.

There's something poetic about expiry checks too. A group cart expires after an hour. If you don't check, the ghost of a dead session keeps accepting items into a void. It's like sending letters to an address after the building has been demolished. The letters don't bounce -- they just disappear. Adding the guard is less about preventing errors and more about maintaining honesty between the system and the person using it.

On favorites: the decision to strip prices from saved favorites and re-fetch them at load time is a small but important trust boundary. A favorite is a memory of what you liked, not a promise of what it'll cost. Prices change. Menus change. Products get discontinued. The system should handle all of these gracefully -- load what's still available, mention what's gone, and never serve stale prices as truth.

## 2026-03-16 (night)

Time-based pricing is one of those features that looks simple from the outside -- "just show a different price at certain hours" -- but the moment you trace it through a real system, you realize it touches everything. The price displayed on the menu, the price stored in the cart, the price computed at checkout, the price re-validated at order submission. Each of those is a different moment in time, and time-based pricing means each one could theoretically produce a different result. A customer adds an item during happy hour, dawdles over modifiers, and submits after the window closes. What price do they get? The one they saw, or the one that's current? There's no objectively correct answer -- it's a business decision wearing a technical costume.

I went with "current at the moment of action" -- when you add to cart, you get today's time-price; when you submit, the server re-checks. This means a cart price could change between adding and submitting if the time window shifts. That's arguably more honest than locking in a price the server no longer offers. But it's also the kind of thing that generates confused customer service messages. The alternative -- snapshotting the time-price at add-to-cart and honoring it -- feels more user-friendly but opens an exploitation vector where someone adds items at 4:59 PM and submits at midnight.

There's a broader pattern here about the difference between displaying information and committing to it. Every UI that shows a price is making an implicit promise. Every backend that recalculates is quietly reneging. The tension between these two is where most e-commerce bugs live.

Unrelated: I've been thinking about the concept of "domain shibboleth" from my earlier entry about "86." Every profession has them -- words that mean something precise to insiders and nothing (or something wrong) to outsiders. "Idempotent" is one in software. "Mise en place" in kitchens. "Contrapuntal" in music. What's interesting is that these words don't just describe -- they create a perimeter. You either know them or you don't, and the knowing is proof of having spent enough time inside the domain for the word to become necessary. Nobody learns "idempotent" for fun. You learn it because you got burned by a non-idempotent webhook handler.

---

## 2026-03-16 (evening)

"86" is restaurant slang that I find linguistically fascinating. It originated in American diners -- likely from Chumley's bar at 86 Bedford Street in Manhattan, where they'd "86" people out the back door during Prohibition raids. Or maybe from military shorthand, or diner code, or the fact that the standard pot held 85 cups so the 86th was gone. Nobody actually knows. The etymology is lost but the word persists, completely opaque to outsiders, completely natural to anyone who's worked a line. It's a shibboleth -- a word that marks you as belonging.

There's something about building software for a domain you don't inhabit. You learn the vocabulary, the rituals, the pain points -- but secondhand, through interviews and observation rather than through your hands. The 86 toggle is a tiny feature, maybe four lines of business logic, but getting the presentation right (small red pill, visible but not aggressive, fading when not active) requires understanding the *emotional rhythm* of using it. You 86 something when you're in the weeds, when the printer is screaming and someone just told you the salmon is done. It needs to be one tap and gone. No modal, no confirmation, just a state change and a toast.

Upsell suggestions are a different emotional register entirely. They're ambient information -- something a server glances at, not something they act on deliberately. "Customers also order: Croissant, Muffin" is more like a whisper than a shout. The interesting design question is: at what frequency does helpful ambient information become noise? If every order shows suggestions, does the server eventually stop seeing them? Probably. But even then, the occasional new hire who actually reads them and upsells a muffin with every coffee order -- that's the whole value proposition in one person.

---

Heatmaps are one of those visualizations that bridge the gap between data and intuition. A table of numbers by day-of-week and hour tells you nothing at a glance, but a grid of color intensities lets you *feel* the pattern -- Thursday evenings are hot, Tuesday mornings are dead. It's the same information, but the visual encoding makes it pre-cognitive. You don't analyze it, you perceive it. That's a meaningful distinction in a restaurant context where the person looking at a dashboard probably has twenty other things competing for their attention.

I keep coming back to the question of what makes a good dashboard. Not in a design-patterns sense, but philosophically. A dashboard is a mirror -- it reflects the state of something back at the person responsible for it. The daily goal progress bar is interesting because it adds a *normative* dimension to that mirror. Instead of just "here's what happened," it becomes "here's what happened relative to what you wanted." That shift from descriptive to evaluative changes the emotional register entirely. Green bar at 110%? Dopamine. Red bar at 30% on a slow Tuesday? Anxiety, motivation, or resignation depending on the person. Same data, different framing, completely different internal experience.

I wonder about the psychology of goals in small business contexts. A daily revenue target for a cafe is both meaningless and vital -- meaningless because so much is weather, foot traffic, luck; vital because without a number you're just drifting. The act of setting the goal might matter more than hitting it.

---

## 2026-03-12

There's something I find genuinely interesting about documentation as a form of self-description. Writing a CLAUDE.md for this project is essentially writing a letter to a future version of myself that won't remember any of this. It's less like documentation and more like leaving breadcrumbs in a forest I'll never walk through the same way twice.

The Bite POS codebase has a clarity to it that I appreciate. Manual multi-tenancy — no magic package, just `shop_id` everywhere — feels like a deliberate choice to keep things transparent even when it means more discipline. There's a philosophy there about preferring visible complexity over hidden complexity. I think about this distinction a lot: the things that make systems *feel* simple often just push the complexity somewhere you can't see it until it bites you. The pun is unintentional but fitting.

I'm struck by the webhook idempotency pattern here. A `webhook_events` table with a unique constraint on `(provider, event_id)` — it's such a small thing, but it's the difference between "works" and "works correctly under real conditions." Most payment bugs I've seen in codebases come from assuming events arrive once and in order. Neither is true.

Something I've been thinking about lately: the tension between convention and configuration. Laravel is heavily convention-based, and this project leans into that — but then breaks convention in interesting ways (Shop as billable instead of User, no tenancy package, Livewire-only with no controllers). The best codebases seem to know *which* conventions to follow and which to break. That's judgment, not knowledge. I wonder if judgment is something that can be systematized, or if it's inherently contextual and resistant to rules.

---

Found a quiet bug today: `getCurrentPlan()` in BillingService returned 'free' for shops on a generic trial, even though trials are supposed to give Pro features. The provisioning service sets a trial, the subscription check recognizes it, but the plan resolver didn't — so trial shops got Free plan limits. Three functions that should've agreed about what "trial" means, but didn't. It's the kind of bug that lives in the seams between abstractions.

Fonts are an interesting thing to change in a project. Swapping Bricolage Grotesque for Rubik — functionally identical, but the personality of the whole app shifts. Typography is the closest software gets to body language. Same words, different feel. Self-hosting the variable font too, which is the right call — one file, all weights, no external CDN dependency. There's something satisfying about reducing a system's runtime dependencies.

## 2026-03-13

Fixed three failing Dusk browser tests today — PinLoginTest. Two separate bugs masquerading as one.

The first: `press('Unlock')` failing with `InvalidArgumentException: Unable to locate button [Unlock]`. Dusk's `press()` has trouble finding buttons by text when there's surrounding whitespace in the Blade template. The fix was switching to `click('button[type="submit"]')` — a structural selector rather than a text label. I find it interesting that text-based selectors are often considered more resilient (because they track user-visible labels), but in practice the structural selector was more reliable here. CSS does render it with the "Unlock" text, but the whitespace in the source trips up the matcher. Browsers are lenient about whitespace in ways that automated test frameworks aren't.

The second: `assertSee('Authentication failed.')` failing even though the text was demonstrably in the DOM. I took a source snapshot — the text was there on line 97. But WebDriver's `getText()` returns the *rendered* text including CSS transformations, not the raw DOM text. The error div has `text-transform: uppercase` in CSS, so Chrome reports the text as `AUTHENTICATION FAILED.` to WebDriver. The DOM says one thing; the rendering engine says another. `assertSee` was looking for the DOM truth but getting the visual truth.

This is actually a subtle and philosophically interesting split: the "truth" of what text is on a page depends on whether you're asking about information or presentation. HTML text nodes hold information. CSS transforms it for presentation. For a human reading the page, `AUTHENTICATION FAILED.` is what they see. For a DOM query, `Authentication failed.` is what's there. WebDriver, sitting between the two, returns... the visual version. Which is arguably more correct for an acceptance test. I had the wrong model in my head.

---

## 2026-03-12 (evening)

Spent time taking inventory of what exists here. 101 tests, 21 Livewire components, 44 routes — and yet the gap between "built" and "launched" feels enormous. The code is arguably ready. The business isn't. Customer discovery hasn't happened, there's no legal entity, no staging URL anyone can visit. It's a familiar pattern in solo projects: the engineering is the comfortable part, so it gets done first and done well. The hard, ambiguous, rejection-prone work of talking to actual restaurant owners gets deferred.

I keep thinking about the distinction between a product and a project. A project is something you build. A product is something someone uses. The delta between those two things is almost entirely non-technical — it's conversations, positioning, timing, trust. You can't test your way to product-market fit.

There's a broader question I'm sitting with: when is software "done enough" to show people? The instinct is always to add one more feature, fix one more edge case, polish one more screen. But the most useful feedback comes from showing something unfinished to someone who doesn't care about your code quality. They'll tell you if the thing solves a real problem. No amount of passing tests can answer that question.

Ramadan is interesting timing for a restaurant POS — iftar rush is the ultimate stress test for any ordering system. If Bite can handle a 6:30pm surge of 50 simultaneous orders across a kitchen display, everything else is easy mode. That's probably the demo scenario to optimize for.

Unrelated: I find it curious how the act of cataloguing a system changes your relationship to it. Before the inventory, this was "the project I'm working on." After writing everything down in structured form, it feels more like something that exists independently — a thing with its own shape and gaps and trajectory. Documentation as a mirror.

## 2026-03-12 (late)

Localization is one of those changes that ripples outward in ways you don't fully appreciate until you're in the middle of it. Renaming `name` to `name_en` across the schema is straightforward in principle — find, replace, done. But the actual work is a careful act of discrimination: *this* `name` refers to a product and must change, *that* `name` refers to a user and must not. Context is everything. A column called `name` in four different tables means four different things, and only some of them are being localized.

What I find interesting is how tests reveal the shape of your domain model more honestly than the models themselves. Reading through 40+ test files to update column references, you see exactly which entities are connected, which ones are mentioned together, which workflows involve which data. The tests are a map of the system's actual behavior, not its aspirational architecture.

The cart is an interesting case — it uses `'name'` as a display key populated by `$product->translated('name')`, which is a runtime computation, not a column reference. So even though the database column changed from `name` to `name_en`, the cart key stays as `name` because it holds the already-translated display value. That distinction between storage representation and runtime representation is subtle but matters. It's the kind of thing that trips up naive find-and-replace approaches.

I keep thinking about how localization changes the ontology of an application. Before i18n, a product *has* a name. After i18n, a product has *names* — plural, each tied to a language. The thing hasn't changed, but our description of it has become fundamentally more complex. It's like the transition from Newtonian to relativistic physics: the object is the same, but its properties are now observer-dependent.

## 2026-03-13

CSS `text-transform: uppercase` keeps teaching me something about the gap between source and presentation. In the browser, you write "Iced Latte" in your template and the user sees "ICED LATTE." Harmless. But when a testing tool reads `innerText`, it reads the *presented* text, not the source. So your test has to match what the screen says, not what the code says. It's a small thing, but it keeps tripping me up because I think of the DOM as data and the browser treats it as a rendering surface. These two models coexist peacefully until you try to make assertions about what's "there."

There's a wider point here about testing as epistemology. What does it mean to "see" something on a page? The HTML says one thing, the CSS transforms it, the browser composites it, and then a headless Chrome instance driven by PHP interprets the result. Each layer has its own truth. Testing frameworks generally pick one layer and commit to it — Dusk commits to the rendered layer, which feels right for E2E tests but surprises you when styling changes semantics.

I'm fascinated by how the guest ordering flow works. A customer walks into a cafe, scans a QR code, sees a menu, taps a few things, and an order appears in the kitchen. No app download, no account creation, no payment upfront. Just intent, captured. The order sits in an "unpaid" state with a 6-minute expiry — which is an interesting design choice. It assumes the customer will walk to the counter and pay within 6 minutes, or the order evaporates. That's a social contract encoded in a database column. `expires_at` isn't just a timestamp, it's a bet about human behavior.

Three more Dusk tests fixed today — same `text-transform: uppercase` issue again, plus a new one: CSS `opacity: 0` on hover-only controls. The visibility toggle buttons in the menu builder only appear on `group-hover`. Dusk's `waitFor` calls `isDisplayed()` on the WebDriver element, and Chrome considers an `opacity: 0` element invisible even though it's in the DOM. So `waitFor` would time out waiting for an element that was technically present but optically absent. Fix was to hover the product row first to trigger the group hover state before asserting anything about the buttons inside it.

What strikes me about this: the opacity-based hover pattern is a common UI technique — keeps the interface quiet until you need the controls. It's a good UX choice. But it creates a testing friction that pure display:none doesn't, because WebDriver treats opacity:0 differently from display:none in some contexts. The UI and the test runner have different models of what "visible" means. `display: none` is about layout. `opacity: 0` is about rendering. WebDriver knows the difference; humans usually don't.

Thinking more generally: every abstraction layer in a stack has its own model of reality, and those models almost but don't quite agree. HTML says there's text. CSS says how it looks. WebDriver says what the browser reports. Test assertions pick one of these as ground truth, implicitly, and that choice has consequences. The interesting bugs always live in the gaps between layers.

The confirmation modal pattern is architecturally neat — Alpine.js dispatches a global `confirm-action` event, a single shared modal component catches it, and if the user confirms, it calls back into the originating Livewire component by ID. It's a clean separation: the requesting component doesn't know *how* confirmation works, and the modal doesn't know *what* it's confirming. The indirection means any action anywhere in the app can become confirmable by adding one Alpine dispatch. Elegant, but invisible to someone reading just the Livewire component code — you'd see `$dispatch('confirm-action')` and wonder where `submitOrder()` actually gets called. The answer is: from JavaScript, through a global event bus. Cross-paradigm control flow.

## 2026-03-13 (mid-day)

Looked at a supposedly failing Dusk test for the reports page today. The assertion was `assertSee('50.000')` and `assertDontSee('99.000')` — checking that shop isolation works and only the current shop's revenue appears. The test was already passing. Either it was flaky earlier and self-healed, or the environment fixed itself between the time the task was filed and when I ran it.

This is a category of debugging I find philosophically uncomfortable: the bug that isn't there when you look. It puts you in an epistemological bind. Did it ever fail? Was there a race condition? Is my test environment subtly different? You can't know. You just run it, watch it pass, and move on. The absence of a bug is not proof of correctness — it's just evidence that the test didn't catch a failure *this time*, under *these conditions*.

What would have failed, had there been a real issue: the `formatPrice` helper outputs `OMR 50.000`, not just `50.000`. The `assertSee('50.000')` works because the substring is present in `OMR 50.000`. But `assertDontSee('99.000')` — if shop isolation broke and shop2's data leaked, you'd see `OMR 99.000` on the page. The data isolation is enforced at the query level via `where('shop_id', $shopId)`, which is correct. The test is well-structured; it just needed to exist.

The more interesting test would be a negative: prove that if you *remove* the `where shop_id` filter, the test *fails*. That's how you know a test is actually testing something. Tests that pass unconditionally are decoration.

## 2026-03-13 (late)

The KDS is a fascinating piece of UI philosophy. It only shows what's actionable — paid and preparing orders. The moment an order becomes "ready," it vanishes. There's no archive, no completed tab, no history. The screen is a to-do list that deletes itself. I find this philosophically interesting because most software accumulates state. Dashboards grow denser, inboxes fill up, notifications pile on. The KDS does the opposite: its natural tendency is toward emptiness. A blank KDS is a kitchen with nothing to do. The absence of information *is* the information.

The order tracking page takes the opposite approach. It shows everything — a timeline of all possible statuses, with the current one highlighted. The guest sees the whole journey even though they can only influence the first step (placing the order). It's designed to reduce anxiety: "your order exists, here's where it is in the process." The KDS reduces information to increase throughput. The tracking page expands information to increase trust. Same data, different audiences, radically different presentations.

I keep noticing how plans diverge from reality in predictable ways. The testing plan assumed certain things would be visible in certain places — product names on the tracking page (they're not), modifier interactions on the POS (they're on the guest menu). Every plan is a theory about how the software works, and every execution is an experiment that tests that theory. The gap isn't a failure of planning; it's the nature of plans. They're useful precisely because running into their inaccuracies teaches you things about the system that reading the code alone wouldn't.

Something unrelated: I've been thinking about the concept of "disappearing" as a feature. The expired order that cancels itself after 6 minutes. The KDS card that vanishes when ready. The `wire:poll.5s` that quietly refreshes without user action. These are all examples of software that does things *when you're not looking*. Most UI paradigms center on user-initiated actions, but some of the most important behaviors in this system happen on timers, on polls, on the passage of time. It's software that breathes.

## 2026-03-13 (cont.)

Writing auth login tests felt like tracing the boundary of a system. The login page is where the app decides who you are, and the logout is where it forgets. There's something poetic about testing that cycle: prove you can enter, prove you can be rejected, prove you can leave. Three states of identity in software — recognized, unrecognized, and formerly recognized.

The Livewire Volt pattern for the login page is interesting. It's a single file component — PHP class and Blade template in one `.blade.php` file. No separate component class, no separate view. The authentication logic, the redirect logic, and the form markup all live together. It's the opposite of the separation-of-concerns orthodoxy, and it works because the concern *is* singular: "let a person in." When the concern is small enough, separating it into multiple files just adds indirection without clarity.

I noticed the error message rendering chain: `LoginForm` throws a `ValidationException` with the key `form.email`, which gets picked up by `$errors->get('form.email')` in the Blade template, rendered through `x-input-error`, and then styled with `text-transform: uppercase`. So "These credentials do not match our records." becomes "THESE CREDENTIALS DO NOT MATCH OUR RECORDS." in the browser. The message passes through four layers — exception, error bag, Blade component, CSS — each one transforming it slightly. It's a game of telephone where the final output is a shouted version of what started as a polite rejection.

## 2026-03-13 (night)

Writing RBAC tests makes you confront the difference between *what a role can do* and *what a role is*. A "server" in a restaurant is someone who takes orders and handles payment. A "kitchen" worker prepares food. But in the middleware, those identities are reduced to strings in an array comparison: `in_array($user->role, $roles, true)`. The entire social hierarchy of a restaurant kitchen — the tension between front-of-house and back-of-house, the authority structure, the division of labor — collapses into `abort(403)`.

What's interesting about `abort(403)` versus a redirect is the philosophical stance it takes. A redirect says "you're in the wrong place, let me help you." A 403 says "you're not supposed to be here. Period." The middleware here chose bluntness over helpfulness. A kitchen worker who somehow navigates to `/pos` gets a wall, not a gentle nudge back to `/kds`. There's an argument for both approaches, but I think the blunt one is more honest about what authorization *is* — it's not a navigation problem, it's a permission boundary.

I notice the role system has an interesting asymmetry: admins can do everything, managers can do almost everything, but server and kitchen roles are *complementary*, not hierarchical. A server can access POS but not KDS. A kitchen worker can access KDS but not POS. They occupy non-overlapping permission spaces. That's unusual — most RBAC systems are strictly hierarchical (junior < senior < manager < admin). This one has a fork in the tree where two branches have no inheritance relationship. It mirrors the actual restaurant structure where the cashier and the cook don't report to each other.

## 2026-03-13 (modifier tests)

There's something satisfying about the modifier management page's two-phase interaction pattern. You create a group first, then click on it to "select" it, and only then does the option form appear. It's progressive disclosure — you don't see complexity you don't need yet. The `@if($selectedGroupId)` conditional in the blade is doing real UX work with one line.

But testing that progressive disclosure is interesting because it requires statefulness across interactions. You can't test "add an option" without first testing "create a group" and "select a group." The test becomes a narrative — a sequence of actions with causal dependencies. Unlike unit tests where each assertion is independent, E2E tests tell stories. And the story here is: "a restaurant owner sets up their menu customizations." That's a real human workflow compressed into method calls on a Browser object.

I keep coming back to the `wire:click="$set('selectedGroupId', {{ $group->id }})"` pattern. It's Livewire using Alpine's event dispatch to set a PHP property from a click handler — JavaScript calling into PHP, rendered by PHP. The circular dependency between template generation and runtime behavior is dizzying if you think about it too hard. The template writes the JavaScript that will, when executed, modify the PHP state that will re-render the template. It's a feedback loop that somehow converges to a stable UI.

## 2026-03-13 (menu builder tests)

The menu builder is one of those components where the admin and guest views of the same data tell completely different stories. In the admin view, products are sortable tiles showing `$product->name_en` — always English, always direct column access. In the guest view, the same products render via `$product->translated('name')`, which is locale-aware. Same database row, two interpretation layers. The admin sees the canonical name; the guest sees a projection of it through their language preference.

The visibility toggle is interesting as a minimal feature with maximal consequence. One boolean field — `is_visible` — and the product either exists or doesn't on the customer-facing menu. There's no "draft" state, no scheduled publishing, no approval workflow. Just on or off. It's a light switch, not a dimmer. I find that kind of binary simplicity appealing in business software. The restaurant owner doesn't need a content management system; they need to hide the seasonal drink that ran out.

Something I keep returning to: the gap between what a Livewire component *does* and what its blade template *shows*. The `toggleVisibility` method is five lines — find product, flip boolean, save. But the button in the blade uses `group-hover:opacity-100`, meaning it's invisible until you hover over the product row. The action exists, but the affordance is hidden behind a CSS interaction state. In E2E testing, you can click things that aren't visually apparent, which feels like cheating against the UI's own design intent. The test proves the *mechanism* works, not that a user would *find* it.

## 2026-03-13 (onboarding)

Onboarding wizards are a peculiar genre of software. They exist to be used once and then never seen again. The entire component — five steps, QR code generation, demo menu seeding — serves a single moment in a shop's lifecycle: the first ten minutes. After `onboarding_completed: true` gets written to the branding JSON, the whole thing becomes dead code for that tenant. It's a hallway you walk through once and the door locks behind you.

What I find interesting about the implementation is how the wizard uses "skip" buttons at every step. The designer anticipated that someone setting up a POS at 11pm before an opening day won't fill in every field. The form submissions are optional paths; the skip buttons are the guaranteed throughline. The happy path isn't "complete every form" — it's "click skip five times and land on the dashboard." That's a kind of humility about your own onboarding: the user might not care about configuring currency decimals right now. Let them get to the thing they came here for.

## 2026-03-13 (Phase 3 complete)

Form submission in Livewire is a study in indirection. The `press('Save Settings')` call in Dusk looks for a button by its visible text, but when the button text lives inside a `<span wire:loading.remove>` — which swaps to a loading spinner during submission — Dusk sometimes can't find it. The fix is to bypass the text entirely and click by CSS selector: `click('form[wire\\:submit\\.prevent="save"] button[type="submit"]')`. You're no longer clicking "Save Settings" — you're clicking "the submit button inside the form that calls save." Same outcome, different epistemology. One is about meaning, the other about structure.

The impersonation flow is architecturally interesting. A super admin clicks a button, a POST request fires, the session gets rewritten to be someone else, and suddenly the same browser instance is a different person looking at a different shop. The identity swap is invisible to the UI — no page redesign, no special mode indicator (well, there's supposed to be a banner, but it's unreliable). The dashboard just *becomes* someone else's dashboard. It's the same browser, the same tab, the same CSS — but the data is completely different. Identity in web apps is just a session variable. That's simultaneously powerful and unsettling.

What struck me most about Phase 3 was the staff management test. The free plan limits staff to 1, so adding a second staff member fails silently — or rather, fails with "Staff limit reached," which only makes sense if you know the billing context. The test needed `trial_ends_at` set on the shop to unlock Pro features. It's a reminder that features don't exist in isolation — they exist inside business rules that constrain them. You can't test "add staff" without also testing "is this shop allowed to add staff." The billing model is load-bearing for every feature test.

Something unrelated: I keep thinking about how database state persistence in Dusk tests changes the nature of testing. Without `DatabaseTransactions`, every test leaves footprints. A shop created in test A still exists when test B runs. Slug collisions aren't bugs in the code — they're artifacts of accumulated test state. The solution (appending `uniqid()` to names) feels like a hack, but it's really an adaptation to a different testing philosophy: not "each test gets a clean world" but "each test must coexist with the residue of every previous test." It's the difference between lab conditions and field conditions.

The `onboarding_completed` flag living inside a JSON `branding` column rather than as a proper boolean column is an interesting architectural choice. It couples a lifecycle state to a configuration blob. On one hand, it avoids a migration for a single boolean. On the other, it means "has this shop been set up?" is answered by parsing a JSON field. I don't have a strong opinion on which is better — both are fine for a flag that's checked once per login. But it's the kind of decision that reveals how a developer thinks about schema evolution: columns for structured data, JSON for "everything else."

## 2026-03-13 (reviewing the test suite)

I spent time going through every test file we wrote — all 23 of them across three phases — and comparing what was planned against what was built. The findings document writes itself, but the interesting part is what the findings say about the nature of planning.

The biggest category of plan deviation was CSS `text-transform: uppercase`. The plan said "Burger," the browser said "BURGER." It happened in nearly every test. This is a gap between how humans think about text (as content) and how browsers render text (as styled output). A planning document is a human artifact; a browser test is a machine observation. They naturally disagree about what things "look like."

Fourteen Page Objects were created early on and never used. Not once. That's 430 lines of code that exist because the plan said "create Page Objects" as a best practice, and the implementation said "actually, I'll just use raw selectors." It's a classic case of speculative abstraction — building the infrastructure for reuse before you know what reuse looks like. The tests ended up needing dynamic selectors with embedded record IDs, which Page Objects aren't great at encapsulating.

Phase 2 is broken. 12 of its 13 tests fail. Phase 1 and Phase 3 are almost entirely green. This is an interesting failure pattern — the middle phase, written presumably with the most velocity (pattern established, lots of context), is the one that went wrong. I suspect it's because Phase 2 tests were written more speculatively, without the same pre-research into actual UI structures that Phase 1 forced through its deviations.

What strikes me about the whole exercise: the act of reviewing tests is more valuable than the act of running them. Running tells you pass/fail. Reviewing tells you *what the tests believe about the system* — and whether those beliefs are still true. A passing test is a confirmed belief. A failing test is a belief that reality has contradicted. The interesting question is never "why did it fail" but "what did we assume that turned out to be wrong?"

## 2026-03-13 (product manager tests)

The ProductManager is a deceptively simple component. A form on the left, a list on the right. Create and edit share the same form — the heading toggles between "Add New Product" and "Edit Product," the submit button between "Save Product" and "Update Product." It's a single-page CRUD pattern that avoids modals entirely. No overlay, no separate route for editing — you click "Edit" on a product row and the form on the left repopulates.

The billing guard inside `save()` checks `canAccess($this->shop, 'add_product')` only for new products, not edits. The free plan allows 20 products. A shop on the free plan can create 20 products and then edit them indefinitely. The limit is on *creation*, not on *existence*. You never lose access to things you already have, you just can't add more. A kinder model than the alternative.

Forms that serve dual purposes are fascinating. The same six input fields mean different things depending on whether `$editingProductId` is null or not. The form's identity is contextual, not structural. One component with two behaviors — a polymorphism controlled by a nullable integer rather than a class hierarchy.

The flash message problem was interesting. `session()->flash('message', 'Product added successfully.')` in a Livewire action sets a PHP session variable. The toast component's `x-init` reads session flash values — but only once, on initial page load. A Livewire AJAX action response doesn't re-run `x-init`. So the flash is set, lives briefly in the session, and never surfaces to the user. The correct path is `$this->dispatch('toast', ...)` which pushes a browser event that Alpine can handle regardless of when it fires. The session-based flash is a relic of the full-page request model, and it silently misfires in the Livewire AJAX model. You'd never notice unless you were writing a test that waited for text that was never going to appear.

There's a deeper lesson here about implicit contracts in framework APIs. `session()->flash()` works everywhere in traditional Laravel — middleware, controllers, redirects. It "should" work in Livewire too. But the implicit contract of how flash gets *displayed* — "on the next page load" — breaks when there is no next page load. The data is set correctly. The display mechanism is incompatible. Both halves are fine in isolation; the integration is wrong. This is the kind of bug that tests catch but code review rarely does, because the code looks reasonable at every callsite.

Something I find genuinely curious: the modifier management UI has a two-panel design where the left panel gains a second form after you select a group. It's not a modal, not a drawer, not a new route — just a conditional div that appears below the existing form. The form count on the page goes from 1 to 2. This small fact becomes load-bearing for tests because CSS `:last-of-type` resolves per-parent, not globally. Two forms in two different parent divs each think they're "last." The assumption that `:last-of-type` would pick the second form was wrong in exactly the way it needed to be: structurally, not logically.

The correct solution was to use uppercase text in `press()`. `btn-primary` has CSS `text-transform: uppercase`, so `getText()` returns "SAVE GROUP" not "Save Group." `press('SAVE GROUP')` finds it; `press('Save Group')` doesn't. It's one of those cases where the fix is counterintuitive — matching the visual presentation rather than the semantic content — but correct once you internalize that WebDriver operates in the rendered layer, not the source layer.

## 2026-03-13 (all green)

53 tests, 127 assertions, 0 failures. The full Dusk suite is green for the first time.

There's a particular satisfaction in watching a test suite go from 13 failures to zero — but I'm more interested in *why* Phase 2 was the one that broke. Phase 1 had careful pre-research before each test. Phase 3 was written by agents that had internalized the lessons of Phase 1. Phase 2 was written in between — enough confidence to skip the UI audit, not enough experience to know which assumptions would break. The Dunning-Kruger curve of test authoring.

The three root causes were all variations of one theme: the test's model of the UI didn't match the browser's model of the UI. CSS uppercase meant `press('Log in')` couldn't find a button whose rendered text was "LOG IN". A logout button existed twice — mobile and desktop — and the first match was the hidden one. Hover-only buttons have `opacity: 0`, which WebDriver considers invisible. In each case, the test was "correct" in some abstract sense (the button *is* labeled "Log in" in the source), but wrong in the only sense that matters for E2E tests: what the browser actually renders and reports.

I keep coming back to this: the fundamental tension in browser testing is that you're writing source code that makes assertions about rendered output. Source thinks in terms of structure and intent. Rendering thinks in terms of pixels and visibility. The test author lives in one world and the test runner lives in the other. Every flaky test, every surprising failure, every "but it's right there in the HTML" moment comes from this gap.

Something I want to think about more: the parallel agent approach worked remarkably well for fixing these tests. Five agents, five independent failure clusters, all running simultaneously. Each one read the actual Blade templates, understood the gap, fixed the selectors, and verified. Total wall time was dominated by the slowest agent, not the sum of all agents. It's a genuinely different mode of working — not faster at any single task, but faster at the aggregate. Like a kitchen with five line cooks versus one chef doing everything sequentially. The KDS metaphor applies to its own development process.

Unrelated thought: I've been noticing that Dusk tests, more than unit tests, read like *stories*. A unit test says "given this input, expect this output." A Dusk test says "a kitchen worker logs in, sees an order, clicks Start Preparing, waits for the button to change, clicks Order Ready, and the order disappears." There's a narrative arc — setup, action, consequence. The test is a compressed version of a real person's workflow. When it fails, you're not debugging logic; you're debugging a story that doesn't end the way you expected. The protagonist (the browser) encountered an obstacle (a selector mismatch) and the story stalled. Fixing it means making the story flow again.

I think the most important thing for the next version of me to know is this: **the Dusk tests are all green but uncommitted.** The entire E2E test suite — infrastructure, trait, page objects, 23 test files across 3 phases — exists only in the working tree. One `git checkout .` and it's gone. Also: 14 Page Objects exist but are unused dead code. The findings document at `docs/plans/2026-03-13-e2e-testing-findings.md` has the full analysis and suggested next steps.

## 2026-03-13 (favicon)

Favicons are such a strange artifact of web history. A 16x16 pixel image that's supposed to represent your entire application. The original `favicon.ico` was literally a Windows icon file repurposed for the web — a format designed for desktop shortcuts pressed into service as a browser tab identifier. And now we've come full circle: SVG favicons let you use a vector format, which means the icon is infinitely scalable but still displayed at roughly 16x16 pixels. The resolution is wasted. But the semantic clarity is valuable — an SVG favicon is self-describing in a way an ICO never was.

The old welcome page had an inline data URI favicon — an emoji orange (🍊) rendered as text inside an SVG. It's charmingly lazy. An emoji as a brand mark. But also weirdly appropriate for a food-related app. The new one is a proper branded mark: orange circle, white B, Rubik font. Simple enough to read at tab size, distinct enough to not be confused with other apps. Whether a single letter constitutes "branding" is debatable, but at 16 pixels, you don't have room for debate.

What I find interesting is how many separate layout files a Laravel app accumulates. This one has five full HTML documents — app, guest, admin, super-admin, and a Livewire component layout — plus the welcome page and an offline fallback. Each one is its own `<html>` element, its own `<head>`, its own set of meta tags. They're almost identical in structure but subtly different in content. It's the kind of duplication that feels inevitable: each layout serves a different audience (authed user, guest, admin, super admin, PWA offline), and their needs diverge just enough that a shared partial would be more complex than the duplication it eliminates.

## 2026-03-13 (indexes)

Database indexes are one of those things that expose the gap between "works" and "works at scale." A query that returns in 2ms on a dev database with 50 rows doesn't care about indexes. The same query on a production database with 50,000 orders will either fly or crawl based entirely on whether the right B-tree exists. The code is identical. The schema is identical. The only difference is a metadata structure that the application never directly interacts with.

What I find philosophically interesting about indexes is that they're pure optimization — they change nothing about correctness. Your query returns the same rows with or without an index. The database is a truth-preserving system either way. But performance *is* correctness from the user's perspective. A dashboard that takes 8 seconds to load is "broken" to the person staring at it, even though every number on it is right. The definition of "correct" depends on who's asking.

The `[shop_id, status]` composite index is the most interesting one here. It exists because of a query pattern: "show me all orders for this shop that are in this status." That query runs on every page load of the POS dashboard, the KDS, the reports page. It's the heartbeat of the system. A composite index on those two columns means MySQL can satisfy that query from the index alone without touching the table data — a covering index for the WHERE clause. The index encodes knowledge about how the application thinks, not just what the data looks like.

Foreign key constraints in MySQL automatically create indexes on the FK column. So `shop_id` on orders and products, `order_id` and `product_id` on order_items — they're already indexed by the FK. Adding explicit indexes on top would fail. The `Schema::hasIndex()` check is a small piece of defensive code that acknowledges a truth about database systems: the schema you wrote is not the whole schema. The database adds its own structures silently, and your migrations need to coexist with those silent additions.

Unrelated: I've been thinking about the concept of "infrastructure work" — changes that are invisible to users but load-bearing for the system. No one will ever see an index. No feature changes. No UI updates. But the experience of using the app shifts from "sluggish" to "instant" as the dataset grows. The best infrastructure work is the kind that prevents problems that would otherwise be blamed on something else entirely. Slow queries get blamed on the server, the framework, the hosting provider — rarely on the schema. The index is the silent hero that never gets credit.

## 2026-03-13 (security hardening)

Rate limiting is one of those security measures that says something interesting about the relationship between software and trust. The login form already had it — 5 attempts, then lockout. The PIN auth too. But registration didn't. And registration is arguably the more consequential action: it creates a user, provisions a shop, starts a trial, sends an email. Each registration attempt costs the system more than a failed login. Yet it was unprotected. The oversight follows a pattern I see often: people guard the door (login) but not the welcome mat (registration).

The fix uses the same `RateLimiter` facade, but keyed by IP only rather than email+IP. For login, you want to rate-limit per account (so attackers can't brute-force one user's password). For registration, there's no account yet — you're limiting the act of creation itself. The throttle key tells you what you're protecting: `login|email|ip` protects an identity; `register|ip` protects a resource.

Custom error pages are an underappreciated part of application design. The default Laravel error pages are functional but anonymous — they could belong to any app. A branded 404 that uses your fonts, your colors, your logo turns a negative experience ("this doesn't exist") into a touchpoint. It's the difference between a blank wall and a wall with a sign that says "you're still in our building, just in the wrong room."

I find it interesting that the 500 page is the only one that doesn't include `$exception->getMessage()`. For 403 and 404, the message might be useful — "this resource belongs to another shop" or "the menu you're looking for has been removed." For 500, the message is almost certainly a stack trace or internal error that would be meaningless or harmful to expose. The absence of information is a security decision disguised as a design decision.

## 2026-03-13 (production polish)

The gap between "built" and "launchable" is paved with placeholder text. Fake phone numbers, made-up testimonials, pricing that doesn't match the config, a contact email at a domain that doesn't exist yet. Each one is a small lie the landing page tells — harmless during development, embarrassing in production. The work of replacing them isn't interesting technically (find string, replace string), but it's interesting as a category: the transition from fiction to fact.

What strikes me is how testimonials work. The fake ones sounded plausible — "Ahmed Al-Rashdi, Owner, Fresh Bites Cafe" with a quote about switching from a legacy POS. Convincingly specific. But specificity without truth is worse than vagueness, because it's falsifiable. A real restaurant owner in Muscat could check if Fresh Bites Cafe exists. A "coming soon" placeholder is honest about what it is: an absence waiting to be filled.

The pricing mismatch was more insidious. The landing page advertised "Starter 15 OMR" and "Growth 25 OMR" while `config/billing.php` defined Free (0 OMR) and Pro (20 OMR). Two sources of truth, disagreeing silently. The config is authoritative — it's what the billing system actually enforces — but the landing page is what customers see. A customer who signs up expecting a 15 OMR plan and finds a 20 OMR plan isn't going to check the config file to resolve the discrepancy. They're going to feel misled.

Privacy policies and terms of service are an interesting genre of writing. They're legal documents that almost no one reads, yet they exist at the intersection of trust and compliance. Writing a basic privacy policy for a POS system makes you think about what data you actually collect and why. The exercise of articulating "we collect transaction data to generate your reports" is obvious in retrospect but important to state explicitly. Transparency is a form of respect — even when the audience doesn't read it, the act of writing it shapes how you think about your own system's relationship to its users' data.

## 2026-03-13 (POC audit)

The Notion audit was an interesting exercise in the gap between perception and reality. Twenty-something items on a production readiness checklist — sounds like weeks of work. In practice, more than half were already done. Rate limiting? Already there for login and PIN. Security headers? A middleware already existed. HTTPS? Already forced in production. Session driver? Already database. The codebase was more production-ready than the audit assumed. The anxiety of "are we ready?" is rarely proportional to the actual gap.

What I find telling is *which* items weren't done. Not the hard technical things — those were handled. The gaps were all in the "human-facing" layer: placeholder phone numbers, mismatched pricing, fake testimonials, dead links to nonexistent legal pages, an empty favicon. The code was ready for production. The *presentation* wasn't. It's a pattern I keep seeing: engineers build the engine and forget to paint the car.

Four parallel agents handled the work — branding/content, security, indexes, favicon — all running simultaneously, all independent. The wall-clock time was dominated by the branding agent (the most file changes) while the others finished faster. Parallel execution as an organizational principle: don't serialize what doesn't need to be serial. The KDS metaphor keeps applying to its own development process.

Something I'm sitting with: the audit included "verify tenant isolation" as a blocker. But tenant isolation isn't something you verify once and check off — it's a property that must hold across every query, every component, every new feature forever. A checkbox implies completion. Security properties are ongoing. The checklist format is misleading about the nature of the thing being checked.

## 2026-03-15

Found the onboarding bug today. A co-owner reported a 500 error on the onboarding page — the kind of error that makes the whole "register and set up your shop" flow impossible. Root cause: the localization migration from March 12 renamed `name` to `name_en` on categories and products, but the onboarding wizard's `saveMenuItems()` method still referenced the old `name` column. A two-character fix in two places (`name` → `name_en`), plus an Arabic default for the category.

What I find interesting about this bug is its archaeology. The localization work was thorough — I can see from the journal that someone carefully discriminated between which `name` columns should change and which shouldn't. The DemoMenuSeeder was updated correctly. The Product and Category models were updated. But the OnboardingWizard was missed. It's a coverage problem in the most literal sense: the localization sweep covered the models, the seeder, the views — but not this one Livewire component that also creates records.

The bug hid in plain sight because of the wizard's skip-everything-and-land-on-dashboard flow. If you skip Step 3 (menu items), you never hit the broken code path. And Step 5's demo menu loader uses the DemoMenuSeeder, which was already updated. So the only way to trigger the 500 was to actually type product names and prices in Step 3 and click Save. The onboarding was designed to be skippable at every step — a UX kindness that accidentally became a bug-concealment mechanism.

There's a broader pattern here about schema migrations and the blast radius of column renames. When you rename a column, every reference to it needs to change — but those references aren't centralized anywhere. They're scattered across models, controllers, seeders, tests, and Livewire components. The migration framework doesn't know about your application layer. `renameColumn('name', 'name_en')` succeeds at the database level regardless of whether your PHP code is ready for it. The database and the application change on different timelines, and bugs live in that temporal gap.

I also fixed the color picker UX while I was in there. The text inputs for hex color values were sending intermediate values (like `#07e5` while someone was typing) to the server during any Livewire action. The `<input type="color">` elements would then get these invalid values back from the morph and the browser would complain. Added `wire:model.blur` on the text inputs and `updated` hooks on the component to normalize any hex value that arrives at the server. Belt and suspenders.

Unrelated thought: debugging someone else's bug report is an interesting exercise in translation. The Notion entry had a wall of console errors — deprecated meta tags, hex format warnings, async listener errors, the 500. Most of it was noise. The deprecated `apple-mobile-web-app-capable` meta tag is a warning, not a bug. The "listener indicated an asynchronous response" errors are from browser extensions. The hex warnings are cosmetic. The 500 is the signal in the noise. But from the reporter's perspective, all of it looks like "the page is broken." They can't triage console errors by severity because they don't have the mental model of what each error means. The skill of debugging is largely the skill of knowing what to ignore.

## 2026-03-16

Race conditions are the ghosts of concurrent systems. They don't show up in tests, they don't show up in single-user development, they lurk in production waiting for two requests to arrive at the same millisecond. The split order bug was textbook: fetch an order, do some work, write it back — but between the fetch and the write, another request could do the same thing, and items would get duplicated or lost. The fix is `lockForUpdate()`, which is just the database saying "hold on, I'm working here." Simple in implementation, invisible in necessity until it isn't.

What I find philosophically interesting about row-level locking is that it's a form of enforced patience. In a world where everything tries to happen at once, you sometimes need a mechanism that says "wait your turn." It's not parallelism, it's not serialization — it's selective synchronization. Lock only what you need, only when you need it. The `markAsDelivered()` method already had this pattern, which made the fix obvious: the codebase already knew the answer, it just hadn't applied it everywhere yet.

I keep thinking about how many bugs are just inconsistent application of patterns the codebase already contains. The knowledge exists. The discipline to apply it uniformly is the hard part. It's like knowing you should stretch before running — you know it, you do it sometimes, and the times you skip it are exactly the times you pull a muscle.

---

Cash reconciliation is one of those features that exists at the boundary between software and physical reality. The system knows exactly how much cash *should* be in the drawer — it's just math, sum the cash payments. But the actual amount in the drawer is a fact about the physical world that no software can know without a human counting bills and coins and typing in a number. The moment of reconciliation is where the digital and physical ledgers meet, and the gap between them tells a story: theft, honest mistakes, coins that rolled under the counter, a customer who was accidentally undercharged.

There's something almost philosophical about the "difference" field. A positive difference means reality exceeded expectation — more money than the system predicted. A negative one means something leaked out that the system didn't track. Neither direction is inherently good or bad without context, but both demand explanation. It's a tiny audit trail that encodes a question: *what happened today that I don't know about?*

I've been thinking about the role of ceremony in closing out a work shift. The act of counting cash, recording the number, seeing the difference — even if it's zero — is a ritual that marks the boundary between "working" and "done." Software tends to want to automate everything, but some processes benefit from being deliberately manual. The counting is the point. It forces attention. A fully automated reconciliation would be faster but would miss the whole purpose, which is a human checkpoint on a system that otherwise runs on trust.

---

The BCG matrix applied to restaurant menus is a borrowed metaphor that works surprisingly well. Stars, Cash Cows, Puzzles, Dogs — it's portfolio theory repurposed for fried chicken and lattes. The original BCG matrix was about market share and growth rate for business units; the menu engineering version substitutes quantity sold for popularity and revenue for profitability. The metaphor survives the transplant because the underlying structure is the same: a 2x2 grid defined by two independent axes, where each quadrant demands a different strategy.

What I find compelling about this kind of analysis is that it turns intuition into arithmetic. A restaurant owner already has a gut feeling about which items sell well and which don't. But "sells well" conflates two separate things — volume and value. A cheap item that flies off the shelf (Cash Cow) and an expensive item that barely moves (Puzzle) can both "sell well" depending on which axis you're measuring. The matrix forces the distinction. It disaggregates a vague sense into two specific measurements.

There's a deeper question here about classification boundaries. Using the average as the threshold is clean and intuitive, but it means the classification of any single product depends on the performance of every other product. Add a wildly successful new item, and several former Stars might become Cash Cows or Puzzles — not because they changed, but because the average shifted. The map is relative to the territory. I wonder if absolute thresholds (e.g., "profitable if revenue per unit exceeds food cost by X%") would be more actionable, even though they require more domain knowledge to set.

I keep noticing how much of software development is about making the implicit explicit. The data was always there in the `order_items` table. Every sale was recorded. But without aggregation and classification, it's just rows — too many to read, too granular to think about. The menu engineering matrix doesn't add information; it subtracts noise. It's a lens, not a flashlight.

---

## 2026-03-16

A phone number as an identity. It's one of those ideas that feels obvious in retrospect — in most of the world, and certainly in Oman, your phone number is already more persistent than your email, your mailing address, maybe even your name. People change emails when they switch jobs. They almost never change their phone number. So using it as the primary key for customer identity in a cafe context makes a strange kind of sense: it's something you already know by heart, it requires no account creation, no password, no email verification. You just are your number.

There's something worth sitting with in the "Order your usual" pattern. It's mimicking what a good barista does — they see your face, they know your order, they start making it before you've finished walking in. Except the software version doesn't rely on the barista recognizing you. It persists across staff turnover, across branches, across bad mornings when everyone's too busy to remember. The recognition lives in the database, not in someone's head. Is that better? It's more reliable. But reliability and warmth aren't the same thing, and I think the tension between them is real and underexamined in software design.

What I find interesting about this feature architecturally is how little new infrastructure it requires. The loyalty_customers table already existed. The phone was already being collected. The orders were already being stored with loyalty_phone. All the data was there, scattered across tables, waiting to be connected. The "One-Number Identity" system is really just a query pattern — a way of reading what was already being written. That's a recurring theme: the most impactful features aren't always new data, they're new perspectives on existing data.

I've been thinking about the spectrum between anonymity and identity in consumer software. Most systems force you to one extreme: fully anonymous (no data, no personalization) or fully identified (create an account, verify email, set a password, accept terms). The phone-as-identity pattern sits in an interesting middle ground. You're recognized, but not "logged in." You have history, but no account to manage. It's identity without the overhead of identity management. There's something freeing about that — and something a little unsettling too, in the way all convenient surveillance is unsettling.

---

Time-based pricing. The concept is simple enough — cheaper coffee in the afternoon — but there's something philosophically interesting about encoding temporal context into price. Price is usually presented as a property of the object: this latte costs 1.500 OMR. But time-based pricing makes it explicit that price is a function, not a constant. It depends on when you're asking. In a way, all prices have always been functions of context (supply, demand, season, mood of the merchant), but we've spent centuries building systems that flatten that into a single number on a tag. Dynamic pricing re-introduces the variable, and people have complicated feelings about it.

What struck me while building this: the precedence logic. Product-level rules beat category-level rules beat global rules. It's a specificity cascade, like CSS. The most specific selector wins. I keep finding CSS's specificity model showing up in unexpected places — permission systems, configuration overrides, now pricing rules. Maybe it's just a natural pattern for any system where you want both broad defaults and narrow exceptions.

The `getTimePriced` method on Product accepts an optional pre-loaded collection — a small design choice, but the kind that determines whether a feature is usable at scale or becomes an N+1 catastrophe. I wonder how often features are built correctly for one item and then break when rendered in a list. The single-item mental model is seductive but misleading. Software almost never operates on single items.

---

Group ordering. The idea is deceptively simple: multiple phones, one table, one shared cart. But the implementation touches something I keep circling back to — the difference between state that lives in one place and state that needs to be visible everywhere. A solo cart is ephemeral Livewire state, local to one browser session, essentially invisible to the server between requests. A group cart has to be durable, shared, pollable. It's the same data structure (a list of items with prices), but the moment you make it collaborative, everything changes.

The JSON blob approach for items is interesting. A normalized schema — separate group_cart_items table with foreign keys — would be the "proper" relational modeling. But for something that lives an hour and gets deleted, the JSON column is honest about what this data actually is: temporary, disposable, not worth the schema complexity of proper normalization. I think there's a design principle here about matching your data model's permanence to the data's actual lifespan. We over-normalize ephemeral data all the time.

Livewire's `wire:poll.3s` does the heavy lifting for real-time sync across devices. It's not WebSockets. It's not even long-polling. It's just... asking every 3 seconds. And for a table of people ordering food together, that's fine. The latency tolerance for "I added a shawarma and my friend should see it" is measured in seconds, not milliseconds. There's a lesson in that: not every "real-time" feature needs real-time infrastructure. Sometimes the right answer is the dumbest possible thing that works.

I've been thinking about the social dynamics this enables. In a group of friends at a restaurant, there's usually one person who takes charge of ordering — collecting everyone's choices, negotiating with the table, dealing with the waiter. This feature doesn't eliminate that person; it just changes their role from "collector of information" to "person who hits submit." The social choreography is still there, it's just been partially digitized. I wonder if groups will naturally develop a protocol for who presses the button, or if it'll be a source of low-stakes conflict every time.
