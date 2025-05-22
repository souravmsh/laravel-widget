<?php

namespace Souravmsh\LaravelWidget\View\Components;

use Illuminate\View\Component;

class FontHunterWidget extends Component
{
    public string $title;
    public string $description; 

    /**
     * Create a new component instance.
     *
     * @param string|null $title
     * @param string|null $description
     */
    public function __construct(
        ?string $title = null,
        ?string $description = null
    ) {
        $this->title       = $title ?? 'Laravel Widget :: Font Hunter ';
        $this->description = $description ?? 'Font Hunter is a tool to help you find the fonts used on a website.';
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('laravel-widget::font-hunter.index', [
            'title'       => $this->title,
            'description' => $this->description
        ]);
    }
}