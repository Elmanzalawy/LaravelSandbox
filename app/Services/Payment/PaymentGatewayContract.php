<?php

namespace App\Services\Payment;

interface PaymentGatewayContract
{
    public function charge($amount);
    public function setDiscount($discount);
}
