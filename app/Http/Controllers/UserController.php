<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Responder;
use App\Helpers\Media;

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
        $media = Media::uploadMedia($file, $request->user(), true);
        if (!$media)
            return Responder::error(null, 'API_MEDIA:media:upload');

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
        $user = $response->object();
        $user->media = null;
        if ($user->media_id) {
            $response = Http::get(config('api.API_MEDIA') . '/media/' . $user->media_id);
            if (!$response->successful())
                return Responder::error($response, 'API_MEDIA:media:get');
            $user->media = $response->object()->link;
        }
        return response()->json(collect($user)->except('media_id'), 200);
    }
}
