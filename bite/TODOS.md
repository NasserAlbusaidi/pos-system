# TODOS

## Cloud SQL: Enable backups and PITR after upgrading from free trial
- **What:** Enable automated daily backups with 7-day retention and point-in-time recovery (PITR) via binary logging on the `bite` Cloud SQL instance
- **Why:** Free trial instances don't support backups or PITR. Once upgraded to a paid tier, run: `gcloud sql instances patch bite --project=ascent-web-260224-119 --backup-start-time=02:00 --retained-backups-count=7 --enable-bin-log --retained-transaction-log-days=7`
- **Blocked by:** Cloud SQL free trial tier limitation
- **Requirement:** SEC-04

## Branding: Defensive hex-to-RGB fallback
- **What:** Add fallback to default colors when `$toRgb()` in `app.blade.php` encounters malformed hex values
- **Why:** Malformed hex (e.g., "blue" instead of "#0000FF") silently produces `0 0 0` (black), making the page unreadable (black-on-black). The color picker in onboarding prevents this in practice, but direct DB edits or API imports could trigger it.
- **Pros:** 3-line defensive check prevents an unreadable page
- **Cons:** Very low probability in normal usage
- **Context:** `app.blade.php` lines 25-35 contain the `$toRgb` closure. Currently returns `'0 0 0'` when hex length !== 6. Should fall back to the default values (`#FDFCF8`, `#1A1918`, `#CC5500`) instead.
- **Depends on / blocked by:** None

## Images: Optimization pipeline for guest menu
- **What:** Resize uploaded product images to a reasonable max width (e.g., 800px), convert to WebP, and serve responsive `srcset` for different screen sizes
- **Why:** A 33-item menu with unoptimized JPEG/PNG files could require 6-15MB of downloads on the guest menu. On mobile data in Oman, this is slow.
- **Pros:** Reduces guest menu payload to ~1-2MB, faster page load on mobile, better UX
- **Cons:** Requires adding Intervention Image or similar library, adds complexity to upload pipeline
- **Context:** Products store images via `ProductManager.php` to `products/` on public disk. Images are displayed at `h-40` (160px) on mobile, ~320px on tablet. Serving originals at 1000-3000px is wasteful. Consider processing on upload (eager) rather than on-the-fly (lazy) to keep serving simple.
- **Depends on / blocked by:** None. Can be added independently.

## Sentry: Set real SENTRY_LARAVEL_DSN on Cloud Run
- **What:** Replace the dummy `SENTRY_LARAVEL_DSN=https://dummy@sentry.io/0` on Cloud Run with a real Sentry DSN
- **Why:** Error tracking is currently disabled in production. The dummy value satisfies the AppServiceProvider validation but Sentry won't capture any errors.
- **How:** Create a Sentry project for Bite POS, get the DSN, then `gcloud run services update bite-pos-demo --update-env-vars="SENTRY_LARAVEL_DSN=<real-dsn>"`
- **Depends on / blocked by:** Sentry account/project setup

## Images: Storage cleanup on product photo update
- **What:** Delete old image file from disk when a product's photo is replaced
- **Why:** `ProductManager.php` line 118-121 stores the new image but never deletes the old one. Over time, orphaned files accumulate on disk.
- **Pros:** Prevents disk space leak, keeps storage clean
- **Cons:** Minimal — `Storage::delete()` is one line
- **Context:** In the update path: before storing the new image, check if `$product->image_url` exists and delete it via `Storage::disk('public')->delete($product->image_url)`. Also consider a cleanup command for existing orphans.
- **Depends on / blocked by:** None
