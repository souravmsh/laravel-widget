<?php

namespace Souravmsh\LaravelWidget\Facades;

use Illuminate\Support\Facades\Facade;

class FontHunter extends Facade
{
    public static function getFacadeAccessor()
    {
        return \Souravmsh\LaravelWidget\Services\FontHunterService::class;
    }
}
