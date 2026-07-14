<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\PaymentMethod;

class PaymentMethodPolicy
{
    private function owns(Customer $customer, PaymentMethod $paymentMethod): bool
    {
        return $paymentMethod->customer_id === $customer->id;
    }

    public function view(Customer $customer, PaymentMethod $paymentMethod): bool
    {
        return $this->owns($customer, $paymentMethod);
    }

    public function update(Customer $customer, PaymentMethod $paymentMethod): bool
    {
        return $this->owns($customer, $paymentMethod);
    }

    public function delete(Customer $customer, PaymentMethod $paymentMethod): bool
    {
        return $this->owns($customer, $paymentMethod);
    }

    public function setDefault(Customer $customer, PaymentMethod $paymentMethod): bool
    {
        return $this->owns($customer, $paymentMethod);
    }
}
