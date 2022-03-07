<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Responder;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->only('startup_id', 'amount');
        $validator = Validator::make($data, [
            'startup_id' => 'required|integer',
            'amount' => 'required|numeric|min:1'
        ]);
        if ($validator->fails())
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);

        $response = Http::post(config('api.API_PAYMENT') . '/payment', [
            'user_id' => $request->user(),
            'startup_id' => $request->startup_id,
            'amount' => $request->amount,
            'status' => 'SUCCESS'
        ]);
        if (!$response->successful())
            return Responder::error($response, 'API_PAYMENT:payment:create');

        $payment = $response->object();

        $response = Http::get(config('api.API_PAYMENT') . '/payment/' . $payment->id . '/link');
        if (!$response->successful())
            return Responder::error($response, 'API_PAYMENT:payment:link');

        return response()->json(['link' => $response->json(),], 201);
    }
}
