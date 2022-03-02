<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Responder;

class RewardController extends Controller
{
    public function get(Request $request)
    {
        $response = Http::get(config('api.API_FEED_CONTENT') . '/startup', [
            'userId' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_FEED_CONTENT:startup:get');
        return response()->json($response->object(), 200);

        return response()->json([
            'message' => 'Forbidden',
        ], 403);
    }
}
