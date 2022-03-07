<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Responder;
use App\Helpers\Media;

class ModeratorController extends Controller
{
    public function get(Request $request)
    {
        if (Gate::allows('moderator')) {
            $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/dataset');
            if ($response->getStatusCode() != 200)
                return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');
            $datasets = collect($response->object());
            foreach ($datasets as $dataset) {
                if ($dataset->moderatable_type == 'STARTUP') {
                    $response = Http::get(config('api.API_FEED_CONTENT') . '/startup/' . $dataset->moderatable_id);
                    if ($response->getStatusCode() != 200)
                        return Responder::error($response, 'API_FEED_CONTENT:startup:get');
                    $dataset->moderatable_object = $response->object();
                } else if ($dataset->moderatable_type == 'ROLE_APPLICANT' || $dataset->moderatable_type == 'ROLE_CREATOR') {
                    $response = Http::get(config('api.API_USER') . '/user/' . $dataset->moderatable_id);
                    if ($response->getStatusCode() != 200)
                        return Responder::error($response, 'API_USER:user:get');
                    $dataset->moderatable_object = $response->object();
                }
                $response = Http::get(config('api.API_FEED_CONTENT') . '/mediarelationship', [
                    'mediableType' => 'Dataset',
                    'mediableId' => $dataset->id,
                ]);
                if ($response->getStatusCode() != 200)
                    return Responder::error($response, 'API_FEED_CONTENT:mediarelationship:get');
                $dataset->media = Media::getMediaByIds(collect($response->object())->pluck('mediaId'));
            }
            return response()->json($datasets, 200);
        }
        return response()->json([
            'message' => 'Forbidden',
        ], 403);
    }

    public function post(Request $request)
    {
        if (Gate::allows('moderator')) {
            $data = $request->only('dataset_id', 'status', 'comment');
            $validator = Validator::make($data, [
                'dataset_id' => 'required|integer',
                'status' => 'in:PENDING,GRANTED,PROHIBITED',
                'comment' => 'string'
            ]);
            if ($validator->fails())
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
            $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/dataset/' . $request->dataset_id);
            if ($response->getStatusCode() == 404)
                return response()->json(['message' => 'Dataset not found'], 404);
            else if (!$response->successful())
                return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');

            if ($request->status == 'GRANTED') {
                $dataset = $response->object();
                if ($dataset->moderatable_type == 'STARTUP') {
                    $response = Http::put(config('api.API_FEED_CONTENT') . '/startup/' . $dataset->moderatable_id, [
                        'status' => 'Published',
                    ]);
                    if (!$response->successful())
                        return Responder::error($response, 'API_FEED_CONTENT:startup:update');
                } else if ($dataset->moderatable_type == 'ROLE_CREATOR') {
                    $response = Http::post(config('api.API_USER') . '/user/' . $dataset->moderatable_id . '/roles', [
                        'type' => 'CREATOR',
                    ]);
                    if (!$response->successful())
                        return Responder::error($response, 'API_USER:role:create');
                } else if ($dataset->moderatable_type == 'ROLE_APPLICANT') {
                    $response = Http::post(config('api.API_USER') . '/user/' . $dataset->moderatable_id . '/roles', [
                        'type' => 'APPLICANT',
                    ]);
                    if (!$response->successful())
                        return Responder::error($response, 'API_USER:role:create');
                }
            }

            $response = Http::put(config('api.API_BUSINESS_CONTENT') . '/dataset', [
                'id' => $request->dataset_id,
                'status' => $request->status,
                'comment' => $request->comment,
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:update');

            return response()->json(['message' => 'Successful',], 200);
        }
    }
}
