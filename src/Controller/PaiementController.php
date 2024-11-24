<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Exception\PaymentException;
use App\Form\PaiementType;
use App\Repository\PaiementRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/paiement')]
class PaiementController extends AbstractController
{
    #[Route('/', name: 'app_paiement_index', methods: ['GET'])]
    public function index(Request $request, PaiementRepository $paiementRepository, PaginatorInterface $paginator): Response
    {
        try {
            $query = $paiementRepository->createQueryBuilder('p')
                ->orderBy('p.createdAt', 'DESC')
                ->getQuery();

            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1),
                10
            );

            return $this->render('paiement/index.html.twig', [
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du chargement des paiements.');
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/new', name: 'app_paiement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PaymentService $paymentService): Response
    {
        $paiement = new Paiement();
        $form = $this->createForm(PaiementType::class, $paiement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $result = $paymentService->initializePayment($paiement);
                $this->addFlash('success', 'Paiement initialisé avec succès.');
                return $this->redirectToRoute('app_paiement_show', ['id' => $paiement->getId()]);
            } catch (PaymentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur inattendue est survenue. Veuillez réessayer plus tard.');
            }
        }

        return $this->render('paiement/new.html.twig', [
            'paiement' => $paiement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_show', methods: ['GET'])]
    public function show(Paiement $paiement): Response
    {
        try {
            return $this->render('paiement/show.html.twig', [
                'paiement' => $paiement,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'affichage du paiement.');
            return $this->redirectToRoute('app_paiement_index');
        }
    }

    #[Route('/{id}/check-status', name: 'app_paiement_check_status', methods: ['POST'])]
    public function checkStatus(Paiement $paiement, PaymentService $paymentService): Response
    {
        try {
            $status = $paymentService->checkPaymentStatus($paiement);
            return $this->json($status);
        } catch (PaymentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue lors de la vérification du statut.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}