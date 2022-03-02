<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use App\Helpers\MediaLinker;
use App\Helpers\Responder;

class PostController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->only('startup_id', 'header', 'text', 'files');
        $validator = Validator::make($data, [
            'files.*' => 'file|image|max:5120',
            'header' => 'required|string|min:5|max:200',
            'text' => 'required|string|min:5|max:5000',
            'startup_id' => 'required|integer',
        ]);
        if ($validator->fails())
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

        if (!Gate::allows('startup', $request->startup_id))
            return response()->json(['message' => 'Forbidden'], 403);

        $response = Http::post(config('api.API_FEED_CONTENT') . '/post', [
            'startupId' => $request->startup_id,
            'header' => $request->header,
            'text' => $request->text,
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_FEED_CONTENT:post:create');

        $post = $response->object();

        $media = new MediaLinker($post->id, 'Post');
        $files = $request->file('files');
        if ($files) {
            $response = $media->attach($request->user(), $files, true);
            if ($response->getStatusCode() != 200)
                Http::delete(config('api.API_FEED_CONTENT') . '/post/' . $post->id);
            return $response;
        } else
            return response()->json(['message' => 'Successful',], 201);
    }
}
