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
