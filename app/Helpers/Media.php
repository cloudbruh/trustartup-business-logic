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
}
