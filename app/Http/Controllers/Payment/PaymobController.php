<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PaymentService;

class PaymobController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function initiatePayment(Request $request)
    {
        // Handle Paymob payment initiation
    }

    public function handleCallback(Request $request)
    {
        // Handle Paymob callback
    }
}

