<?php

namespace Souravmsh\LaravelWidget\Facades;

use Illuminate\Support\Facades\Facade;

class Avatar extends Facade
{
    public static function getFacadeAccessor()
    {
        return \Souravmsh\LaravelWidget\Services\AvatarService::class;
    }
}
