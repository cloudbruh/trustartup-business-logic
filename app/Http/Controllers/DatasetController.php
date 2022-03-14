<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use App\Helpers\MediaLinker;
use App\Helpers\Media;
use App\Helpers\Responder;

class DatasetController extends Controller
{
    public function requestRole(Request $request)
    {
        $data = $request->only('type', 'files', 'content');
        $validator = Validator::make($data, [
            'files' => 'required',
            'files.*' => 'file|image|max:5120',
            'type' => 'required|in:APPLICANT,CREATOR',
            'content' => 'required|string|min:10|max:5000',
        ]);
        if ($validator->fails())
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/dataset', [
            'moderatable_id' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');

        $datasets = collect($response->object());
        if (count($datasets->where('status', 'GRANTED')->where('moderatable_type', 'ROLE_' . $request->type)))
            return response()->json(['message' => 'Already have this role'], 409);
        if (count($datasets->where('status', 'CREATED')->where('moderatable_type', 'ROLE_' . $request->type)))
            return response()->json(['message' => 'Already requested this role'], 409);

        $response = Http::post(config('api.API_BUSINESS_CONTENT') . '/dataset', [
            'moderatable_id' => $request->user(),
            'moderatable_type' => 'ROLE_' . $request->type,
            'content' => $request->content,
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:create');

        $dataset = $response->object();

        $media = new MediaLinker($dataset->id, 'Dataset');
        $files = $request->file('files');
        $response = $media->attach($request->user(), $files, false);
        if ($response->getStatusCode() != 200)
            Http::delete(config('api.API_BUSINESS_CONTENT') . '/dataset/' . $dataset->id);
        return $response;
    }

    public function requestStartup(Request $request)
    {
        $data = $request->only('startup_id', 'files', 'content');
        $validator = Validator::make($data, [
            'files.*' => 'file|image|max:5120',
            'startup_id' => 'required|integer',
            'content' => 'required|string|min:5|max:5000',
        ]);
        if ($validator->fails())
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

        if (!Gate::allows('startup', $request->startup_id))
            return response()->json(['message' => 'Forbidden'], 403);

        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/dataset', [
            'moderatable_id' => $request->startup_id,
            'moderatable_type' => 'STARTUP',
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');

        $datasets = collect($response->object());
        if (count($datasets->whereIn('status', ['CREATED', 'PENDING'])))
            return response()->json(['message' => 'Already created request'], 409);
        if (count($datasets->where('status', 'GRANTED')))
            return response()->json(['message' => 'Already granted'], 409);

        $response = Http::post(config('api.API_BUSINESS_CONTENT') . '/dataset', [
            'moderatable_id' => $request->startup_id,
            'moderatable_type' => 'STARTUP',
            'content' => $request->content,
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:create');

        $dataset = $response->object();
        $files = $request->file('files');
        if ($files) {
            $media = new MediaLinker($dataset->id, 'Dataset');
            $response = $media->attach($request->user(), $files, false);
            if ($response->getStatusCode() != 200)
                Http::delete(config('api.API_BUSINESS_CONTENT') . '/dataset/' . $dataset->id);
            return $response;
        } else
            return response()->json(['message' => 'Successful',], 201);
    }

    public function get(Request $request)
    {
        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/dataset', [
            'user_id' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');
        $datasets = collect($response->object());
        foreach ($datasets as $dataset) {
            $response = Http::get(config('api.API_FEED_CONTENT') . '/mediarelationship', [
                'mediableType' => 'Dataset',
                'mediableId' => $dataset->id,
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_FEED_CONTENT:mediarelationship:get');
            $media = collect($response->object())->pluck('mediaId');
            foreach ($media as $i) {
                $response = Http::get(config('api.API_MEDIA') . '/media/' . $i);
                if (!$response->successful())
                    return Responder::error($response, 'API_MEDIA:media:get');
                $dataset->media[] = $response->object()->link;
            }
        }
        return response()->json($datasets, 200);
    }
}
