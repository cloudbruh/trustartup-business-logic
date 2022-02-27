<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use App\Helpers\Responder;

class MediaLinker
{
    private $mediable_id;

    private $mediable_type;

    public $created_media = [];

    public $created_relationships = [];

    public function __construct($mediable_id, $mediable_type)
    {
        $this->mediable_id = $mediable_id;
        $this->mediable_type = $mediable_type;
    }

    private function revertTransaction()
    {
        foreach ($this->created_media as $i)
            Http::delete(config('api.API_MEDIA') . '/media/' . $i->id);
        foreach ($this->created_relationships as $i)
            Http::delete(config('api.API_FEED_CONTENT') . '/mediarelationship/' . $i->id);
    }

    public function attach($user_id, $files, $is_public)
    {
        foreach ($files as $file) {
            $response = Http::post(config('api.API_MEDIA') . '/media', [
                'userId' => $user_id,
                'isPublic' => $is_public,
                'mimeType' => $file->getMimeType(),
            ]);
            if (!$response->successful()) {
                $this->revertTransaction();
                return Responder::error($response, 'API_MEDIA:create');
            }

            $media = $response->object();
            $this->created_media[] = $media;

            $response = Http::attach('file', fopen($file, 'r'), $file->getClientOriginalName())
                ->post(config('api.API_MEDIA') . '/media/' . $media->id . '/upload');
            if (!$response->successful()) {
                $this->revertTransaction();
                return Responder::error($response, 'API_MEDIA:upload');
            }

            $response = Http::post(config('api.API_FEED_CONTENT') . '/mediarelationship', [
                'mediableType' => $this->mediable_type,
                'mediableId' => $this->mediable_id,
                'mediaId' => $media->id,
            ]);
            if (!$response->successful()) {
                $this->revertTransaction();
                return Responder::error($response, 'API_FEED_CONTENT:mediarelationship:create');
            }

            $this->created_relationships[] = $response->object();
        }
        return response()->json(['message' => 'Successful',], 200);
    }
}
