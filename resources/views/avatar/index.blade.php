<img src="{{ $src ?: $fallbackSrc }}"
     alt="{{ $alt }}"
     @if($id) id="{{ $id }}" @endif
     @if($classes) class="{{ $classes }}" @endif
     @if($style) style="{{ $style }}" @endif
     @if($width) width="{{ $width }}" @endif
     @if($height) height="{{ $height }}" @endif
     {{ $attributes }}
     onerror="this.src='{{ $fallbackSrc }}';">