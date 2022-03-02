<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Responder;

class ReviewController extends Controller
{
    public function create(Request $request)
    {
        if ($request->direction !== 'UTOS' && $request->direction !== 'STOU')
            return response()->json(['message' => 'Direction needed'], 400);
        if ($request->direction === 'UTOS') {
            $data = $request->only('startup_id', 'message', 'type');
            $validator = Validator::make($data, [
                'type' => 'required|in:POSITIVE,NEGATIVE',
                'message' => 'required|string|min:2|max:5000',
                'startup_id' => 'required|integer',
            ]);
            if ($validator->fails())
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
            $user_id = $request->user();
        } else if ($request->direction === 'STOU') {
            $data = $request->only('user_id', 'startup_id', 'message', 'type');
            $validator = Validator::make($data, [
                'type' => 'required|in:POSITIVE,NEGATIVE',
                'message' => 'required|string|min:5|max:5000',
                'user_id' => 'required|integer',
                'startup_id' => 'required|integer',
            ]);
            if ($validator->fails())
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
            if (!Gate::allows('startup', $request->startup_id))
                return response()->json(['message' => 'Forbidden'], 403);
            $user_id = $request->user_id;
        }

        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/review', [
            'user_id' => $user_id,
            'startup_id' => $request->startup_id,
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:review:get');
        if (count($response->object()))
            return response()->json(['message' => 'Already created review'], 409);

        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/application', [
            'user_id' => $user_id,
            'startup_id' => $request->startup_id,
            'status' => 'FIRED',
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:application:get');
        if (!count($response->object()))
            return response()->json(['message' => 'There is no application with FIRED status'], 403);

        $response = Http::post(config('api.API_BUSINESS_CONTENT') . '/review', [
            'user_id' => $user_id,
            'startup_id' => $request->startup_id,
            'message' => $request->message,
            'direction' => $request->direction,
            'type' => $request->type,
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:review:create');

        return response()->json(['message' => 'Successful',], 201);
    }

    public function get(Request $request)
    {
        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/review', [
            'user_id' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:review:get');
        return response()->json($response->object(), 200);
    }
}
