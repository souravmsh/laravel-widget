<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Souravmsh\LaravelWidget\Services\FontHunterService;

Route::group([
    'middleware' => config('laravel_widget.middleware', ['web']),
    'prefix'     => config('laravel_widget.url_prefix', 'laravel-widget'),
    'as'         => 'laravel-widget.',
], function () {
    
    Route::group([
        'prefix' => 'font-hunter',
        'as'     => 'font-hunter.',
    ], function () {
        
        Route::post('generate', function () {

            if (isset(request()['url']) && !preg_match('/^https?:\/\//', request()['url'])) {
                request()['url'] = 'http://' . request()['url'];
            }
            $validator = Validator::make(request()->all(), [
                'url' => 'required|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }

            try { 
                $url = request()->input('url');
                $response = app(FontHunterService::class)->generate($url);
                return response()->json($response, 200);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'An error occurred: ' . $e->getMessage()
                ], 500);
            }
        })->name('generate');

        Route::get('download', function () {
            return app(FontHunterService::class)->download(request()->get('path'));
        })->name('download');
        
    });

});
