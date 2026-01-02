<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServerTimeController extends Controller
{
    /**
     * Return current server time in ISO format for client synchronization.
     */
    public function now(Request $request)
    {
        return response()->json(['now' => now()->toIso8601String()]);
    }
}
