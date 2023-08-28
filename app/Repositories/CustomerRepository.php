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
