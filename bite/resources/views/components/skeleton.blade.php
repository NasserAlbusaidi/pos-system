@props(['width' => null, 'height' => null])

@php
    $styles = collect([
        $width ? "width: {$width}" : null,
        $height ? "height: {$height}" : null,
    ])->filter()->implode('; ');
@endphp

<div
    {{ $attributes->class(['skeleton']) }}
    @if($styles) style="{{ $styles }}" @endif
    aria-hidden="true"
>&nbsp;</div>
