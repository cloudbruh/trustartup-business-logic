<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\MediaLinker;
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
            'user_id' => $request->user(),
        ]);
        if ($response->getStatusCode() != 200)
            return Responder::error($response, 'API_BUSINESS_CONTENT:dataset:get');

        $datasets = collect($response->object());
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
        if ($response->getStatusCode() != 200)
            Http::delete(config('api.API_BUSINESS_CONTENT') . '/dataset/' . $dataset->id);
        return $response;
    }
}
