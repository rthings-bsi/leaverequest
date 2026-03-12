<?php

$compiledPath = env('VIEW_COMPILED_PATH');

if (!$compiledPath) {
    $storagePath = storage_path('framework/views');
    if (is_dir($storagePath) && is_writable($storagePath)) {
        $compiledPath = $storagePath;
    } else {
        $tmpPath = sys_get_temp_dir() . '/laravel_views';
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath, 0755, true);
        }
        $compiledPath = $tmpPath;
    }
}

return [

    'paths' => [
        resource_path('views'),
    ],

    'compiled' => $compiledPath,

];
