{{-- LOST / FOUND / CLAIMED / RESOLVED badge, colors from the original app. Needs: $item --}}
@php($label = $item->statusLabel())
<span class="badge badge-{{ strtolower($label) }}">{{ $label }}</span>
