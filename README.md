## Repository Pattern
Repository Pattern creates a middle layer between the data layer (Model) and controllers. This allows you to swap out the data layer without affecting the actual code.


## Repository Pattern also solves a few problems:
- **Prevents repetitive code** by encapsulating logic in one place to be re-used later.
    - Take the following Eloquent query for example:
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
