<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Like;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LikeController extends AbstractController
{
    #[Route('/articles/{id}/like', name: 'app_article_like', methods: ['POST'])]
    public function like(
        #[MapEntity(id: 'id')] Article $article,
        Request $request,
        LikeRepository $likeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Basic CSRF check (optional since Twig already includes token)
        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('submit', $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        $user = $this->getUser();

        $existing = $likeRepository->findOneBy([
            'user' => $user,
            'article' => $article,
        ]);

        if ($existing) {
            $this->addFlash('info', 'You already liked this article.');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        $like = new Like();
        $like->setUser($user);
        $like->setArticle($article);

        $entityManager->persist($like);
        $entityManager->flush();

        $this->addFlash('success', 'You liked this article.');
        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
    }

    #[Route('/articles/{id}/unlike', name: 'app_article_unlike', methods: ['POST'])]
    public function unlike(
        #[MapEntity(id: 'id')] Article $article,
        Request $request,
        LikeRepository $likeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('submit', $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        $user = $this->getUser();

        $existing = $likeRepository->findOneBy([
            'user' => $user,
            'article' => $article,
        ]);

        if (!$existing) {
            $this->addFlash('info', 'You have not liked this article.');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        $entityManager->remove($existing);
        $entityManager->flush();

        $this->addFlash('success', 'You unliked this article.');
        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
    }
}
