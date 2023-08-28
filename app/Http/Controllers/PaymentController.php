<?php

namespace App\Http\Controllers;

use App\Http\Orders\OrderDetails;
use App\Services\Payment\PaymentGatewayContract;

class PaymentController extends Controller
{
    public function store(OrderDetails $orderDetails, PaymentGatewayContract $paymentGateway)
    {
        $order = $orderDetails->all();
        return $paymentGateway->charge(request()->amount);
    }
}
