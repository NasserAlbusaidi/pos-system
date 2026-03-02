<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'Bite POS' }}</title>
        
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if(isset($shop))
            @php
                $branding = $shop->branding ?? [];
                $paperHex = $branding['paper'] ?? '#FDFCF8';
                $inkHex = $branding['ink'] ?? '#1A1918';
                $cremaHex = $branding['accent'] ?? '#CC5500';

                $toRgb = function ($hex) {
                    $hex = ltrim((string) $hex, '#');
                    if (strlen($hex) === 3) {
                        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                    }
                    if (strlen($hex) !== 6) {
                        return '0 0 0';
                    }
                    [$r, $g, $b] = sscanf($hex, '%02x%02x%02x');
                    return "{$r} {$g} {$b}";
                };
            @endphp
            <style>
                :root {
                    --paper: {{ $toRgb($paperHex) }};
                    --ink: {{ $toRgb($inkHex) }};
                    --crema: {{ $toRgb($cremaHex) }};
                }
            </style>
        @endif
    </head>
    <body class="bg-paper text-ink font-sans antialiased">
        {{ $slot }}
    </body>
</html>
