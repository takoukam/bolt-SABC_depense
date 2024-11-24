<?php

namespace App\Exception;

class PaymentException extends \Exception
{
    public static function invalidAmount(float $amount): self
    {
        return new self(sprintf('Le montant %s est invalide. Le montant doit être supérieur à 0.', $amount));
    }

    public static function insufficientFunds(float $amount, float $available): self
    {
        return new self(sprintf('Fonds insuffisants. Montant demandé: %s, Disponible: %s', $amount, $available));
    }

    public static function gatewayError(string $message): self
    {
        return new self(sprintf('Erreur de la passerelle de paiement: %s', $message));
    }
}