<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use App\Helpers\Responder;

class Media
{
    public static function getMediaByIds($media_ids)
    {
        $media = [];
        foreach ($media_ids as $item) {
            $response = Http::get(config('api.API_MEDIA') . '/media/' . $item);
            if (!$response->successful())
                return Responder::error($response, 'API_MEDIA:media:get');
            $media[] = $response->object()->link;
        }
        return $media;
    }

    public static function uploadMedia($file, $user, $is_public)
    {
        $response = Http::post(config('api.API_MEDIA') . '/media', [
            'userId' => $user,
            'isPublic' => $is_public,
            'mimeType' => $file->getMimeType(),
        ]);
        if (!$response->successful())
            return null;

        $media = $response->object();
        $response = Http::attach('file', fopen($file, 'r'), $file->getClientOriginalName())
            ->post(config('api.API_MEDIA') . '/media/' . $media->id . '/upload');
        if (!$response->successful())
            return null;

        return $media;
    }
}
