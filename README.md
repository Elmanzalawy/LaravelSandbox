## Repository Pattern
Repository Pattern creates a middle layer between the data layer (Model) and controllers. This allows you to swap out the data layer without affecting the actual code.

This guide is based on [Coder's Tape - Repository Pattern](https://youtu.be/93ZhGkFIwbA) tutorial.

## Repository Pattern also solves a few problems:
- **Prevents repetitive code** by encapsulating logic in one place to be re-used later.
    - Take the following Eloquent query for example in `CustomersController.php`:
        ```php
        public function index(Request $request){
            return Customer::orderBy('active')
                ->whereActive(1)
                ->with('user')
                ->get()
                ->map(function($customer) {
                    return [
                        'customer_id' => $customer->id,
                        'name' => $customer->name,
                        'created_by' => $customer->user->email,
                        'last_updated' => $customer->updated_at->diffForHumans(),
                    ];
                });
        }    
        ```
    - This can be refactored to:
        ```php
        public function index(Request $request){
            return $this->customerRepository->all();
        }
        ```
- **Makes code modular** by allowing us to swap one implementation in one repository to another in a different repository.
    - For example, we can make an abstract `PaymentServiceRepositoryInterface` and multiple concrete implementations `PayMobRepository`, `PayPalRepository`, `...` which all implement the abstract methods in `PaymentServiceRepositoryInterface`. Then we can switch between payment methods freely without having to write any extra code.
## Implementation
- In the `app` directory, create a new directory called `Repositories`
- Next, we need to create **CustomerRepository.php** class, and **CustomerRepositoryInterface.php** interface.
    - **CustomerRepositoryInterface.php**
        ```php
        <?php

        namespace App\Repositories;

        interface CustomerRepositoryInterface
        {
            public function all();
            public function find($customerId);
            public function findByName($customerName);
            public function update($customerId);
            public function delete($customerId);
        }

        ```
    - **CustomerRepository.php**
        ```php
        <?php
        namespace App\Repositories;

        use App\Models\Customer;

        class CustomerRepository implements CustomerRepositoryInterface
        {
            public function all()
            {
                return Customer::orderBy('active')
                ->whereActive(1)
                ->with('user')
                ->get()
                ->map->format();
            }

            public function find($customerId)
            {
                return Customer::whereActive(1)
                    ->with('user')
                    ->whereId($customerId)
                    ->first()
                    ->format();
            }

            public function findByName($customerName)
            {
                return Customer::whereName($customerName)->first();
            }

            public function update($customerId)
            {
                $customer = Customer::whereId($customerId)->firstOrFail();

                $customer->update(request()->only([
                    'name'
                ]));
            }

            public function delete($customerId)
            {
                $customer = Customer::whereId($customerId)->firstOrFail()->delete();
            }
        }

        ```
    - Now, we need to create a new provider for repositories where we bind the abstract class to the concrete class:
        ```bash
        php artisan make:provider RepositoriesServiceProvider
        ```
    - in **RepositoriesServiceProvider.php**, we bind our repository class **CustomerRepository.php** to the interface **CustomerRepositoryInterface.php**:
        ```php
        <?php

        namespace App\Providers;

        use App\Repositories\CustomerRepository;
        use App\Repositories\CustomerRepositoryInterface;
        use Illuminate\Support\ServiceProvider;

        class RepositoriesServiceProvider extends ServiceProvider
        {
            /**
            * Register services.
            */
            public function register(): void
            {
                //
            }

            /**
            * Bootstrap services.
            */
            public function boot(): void
            {
                $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
            }
        }

        ```
    - Now, we can register the **RepositoriesServiceProvider.php** at our `Providers` section in `config/app.php`:
        ```php
        /*
        |--------------------------------------------------------------------------
        | Autoloaded Service Providers
        |--------------------------------------------------------------------------
        |
        | The service providers listed here will be automatically loaded on the
        | request to your application. Feel free to add your own services to
        | this array to grant expanded functionality to your applications.
        |
        */

        'providers' => ServiceProvider::defaultProviders()->merge([
            /*
            * Package Service Providers...
            */

            /*
            * Application Service Providers...
            */
            App\Providers\AppServiceProvider::class,
            App\Providers\AuthServiceProvider::class,
            // App\Providers\BroadcastServiceProvider::class,
            App\Providers\EventServiceProvider::class,
            App\Providers\RouteServiceProvider::class,
            App\Providers\RepositoriesServiceProvider::class,
        ])->toArray(),

        ```
    - We can now use our Repository in `CustomerController.php` using Laravel **Dependency Injection**:
        ```php
        <?php

        namespace App\Http\Controllers;

        use App\Repositories\CustomerRepositoryInterface;
        use Illuminate\Http\Request;

        class CustomerController extends Controller
        {
            private $customerRepository;

            public function __construct(CustomerRepositoryInterface $customerRepository)
            {
                $this->customerRepository = $customerRepository;
            }
        }

        ```
        - Remember: the bindings we made at `RepositoriesServiceProvider` dictate how Laravel will map the abstract `CustomerRepositoryInterface` to the concrete `CustomerRepository`. Failing to set the bindings will throw a `BindingResolutionException`.

    - Now we can implement all of `CustomerRepository` classes at our `CustomersController` class without having to worry about repeating the logic for the data layer:
        ```php
        <?php

        namespace App\Http\Controllers;

        use App\Repositories\CustomerRepositoryInterface;
        use Illuminate\Http\Request;

        class CustomerController extends Controller
        {
            private $customerRepository;

            public function __construct(CustomerRepositoryInterface $customerRepository)
            {
                $this->customerRepository = $customerRepository;
            }

            public function index(Request $request){
                return $this->customerRepository->all();
            }

            public function show(Request $request, $customerId){
                return $this->customerRepository->find($customerId);
            }

            public function update($customerId)
            {
                $this->customerRepository->update($customerId);
                return redirect(route('customers.show', $customerId));
            }

            public function destroy($customerId)
            {
                $this->customerRepository->delete($customerId);
                return redirect(route('customers.index'));
            }
        }

        ```
