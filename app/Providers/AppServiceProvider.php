<?php

namespace App\Providers;

use App\Services\Payment\BankPaymentGateway;
use App\Services\Payment\CreditPaymentGateway;
use App\Services\Payment\PaymentGatewayContract;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayContract::class, function($app){
            if(request()->paymentMethod == 'credit'){
                return new CreditPaymentGateway('USD');
            }else{
                return new BankPaymentGateway('USD');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
