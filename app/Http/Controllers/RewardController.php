<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Responder;
use App\Helpers\Media;

class RewardController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->only('startup_id', 'name', 'donation_minimum', 'description', 'file');
        $validator = Validator::make($data, [
            'file' => 'file|image|max:5120',
            'name' => 'required|string|min:5|max:200',
            'description' => 'required|string|min:5|max:5000',
            'startup_id' => 'required|integer',
            'donation_minimum' => 'required|numeric|min:0.1',
        ]);
        if ($validator->fails())
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

        if (!Gate::allows('startup', $request->startup_id))
            return response()->json(['message' => 'Forbidden'], 403);

        $file = $request->file('file');
        $media = Media::uploadMedia($file, $request->user(), true);
        if (!$media)
            return Responder::error(null, 'API_MEDIA:media:upload');

        $response = Http::post(config('api.API_FEED_CONTENT') . '/reward', [
            'startupId' => $request->startup_id,
            'name' => $request->name,
            'description' => $request->description,
            'donationMinimum' => $request->donation_minimum,
            'mediaId' => $media->id,
        ]);
        if (!$response->successful()) {
            Http::delete(config('api.API_MEDIA') . '/media/' . $media->id);
            return Responder::error($response, 'API_FEED_CONTENT:reward:create');
        }

        return response()->json(['message' => 'Successful'], 201);
    }

    public function get(Request $request)
    {
        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/reward_user', [
            'user_id' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:reward_user:get');
        return response()->json($response->object(), 200);
    }

    public function request(Request $request)
    {
        $data = $request->only('reward_id', 'destination');
        $validator = Validator::make($data, [
            'reward_id' => 'required|integer',
            'destination' => 'required|string|min:10|max:5000',
        ]);
        if ($validator->fails())
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

        $response = Http::get(config('api.API_FEED_CONTENT') . '/reward/' . $request->reward_id);
        if ($response->getStatusCode() == 404)
            return response()->json(['message' => 'Reward not found'], 404);
        else if (!$response->successful())
            return Responder::error($response, 'API_FEED_CONTENT:reward:get');

        $reward = $response->object();

        $response = Http::get(config('api.API_PAYMENT') . '/payment/sum', [
            'user_id' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_PAYMENT:payment_sum:get');

        if ((float)$reward->donationMinimum > (float)$response->json())
            return response()->json(['message' => 'Not enough donation'], 402);

        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/reward_user', [
            'user_id' => $request->user(),
            'reward_id' => $reward->id,
        ]);

        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:reward_user:get');
        if (count($response->object()))
            return response()->json(['message' => 'Already requested this type of reward'], 409);

        $response = Http::post(config('api.API_BUSINESS_CONTENT') . '/reward_user', [
            'user_id' => $request->user(),
            'reward_id' => $request->reward_id,
            'destination' => $request->destination,
            'status' => 'CREATED',
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:reward_user:create');
        return response()->json($response->object(), 200);
    }
}
