<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\MediaLinker;
use App\Helpers\Responder;

class StartupController extends Controller
{
    public function create(Request $request)
    {
        if (Gate::allows('creator')) {
            $data = $request->only('files', 'name', 'ending_at', 'funds_goal', 'description');
            $validator = Validator::make($data, [
                'files' => 'required',
                'files.*' => 'file|image|max:5120',
                'name' => 'required|string|min:10',
                'description' => 'required|string|min:10',
                'funds_goal' => 'required|integer|min:500|max:10000000',
                'ending_at' => 'required|date|after:today'
            ]);
            if ($validator->fails())
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

            $response = Http::get(config('api.API_FEED_CONTENT') . '/startup', [
                'userId' => $request->user(),
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_FEED_CONTENT:startup:get');

            $startups = collect($response->object());
            if ($startups->pluck('status')->contains('Created'))
                return response()->json(['message' => 'Already have not moderated startup'], 409);

            $response = Http::post(config('api.API_FEED_CONTENT') . '/startup', [
                'userId' => $request->user(),
                'name' => $request->name,
                'description' => $request->description,
                'endingAt' => $request->ending_at,
                'fundsGoal' => $request->funds_goal,
                'status' => 'CREATED'
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_FEED_CONTENT:startup:create');

            $startup = $response->object();

            $media = new MediaLinker($startup->id, 'Startup');
            $files = $request->file('files');
            $response = $media->attach($request->user(), $files, true);
            if ($response->getStatusCode() != 200)
                Http::delete(config('api.API_FEED_CONTENT') . '/startup/' . $startup->id);
            return $response;
        }

        return response()->json([
            'message' => 'Forbidden',
        ], 403);
    }

    public function get(Request $request)
    {
        if (Gate::allows('creator')) {
            $response = Http::get(config('api.API_FEED_CONTENT') . '/startup', [
                'userId' => $request->user(),
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_FEED_CONTENT:startup:get');
            return response()->json($response->object(), 200);
        }

        return response()->json([
            'message' => 'Forbidden',
        ], 403);
    }
}
