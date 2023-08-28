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
