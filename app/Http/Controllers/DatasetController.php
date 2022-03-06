<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\MediaLinker;
use App\Helpers\Media;
use App\Helpers\Responder;

class DatasetController extends Controller
{
    public function request(Request $request)
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
            'user_id' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');

        $datasets = collect($response->object());
        if(count($datasets->where('status', 'VALIDATED')->where('type', $request->type)))
            return response()->json(['message' => 'Already have this role'], 409);
        if ($datasets->pluck('status')->contains('CREATED'))
            return response()->json(['message' => 'Already exists'], 409);

        $response = Http::post(config('api.API_BUSINESS_CONTENT') . '/dataset', [
            'user_id' => $request->user(),
            'type' => $request->type,
            'content' => $request->content,
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:create');

        $dataset = $response->object();

        $media = new MediaLinker($dataset->id, 'Dataset');
        $files = $request->file('files');
        $response = $media->attach($request->user(), $files, false);
        if (!$response->successful())
            Http::delete(config('api.API_BUSINESS_CONTENT') . '/dataset/' . $dataset->id);
        return $response;
    }

    public function get(Request $request)
    {
        $response = Http::get(config('api.API_BUSINESS_CONTENT') . '/dataset', [
            'user_id' => $request->user(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');
        $datasets = collect($response->object());
        foreach($datasets as $dataset){
            $response = Http::get(config('api.API_FEED_CONTENT') . '/mediarelationship', [
                'mediableType' => 'Dataset',
                'mediableId' => $dataset->id,
            ]);
            if (!$response->successful())
                return Responder::error($response, 'API_FEED_CONTENT:mediarelationship:get');
            $dataset->media = Media::getMediaByIds(collect($response->object())->pluck('mediaId'));
        }
        return response()->json($datasets, 200);
    }
}
