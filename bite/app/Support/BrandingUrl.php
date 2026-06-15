<?php

namespace App\Support;

class BrandingUrl
{
    /**
     * Sanitize any shop-supplied URL before it is rendered into a URL-bearing
     * attribute — an img/script `src` or an anchor `href`. Returns the value
     * only when it is safe to render:
     *   - an absolute http(s) URL, or
     *   - a relative path / root-relative path (no scheme).
     *
     * Returns null for anything carrying a dangerous scheme (javascript:,
     * data:, vbscript:, etc.) or otherwise unparseable input. Blade's {{ }}
     * escapes HTML entities but does NOT neutralize scheme-based URI payloads
     * in src/href attributes, so this is the trust boundary for those values.
     */
    public static function safe(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        // Relative or root-relative path with no scheme — safe.
        if (! preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $trimmed)) {
            return $trimmed;
        }

        // Has a scheme — only http/https are allowed.
        $scheme = strtolower((string) parse_url($trimmed, PHP_URL_SCHEME));
        if (in_array($scheme, ['http', 'https'], true)) {
            return $trimmed;
        }

        return null;
    }
}
