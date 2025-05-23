<?php

namespace Souravmsh\LaravelWidget\View\Components;

use Illuminate\View\Component;
use Souravmsh\LaravelWidget\Services\AvatarService;

class AvatarWidget extends Component
{
    public ?string $src;
    public ?string $alt;
    public ?string $id;
    public ?string $classes;
    public ?string $style;
    public $attributes; // No type declaration to match parent class
    public ?int $width;
    public ?int $height;

    /**
     * Create a new component instance.
     *
     * @param string|null $src
     * @param string|null $generatedSrc
     * @param string|null $alt
     * @param string|null $id
     * @param string|null $classes
     * @param string|null $style
     * @param string|null $attributes
     * @param string|null $width
     * @param string|null $height
     */
    public function __construct(
        ?string $src = null,
        ?string $generatedSrc = null,
        ?string $alt = null,
        ?string $id = null,
        ?string $classes = null,
        ?string $style = null,
        ?string $attributes = null,
        ?string $width = null,
        ?string $height = null
    ) {
        $this->src = $src ?? $generatedSrc;
        $this->alt = $alt ?? ($src ? AvatarService::getTextFromSrc($src) : config('laravel_widget.avatar.alt', 'Avatar'));
        $this->id = $id;
        $this->classes = $classes;
        $this->style = $style;
        // Parse attributes string into key-value pairs
        if (is_string($attributes)) {
            $attributesArray = [];
            // Match attributes like key='value' or key="value"
            preg_match_all('/(\S+)=[\'"]([^\'"]*)[\'"]/i', $attributes, $matches);
            for ($i = 0; $i < count($matches[1]); $i++) {
                $attributesArray[$matches[1][$i]] = $matches[2][$i];
            }
            $this->attributes = $this->attributes->merge($attributesArray);
        }
        // Only set width and height if provided; defaults applied in fallbackSrc
        $this->width = $width !== null ? (int) $width : null;
        $this->height = $height !== null && !str_contains($height, '%') ? (int) $height : null;
        // Handle percentage height by moving to style
        $this->style = $height && str_contains($height, '%') ? ($style ? $style . ';' : '') . "height: $height;" : $style;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        // Use config defaults for fallback avatar dimensions if not provided
        $fallbackWidth = $this->width ?? config('laravel_widget.avatar.width', 48);
        $fallbackHeight = $this->height ?? config('laravel_widget.avatar.height', 48);

        return view('laravel-widget::avatar.index', [
            'src' => $this->src,
            'alt' => $this->alt,
            'id' => $this->id ?? config('laravel_widget.avatar.id'),
            'classes' => $this->classes ?? config('laravel_widget.avatar.classes'),
            'style' => $this->style ?? config('laravel_widget.avatar.style'),
            'attributes' => $this->attributes,
            'width' => $this->width,
            'height' => $this->height,
            'fallbackSrc' => AvatarService::create($this->alt, $fallbackWidth, $fallbackHeight),
            'fallbackWidth' => $fallbackWidth,
            'fallbackHeight' => $fallbackHeight,
        ]);
    }
}
