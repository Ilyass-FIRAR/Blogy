<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommentController extends AbstractController
{
    #[Route('/articles/{articleId}/comments', name: 'app_comment_store', methods: ['POST'])]
    public function store(
        Article $article,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $content = $request->request->get('content');

        if (empty($content)) {
            $this->addFlash('error', 'Comment cannot be empty.');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setUser($this->getUser());
        $comment->setArticle($article);

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Comment posted successfully!');
        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
    }

    #[Route('/comments/{id}', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(
        Comment $comment,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Check if user is the author
        if ($comment->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own comments.');
        }

        $articleId = $comment->getArticle()->getId();

        $entityManager->remove($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Comment deleted successfully!');
        return $this->redirectToRoute('app_article_show', ['id' => $articleId]);
    }
}
