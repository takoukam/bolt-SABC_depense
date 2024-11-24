<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Exception\PaymentException;
use App\Service\PaymentGateway\PaymentGatewayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Psr\Log\LoggerInterface;

class PaymentService
{
    private EntityManagerInterface $entityManager;
    private array $gateways;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentGatewayInterface $mtnGateway,
        PaymentGatewayInterface $orangeGateway,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->gateways = [
            'mtn' => $mtnGateway,
            'orange' => $orangeGateway,
        ];
        $this->logger = $logger;
    }

    public function initializePayment(Paiement $paiement): array
    {
        try {
            // Validation du montant
            if ($paiement->getMontant() <= 0) {
                throw PaymentException::invalidAmount($paiement->getMontant());
            }

            // Vérification des fonds disponibles
            $caisse = $paiement->getCaisse();
            if ($paiement->getMontant() > $caisse->getBudget()) {
                throw PaymentException::insufficientFunds($paiement->getMontant(), $caisse->getBudget());
            }

            // Vérification de la disponibilité de la passerelle
            if (!isset($this->gateways[$paiement->getMethode()])) {
                throw new PaymentException('Méthode de paiement non supportée');
            }

            $gateway = $this->gateways[$paiement->getMethode()];
            
            // Calcul des frais
            $frais = $gateway->calculateFees($paiement->getMontant());
            $paiement->setFrais($frais);
            
            // Génération de la référence unique
            $reference = Uuid::v4()->toRfc4122();
            $paiement->setReference($reference);
            
            // Initialisation du paiement avec l'opérateur
            $result = $gateway->initializePayment(
                $paiement->getBeneficiaire()->getTelephone(),
                $paiement->getMontant(),
                $reference
            );
            
            $this->entityManager->persist($paiement);
            $this->entityManager->flush();
            
            $this->logger->info('Paiement initialisé', [
                'reference' => $reference,
                'montant' => $paiement->getMontant(),
                'beneficiaire' => $paiement->getBeneficiaire()->getNom()
            ]);

            return $result;

        } catch (PaymentException $e) {
            $this->logger->error('Erreur lors de l\'initialisation du paiement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Erreur inattendue lors de l\'initialisation du paiement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw PaymentException::gatewayError($e->getMessage());
        }
    }

    public function checkPaymentStatus(Paiement $paiement): array
    {
        try {
            $gateway = $this->gateways[$paiement->getMethode()];
            $status = $gateway->checkPaymentStatus($paiement->getReference());

            if ($status['status'] === 'success') {
                $paiement->setStatus('success');
                $paiement->setValidatedAt(new \DateTimeImmutable());
                $this->entityManager->flush();
            }

            return $status;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du statut', [
                'reference' => $paiement->getReference(),
                'error' => $e->getMessage()
            ]);
            throw PaymentException::gatewayError($e->getMessage());
        }
    }
}