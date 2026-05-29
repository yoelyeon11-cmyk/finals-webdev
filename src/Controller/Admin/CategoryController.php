<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogger;
use App\Service\AdminRealtimeHelper;
use App\Service\RealtimeBroadcastClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_STAFF')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly AdminRealtimeHelper $realtime,
    ) {
    }

    #[Route('/', name: 'admin_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
            'categoriesFingerprint' => $this->realtime->categoriesFingerprint($categoryRepository),
            'websocketUrl' => $this->realtime->websocketUrl(),
        ]);
    }

    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger,
        RealtimeBroadcastClient $realtimeBroadcast,
    ): Response {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $realtimeBroadcast->publish('category.created', [
                'categoryId' => $category->getId(),
                'name' => $category->getName(),
            ]);

            $logger->log('Category Created', 'Created category: ' . $category->getName());
            $this->addFlash('success', 'Category created successfully!');

            return $this->redirectToRoute('admin_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/category/_form.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger,
        RealtimeBroadcastClient $realtimeBroadcast,
    ): Response {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $realtimeBroadcast->publish('category.updated', [
                'categoryId' => $category->getId(),
                'name' => $category->getName(),
            ]);
            $logger->log('Category Updated', 'Updated category: ' . $category->getName());
            $this->addFlash('success', 'Category updated successfully!');

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/_form.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager,
        ActivityLogger $logger,
        RealtimeBroadcastClient $realtimeBroadcast,
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            if ($category->getProducts()->count() > 0) {
                $this->addFlash('warning', 'Cannot delete category that has products!');
            } else {
                $categoryName = $category->getName();
                $categoryId = $category->getId();
                $entityManager->remove($category);
                $entityManager->flush();
                $realtimeBroadcast->publish('category.deleted', [
                    'categoryId' => $categoryId,
                    'name' => $categoryName,
                ]);
                $logger->log('Category Deleted', 'Deleted category: ' . $categoryName);
                $this->addFlash('success', 'Category deleted successfully!');
            }
        }

        return $this->redirectToRoute('admin_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
