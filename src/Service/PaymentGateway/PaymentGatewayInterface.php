<?php

namespace App\Service\PaymentGateway;

interface PaymentGatewayInterface
{
    public function initializePayment(string $phoneNumber, float $amount, string $reference): array;
    public function checkPaymentStatus(string $reference): array;
    public function calculateFees(float $amount): float;
}