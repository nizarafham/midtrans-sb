<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ProcessPaymentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jumlah'=>'required',
        ]);
        if ($validator->failed()) {
            return response()->json(data: $validator->errors(), status: 400);
        }

        $transaction = Transaction::create([
            'invoice_number' => "INV-".uniqid(),
            'jumlah' => $request->jumlah,
            'status' => 'CREATED',
        ]);

        $resp = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withBasicAuth('SB-Mid-server-TfZpfHcOCN1P6BOzm63mvlhP','')
            ->post('https://api.sandbox.midtrans.com/v2/charge', [
                'payment_type' => 'gopay',
                'transaction_details' => [
                    'order_id' => $transaction->id,
                    'gross_amount' => $transaction->jumlah,
                ]
            ]);
        if ($resp->status() == 201 || $resp->status() == 200){
            $actions = $resp->json('actions');
            if (empty($actions)){
                return response()->json(['message' => $resp['status_message']], 500);
            }
            $actionMap = [];
            foreach ($actions as $action) {
                $actionMap[$action['name']] = $action['url'];
            }

            return response()->json(['qr'=> $actionMap['generate-qr-code']]);
        }
        return response()->json(['message' => $resp->body()], 500);
    }

}
