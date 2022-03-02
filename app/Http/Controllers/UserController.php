<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Responder;

class UserController extends Controller
{
    public function setMedia(Request $request)
    {
        $data = $request->only('file');
        $validator = Validator::make($data, [
            'file' => 'required|file|image|max:5120',
        ]);
        if ($validator->fails())
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

        $file = $request->file('file');
        $response = Http::post(config('api.API_MEDIA') . '/media', [
            'userId' => $request->user(),
            'isPublic' => true,
            'mimeType' => $file->getMimeType(),
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_MEDIA:media:create');

        $file = $request->file('file');
        $media = $response->object();
        $response = Http::attach('file', fopen($file, 'r'), $file->getClientOriginalName())
            ->post(config('api.API_MEDIA') . '/media/' . $media->id . '/upload');
        if (!$response->successful())
            return Responder::error($response, 'API_MEDIA:media:upload');

        $response = Http::put(config('api.API_USER') . '/user', [
            'id' => $request->user(),
            'media_id' => $media->id,
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_USER:user:update');

        return response()->json(['message' => 'Successful',], 200);
    }

    public function get(Request $request)
    {
        $response = Http::get(config('api.API_USER') . '/user/' . $request->user());
        if (!$response->successful())
            return Responder::error($response, 'API_USER:user:get');
        return response()->json($response->json(), 200);
    }
}
