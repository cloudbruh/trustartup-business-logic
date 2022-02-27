<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    public function create(Request $request)
    {
        if (Gate::allows('applicant')) {
            $data = $request->only('startup_id', 'message');
            $validator = Validator::make($data, [
                'startup_id' => 'required|integer',
                'message' => 'required|string|min:10'
            ]);
            if ($validator->fails())
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

            $response = Http::get(config('api.API_FEED_CONTENT') . '/startup/' . $request->startup_id);
            if (!$response->successful())
                return response()->json(['message' => 'API_FEED_CONTENT startup error code: ' . $response->getStatusCode()], 404);

            $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/application', [
                'user_id' => $request->user()['uid'],
                'startup_id' => $request->startup_id,
            ]);
            if (count($response->object()))
                return response()->json(['message' => 'Already created application for this startup'], 409);

            $response = Http::post(config('api.API_BUSINESS_CONTENT') . '/application', [
                'user_id' => $request->user()['uid'],
                'startup_id' => $request->startup_id,
                'message' => $request->message,
            ]);
            if (!$response->successful())
                return response()->json(['message' => 'API_BUSINESS_CONTENT error code: ' . $response->getStatusCode()], 500);


            return response()->json(['message' => 'Successful',], 201);
        }

        return response()->json([
            'message' => 'Forbidden',
        ], 403);
    }
}
