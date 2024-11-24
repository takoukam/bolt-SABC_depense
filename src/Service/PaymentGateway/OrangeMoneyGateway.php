<?php

namespace App\Service\PaymentGateway;

use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OrangeMoneyGateway implements PaymentGatewayInterface
{
    private Client $client;
    private string $apiKey;
    private string $apiSecret;

    public function __construct(ParameterBagInterface $params)
    {
        $this->apiKey = $params->get('orange_money.api_key');
        $this->apiSecret = $params->get('orange_money.api_secret');
        $this->client = new Client([
            'base_uri' => 'https://api.orange.com/payment/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function initializePayment(string $phoneNumber, float $amount, string $reference): array
    {
        // Implémentation de l'initialisation du paiement Orange Money
        return [
            'status' => 'pending',
            'reference' => $reference,
        ];
    }

    public function checkPaymentStatus(string $reference): array
    {
        // Implémentation de la vérification du statut du paiement
        return [
            'status' => 'success',
            'reference' => $reference,
        ];
    }

    public function calculateFees(float $amount): float
    {
        // Calcul des frais Orange Money (à adapter selon la grille tarifaire)
        return $amount * 0.018;
    }

    private function getAccessToken(): string
    {
        // Implémentation de l'obtention du token d'accès
        return 'dummy_token';
    }
}