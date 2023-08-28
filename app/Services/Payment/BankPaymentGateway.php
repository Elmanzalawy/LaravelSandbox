<?php

namespace App\Services\Payment;
use Illuminate\support\Str;

class BankPaymentGateway implements PaymentGatewayContract
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
        return [
            'amount' => $amount - $this->discount,
            'discount' => $this->discount,
            'confirmation_number' => Str::random(),
            'currency' => $this->currency,
        ];
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }
}
