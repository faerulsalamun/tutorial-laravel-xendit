<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Xendit\Xendit;
use Illuminate\Support\Str;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function __construct() {
       Xendit::setApiKey("YOUR_API_KEY");
    }

    public function create(Request $request){

        $params = [
            'external_id' => (string) Str::uuid(),
            'payer_email' => $request->payer_email,
            'description' => $request->description,
            'amount' => $request->amount,
            'redirect_url' => 'faerul.com'
        ];

        $createInvoice = \Xendit\Invoice::create($params);

        // Save to database
        $payment = new Payment;
        $payment->status = 'pending';
        $payment->checkout_link = $createInvoice['invoice_url'];
        $payment->external_id = $params['external_id'];
        $payment->save();

        return response()->json(['data' => $createInvoice['invoice_url']]);
    }

    public function webhook(Request $request){
        $getInvoice = \Xendit\Invoice::retrieve($request->id);

        // Get data
        $payment = Payment::where('external_id',$request->external_id)->firstOrFail();

        if ($payment->status == 'settled'){
            return response()->json(['data' => 'Payment has been already processed']);
        }

        // Update status payment
        $payment->status = strtolower($getInvoice['status']);
        $payment->save();

        return response()->json(['data' => 'Success']);
    }
}
