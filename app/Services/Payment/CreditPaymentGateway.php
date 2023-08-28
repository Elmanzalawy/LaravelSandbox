<?php

namespace App\Services\Payment;
use Illuminate\support\Str;

class CreditPaymentGateway implements PaymentGatewayContract
{
    private $currency;
    private $discount;

    public function __construct($currency)
    {
        $this->currency = $currency;
        $this->discount = 0;
    }

    /**
     * charge bank account
     *
     * @param  float $amount
     * @return void
     */
    public function charge($amount)
    {
        $fees = $amount * 0.03;

        return [
            'amount' => ($amount - $this->discount) + $fees,
            'discount' => $this->discount,
            'confirmation_number' => Str::random(),
            'currency' => $this->currency,
            'fees' => $fees,
        ];
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }
}
