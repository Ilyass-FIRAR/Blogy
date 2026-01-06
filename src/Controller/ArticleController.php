<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'app_articles', methods: ['GET'])]
    public function list(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();

        return $this->render('articles/list.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/articles/create', name: 'app_article_create', methods: ['GET'])]
    public function create(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('articles/create.html.twig');
    }

    #[Route('/articles', name: 'app_article_store', methods: ['POST'])]
    public function store(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $title = $request->request->get('title');
        $content = $request->request->get('content');

        if (empty($title) || empty($content)) {
            $this->addFlash('error', 'Title and content are required.');
            return $this->redirectToRoute('app_article_create');
        }

        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);
        $article->setUser($this->getUser());

        $entityManager->persist($article);
        $entityManager->flush();

        $this->addFlash('success', 'Article published successfully!');
        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
    }

    #[Route('/articles/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('articles/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/articles/{id}/edit', name: 'app_article_edit', methods: ['GET'])]
    public function edit(Article $article): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Check if user is the author
        if ($article->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own articles.');
        }

        return $this->render('articles/edit.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/articles/{id}', name: 'app_article_update', methods: ['POST'])]
    public function update(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Check if user is the author
        if ($article->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own articles.');
        }

        $title = $request->request->get('title');
        $content = $request->request->get('content');

        if (empty($title) || empty($content)) {
            $this->addFlash('error', 'Title and content are required.');
            return $this->redirectToRoute('app_article_edit', ['id' => $article->getId()]);
        }

        $article->setTitle($title);
        $article->setContent($content);

        $entityManager->flush();

        $this->addFlash('success', 'Article updated successfully!');
        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
    }

    #[Route('/articles/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Article $article, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Check if user is the author
        if ($article->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own articles.');
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'Article deleted successfully!');
        return $this->redirectToRoute('app_articles');
    }
}
