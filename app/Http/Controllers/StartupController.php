<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class StartupController extends Controller
{
    private function deleteMedia($media)
    {
        foreach ($media as $i)
            Http::delete(config('api.API_MEDIA') . '/media/' . $i->id);
    }

    public function create(Request $request)
    {
        if (Gate::allows('creator')) {
            $data = $request->only('documents', 'name', 'ending_at', 'funds_goal', 'description');
            $validator = Validator::make($data, [
                'documents.*' => 'required|file|mimes:jpg,bmp,png,pdf',
                'name' => 'required|string|min:10',
                'description' => 'required|string|min:10',
                'funds_goal' => 'required|integer|min:500|max:10000000',
                'ending_at' => 'required|date|after:today'
            ]);
            if ($validator->fails())
                return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

            $response = Http::post(config('api.API_FEED_CONTENT') . '/startup', [
                'user_id' => $request->user()['uid'],
                'name' => $request->name,
                'description' => $request->description,
                'endingAt' => $request->ending_at,
                'funds_goal' => $request->name,
            ]);
            if (!$response->successful())
                return response()->json(['message' => 'API_FEED_CONTENT startup error code: ' . $response->getStatusCode()], 500);
            $startup = $response->object();

            $documents = $request->file('documents');
            $created_media = [];
            foreach ($documents as $document) {
                $response = Http::post(config('api.API_MEDIA') . '/media', [
                    'user_id' => $request->user()['uid'],
                    'is_public' => false,
                    'mime_type' => $document->getMimeType(),
                ]);
                if (!$response->successful()) {
                    $this->deleteMedia($created_media);
                    return response()->json(['message' => 'API_MEDIA create error code: ' . $response->getStatusCode()], 500);
                }

                $media = $response->object();
                $created_media[] = $media;
                $response = Http::attach('file', fopen($document, 'r'), $document->getClientOriginalName())
                    ->post(config('api.API_MEDIA') . '/media/' . $media->id . '/upload');
                if (!$response->successful()) {
                    $this->deleteMedia($created_media);
                    return response()->json(['message' => 'API_MEDIA upload error code: ' . $response->getStatusCode()], 500);
                }

                $response = Http::post(config('api.API_FEED_CONTENT') . '/mediarelationship', [
                    'mediable_type' => 'Startup',
                    'mediable_id' => $startup->id,
                    'media_id' => $media->id,
                ]);
                if (!$response->successful()) {
                    $this->deleteMedia($created_media);
                    return response()->json(['message' => 'API_FEED_CONTENT media relationsip error code: ' . $response->getStatusCode()], 500);
                }
            }
            return response()->json(['message' => 'Successful',], 201);
        }

        return response()->json([
            'message' => 'Forbidden',
        ], 403);
    }

    public function get(Request $request)
    {
        if (Gate::allows('creator')) {
            $response = Http::get(config('api.API_FEED_CONTENT') . '/startup', [
                'userId' => $request->user()['uid'],
            ]);
            if (!$response->successful())
                return response()->json(['message' => 'API_FEED_CONTENT startup error code: ' . $response->getStatusCode()], 500);

            return response()->json($response->object(), 200);
        }

        return response()->json([
            'message' => 'Forbidden',
        ], 403);
    }
}
