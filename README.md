## Laravel Service Container
The Laravel service container is a powerful tool for managing class dependencies and performing dependency injection. **Dependency injection** is a fancy phrase that essentially means this: class dependencies are "injected" into the class via the constructor or, in some cases, "setter" methods.

For example, we can use **Dependency Injection** to refactor the following code:
```php
public function store()
{
    $paymentGateway = new PaymentGateway;
    return $paymentGateway->charge(request()->amount);
}
```

to:

```php
public function store(PaymentGateway $paymentGateway)
{
    return $paymentGateway->charge(request()->amount);
}
```

However, this creates a new problem; how can we pass parameters to our `PaymentGateway` service constructor?

In order to pass parameters to services during **Dependency Injection**, we need to tell Laravel how to handle those missing parameters.
- In **AppServiceProvider.php**, we can tell Laravel how to handle the `currency` parameter during Dependency Injection:
    ```php
    <?php

    namespace App\Providers;

    use App\Services\Payment\PaymentGateway;
    use Illuminate\Support\ServiceProvider;

    class AppServiceProvider extends ServiceProvider
    {
        /**
        * Register any application services.
        */
        public function register(): void
        {
            $this->app->bind(PaymentGateway::class, function($app){
                return new PaymentGateway('USD');
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
    ```
    We can even make it more dynamic as follows:

    ```php
    /**
    * Register any application services.
    */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, function($app){
            return new PaymentGateway(request()->currency);
        });
    }
    ```
    This will set the `currency` parameter with respect to each individual request.

Now, we can inject the **PaymentGateway** into any file we need without having to duplicate the logic. However, for this particular example, a problem arises.

if we add some changes to the `store` method, we may add discounts to orders like so:
```php
<?php

namespace App\Http\Orders;

use App\Services\Payment\PaymentGateway;

class OrderDetails
{
    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
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
```

and in **PaymentController.php**:
```php
<?php

namespace App\Http\Controllers;

use App\Http\Orders\OrderDetails;
use App\Services\Payment\PaymentGateway;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(OrderDetails $orderDetails, PaymentGateway $paymentGateway)
    {
        $order = $orderDetails->all();
        return $paymentGateway->charge(50);
    }
}
```

You might think the response would be the following:
```json
{
    amount: 30,
    discount: 20,
    confirmation_number: "GSMPnMypiSMVMCx7",
    currency: "USD"
}
```

However, the actual response would look like this:
```json
{
    amount: 50,
    discount: 0,
    confirmation_number: "3NApB0ESU6VXUlGJ",
    currency: "USD"
}
```
We notice that there `discount`value was unaccounted for. This is because each time Laravel injects a dependency, **it creates a new concrete instance of that dependency**. This means that the second time `PaymentGateway` is injected, the `discount` value will be reset to 0.
In order to fix this issue, we need to tell Laravel to make only one instance of the `PaymentGateway`, we can achieve this using the [**Singleton**](https://laravel.com/docs/10.x/container#binding-a-singleton):

```php
<?php

namespace App\Providers;

use App\Services\Payment\PaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function($app){
            return new PaymentGateway('USD');
        });
    }
}

```
Now, Laravel will only create one instance of the **PaymentGateway** service, and the response will look as we expect:
```json
{
    amount: 30,
    discount: 20,
    confirmation_number: "GSMPnMypiSMVMCx7",
    currency: "USD"
}
```

--------------------
### Dynamic Dependency Injection

So far we discussed how to perform dependency injection. But following the previous example, what if we had multiple payment gateways?

We can have multiple implementation for the `PaymentService` by making a simple refactor:
- Add the interface `PaymentGatewayContract.php`:
    ```php
    <?php

    namespace App\Services\Payment;

    interface PaymentGatewayContract
    {
        public function charge($amount);
        public function setDiscount($discount);
    }
    ```
- Rename `PaymentGateway.php` to `BankPaymentGateway.php`:
    ```php
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
    ```
- Now, in **AppServiceProvider.php**, we need to tell Laravel to to map the abstract **PaymentGatewayContract** to the concrete **BankPaymentGateway**
    ```php
    <?php

    namespace App\Providers;

    use App\Services\Payment\BankPaymentGateway;
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
                return new BankPaymentGateway('USD');
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
    ```
- Then we can modify **PaymentController.php** and **OrderDetails.php** to use the new interface:
    ```php
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
    ```

    ```php
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

    ```

#### Now you might ask: how is this any better?

- To demonstrate how this is better, lets create another payment gateway, **CreditPaymentGateway**, which has different implementation by introducing credit fees:

    ```php
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
    ```
- With this change, in **AppServiceProvider.php** we can easily swap between **BankPaymentGateway** and **CreditPaymentGateway**, without having to change any logic in the code:
    ```php
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
    ```
- Now, if a customer choose **credit** payment method, Laravel will automatically resolve the **PaymentGatewayContract** to the **CreditPaymentGateway**:
    ```json
    {
        amount: 31.5,
        discount: 20,
        confirmation_number: "9VoP38E8oZlugv6t",
        currency: "USD",
        fees: 1.5
    }
    ```

And just like that, with Laravel **Service Container**, we can switch from one integration to another, without having to delete or modify any code or logic.

---
*This tutorial is based on [Coder's Tape - Service Container](https://youtu.be/_z9nzEUgro4?list=PLpzy7FIRqpGD5pN3-Y66YDtxJCYuGumFO)*
