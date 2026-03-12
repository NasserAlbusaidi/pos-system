<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Get the translated value of a field based on the current locale.
     * Falls back to English if the Arabic value is null/empty.
     */
    public function translated(string $field): ?string
    {
        $locale = App::getLocale();
        $localizedField = "{$field}_{$locale}";
        $englishField = "{$field}_en";

        $value = $this->{$localizedField} ?? null;

        if ($value !== null && $value !== '') {
            return $value;
        }

        return $this->{$englishField} ?? null;
    }
}
