<?php

return [

    'paths' => [
        resource_path('views'),
    ],

    /*
    | Paksa Laravel menggunakan /tmp di Vercel,
    | tapi tetap gunakan storage standar di lokal.
    */
    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),

];