<?php

namespace App\Controller;

use App\Entity\BucketListItem;
use App\Form\BucketListItemType;
use App\Service\BucketListSuggestionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

class BucketListController extends AbstractController
{
    private BucketListSuggestionService $suggestionService;
    private LoggerInterface $logger;

    public function __construct(BucketListSuggestionService $suggestionService, LoggerInterface $logger)
    {
        $this->suggestionService = $suggestionService;
        $this->logger = $logger;
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/bucket-list', name: 'app_bucket_list')]
    public function index(EntityManagerInterface $entityManager)
    {
        $bucketListItems = $entityManager->getRepository(BucketListItem::class)->findBy(['user' => $this->getUser()]);

        return $this->render('bucket_list/index.html.twig', [
            'bucketListItems' => $bucketListItems,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/bucket-list/new', name: 'app_bucket_list_new')]
    public function new(Request $request, EntityManagerInterface $entityManager)
    {
        $bucketListItem = new BucketListItem();
        $form = $this->createForm(BucketListItemType::class, $bucketListItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bucketListItem->setUser($this->getUser());
            $entityManager->persist($bucketListItem);
            $entityManager->flush();

            $this->addFlash('success', 'New bucket list item added successfully.');
            return $this->redirectToRoute('app_bucket_list');
        }

        return $this->render('bucket_list/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bucket-list/suggestions', name: 'app_bucket_list_suggestions', methods: ['GET'])]
    public function suggestions(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $interests = $request->query->get('interests', 'adventure, travel');
        try {
            $suggestions = $this->suggestionService->getSuggestions($interests);

            if (empty($suggestions)) {
                $this->logger->info('No suggestions available for interests: ' . $interests);
                return $this->render('bucket_list/suggestions.html.twig', [
                    'error' => 'No suggestions available at this time.',
                    'suggestion' => null,
                ]);
            }

            $randomSuggestion = $suggestions[array_rand($suggestions)];
        } catch (\Exception $e) {
            $this->logger->error('Error fetching suggestions: ' . $e->getMessage());
            return $this->render('bucket_list/suggestions.html.twig', [
                'error' => 'Unable to fetch suggestions at this time.',
                'suggestion' => null,
            ]);
        }

        return $this->render('bucket_list/suggestions.html.twig', [
            'error' => null,
            'suggestion' => $randomSuggestion,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/bucket-list/{id}', name: 'app_bucket_list_detail', methods: ['GET', 'POST'])]
    public function detail(int $id, Request $request, EntityManagerInterface $entityManager)
    {
        $bucketListItem = $entityManager->getRepository(BucketListItem::class)->find($id);

        if (!$bucketListItem || $bucketListItem->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Bucket list item not found.');
        }

        $form = $this->createForm(BucketListItemType::class, $bucketListItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Bucket list item updated successfully.');
            return $this->redirectToRoute('app_bucket_list');
        }

        return $this->render('bucket_list/detail.html.twig', [
            'form' => $form->createView(),
            'bucketListItem' => $bucketListItem,
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/bucket-list/{id}/delete', name: 'app_bucket_list_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager)
    {
        $bucketListItem = $entityManager->getRepository(BucketListItem::class)->find($id);

        if (!$bucketListItem || $bucketListItem->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Bucket list item not found.');
        }

        if (!$this->isCsrfTokenValid('delete' . $bucketListItem->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_bucket_list');
        }

        $entityManager->remove($bucketListItem);
        $entityManager->flush();

        $this->addFlash('success', 'Bucket list item deleted successfully.');
        return $this->redirectToRoute('app_bucket_list');
    }

    #[Route('/', name: 'app_homepage')]
    public function homepage(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->redirectToRoute('app_bucket_list');
    }
}