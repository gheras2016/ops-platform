<?php

namespace App\Services\Payments;

use InvalidArgumentException;

/** Resolves the configured payment gateway driver (config/payments.php). */
class PaymentManager
{
    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= config('payments.gateway', 'test');

        return match ($name) {
            'test' => new TestGateway(),
            'moyasar' => new MoyasarGateway(),
            'stripe' => new StripeGateway(),
            default => throw new InvalidArgumentException("Unsupported payment gateway [{$name}]."),
        };
    }
}
