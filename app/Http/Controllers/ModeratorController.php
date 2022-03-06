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
    public function getDataset(Request $request){
        if (Gate::allows('applicant')) {
            $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/dataset');
            if ($response->getStatusCode() != 200)
                return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');
            $datasets = collect($response->object())->whereIn('status', ['CREATED', 'PENDING']);
            foreach($datasets as $dataset){
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
    }

    public function getStartup(Request $request){
        if (Gate::allows('applicant')) {
            $response = Http::get(config('api.API_FEED_CONTENT') . '/startup');
            if ($response->getStatusCode() != 200)
                return Responder::error($response, 'API_FEED_CONTENT:startup:get');
            $startups = collect($response->object())->whereIn('status', ['Created', 'Pending']);
            foreach($startups as $startup){
                $response = Http::get(config('api.API_FEED_CONTENT') . '/mediarelationship', [
                    'mediableType' => 'Startup',
                    'mediableId' => $startup->id,
                ]);
                if ($response->getStatusCode() != 200)
                    return Responder::error($response, 'API_FEED_CONTENT:mediarelationship:get');
                $startup->media = Media::getMediaByIds(collect($response->object())->pluck('mediaId'));
            }
            return response()->json($startups, 200);
        }
    }

    public function dataset(Request $request){
        if (Gate::allows('applicant')) {
            $data = $request->only('dataset_id', 'status', 'comment');
            $validator = Validator::make($data, [
                'dataset_id' => 'required|integer',
                'status' => 'in:PENDING,VALIDATED,DENIED',
                'comment' => 'string'
            ]);
            if ($validator->fails())
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
            $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/dataset/' . $request->dataset_id);
            if ($response->getStatusCode() == 404)
                return response()->json(['message' => 'Dataset not found'], 404);
            else if (!$response->successful())
                return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');

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

    public function startup(Request $request){
        if (Gate::allows('applicant')) {
            $data = $request->only('startup_id', 'status', 'comment');
            $validator = Validator::make($data, [
                'startup_id' => 'required|integer',
                'status' => 'in:Published',
                'comment' => 'string'
            ]);
            if ($validator->fails())
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
            $response = Http::get(config('api.API_FEED_CONTENT') . '/startup/' . $request->startup_id);
            if ($response->getStatusCode() == 404)
                return response()->json(['message' => 'Startup not found'], 404);
            else if (!$response->successful())
                return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');

            $response = Http::put(config('api.API_FEED_CONTENT') . '/startup/' . $request->startup_id, [
                'status' => $request->status,
                'comment' => $request->comment,
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:update');

            return response()->json(['message' => 'Successful',], 200);
        }
    }
}
