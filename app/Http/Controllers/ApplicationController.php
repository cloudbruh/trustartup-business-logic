<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Responder;

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
            if ($response->getStatusCode() == 404)
                return response()->json(['message' => 'Startup not found'], 404);
            else if (!$response->successful())
                return Responder::error($response, 'API_FEED_CONTENT:startup:get');
            else if ($response->object()->userId == $request->user())
                return response()->json(['message' => 'You cannot apply to your startup'], 403);

            $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/application', [
                'user_id' => $request->user(),
                'startup_id' => $request->startup_id,
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_BUSINESS_CONTENT:application:get');
            if (count($response->object()))
                return response()->json(['message' => 'Already created application for this startup'], 409);

            $response = Http::post(config('api.API_BUSINESS_CONTENT') . '/application', [
                'user_id' => $request->user(),
                'startup_id' => $request->startup_id,
                'message' => $request->message,
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_BUSINESS_CONTENT:application:create');

            return response()->json(['message' => 'Successful',], 201);
        }
        return response()->json([
            'message' => 'Forbidden',
        ], 403);
    }

    public function manage(Request $request)
    {
        $data = $request->only('status', 'application_id');
        $validator = Validator::make($data, [
            'application_id' => 'required|integer',
            'status' => 'in:APPLIED,FIRED',
        ]);
        if ($validator->fails())
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/application/' . $request->application_id);
        if ($response->getStatusCode() == 404)
            return response()->json(['message' => 'Application not found'], 404);
        else if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:application:get');

        $application = $response->object();
        if (!Gate::allows('startup', $application->startup_id))
            return response()->json(['message' => 'Forbidden'], 403);

        $response = Http::put(config('api.API_BUSINESS_CONTENT')  . '/application', [
            'id' => $request->application_id,
            'status' => $request->status,
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:application:update');
        return response()->json(['message' => 'Successful',], 200);
    }

    public function get(Request $request)
    {
        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/application', [
            'user_id' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:application:get');
        return response()->json($response->json(), 200);
    }
}
