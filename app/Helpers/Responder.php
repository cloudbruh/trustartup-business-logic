<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Responder
{
    public static function error($response, $message = '')
    {
        if (config('app.debug')) {
            return response()->json([
                'where' => $message,
                'response' => $response->json(),
            ], 500);
        }
        return response()->json(['message' => 'Something went wrong'], 500);
    }
}
