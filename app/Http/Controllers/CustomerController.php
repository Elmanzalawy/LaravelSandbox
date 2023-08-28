<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
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
}
