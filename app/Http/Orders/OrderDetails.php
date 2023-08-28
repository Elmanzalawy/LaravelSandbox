<?php

namespace App\Http\Orders;

use App\Services\Payment\PaymentGatewayContract;

class OrderDetails
{
    private $paymentGateway;

    public function __construct(PaymentGatewayContract $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function all()
    {
        $this->paymentGateway->setDiscount(20);

        return [
            'name' => 'Victor',
            'address' => '123 Coders\'s Tape Street'
        ];
    }
}
