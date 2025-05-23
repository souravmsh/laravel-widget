<?php

return [

    'middleware' => ['web'], // Middleware for the route
    'url_prefix' => 'laravel-widget', // URL prefix for the route

    // font hunter 
    "font_hunter" => [
        'dir'        => 'laravel-widget/font-hunter', // Base directory for storing files
        'fonts_dir'  => 'fonts', // Subdirectory for font files
        'css_dir'    => 'css', // Subdirectory for CSS files
        'file_name'  => 'fonts.css', // Name of the CSS file
    ],

    // avatar
    'avatar' => [
        'src'     => null,
        'alt'     => 'Avatar',
        'width'   => 48,
        'height'  => 48,
        'id'      => null,
        'classes' => null,
        'style'   => null,
        'attributes' => null,
    ],
    
];
