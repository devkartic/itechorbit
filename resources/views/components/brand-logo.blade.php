@props(['dark' => false, 'width' => 200])
<img src="{{ asset('brand/itechorbit-logo.svg') }}"
     alt="iTechOrbit — Engineering with AI" width="{{ (int) $width }}" height="{{ (int) round($width * 84 / 300) }}"
     style="display: inline-block; max-width: 100%; height: auto; vertical-align: middle;{{ $dark ? ' filter: invert(1) hue-rotate(180deg);' : '' }}">
