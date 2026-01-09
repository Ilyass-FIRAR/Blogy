<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(ArticleRepository $articleRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $articles = $articleRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/dashboard.html.twig', [
            'articles' => $articles,
        ]);
    }
}
