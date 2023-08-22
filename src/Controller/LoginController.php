<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'api_login')]
    public function index(
        #[CurrentUser] ?User $user, 
        ApiTokenRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if ($user === null) {
            return $this->json([
                'message' => 'Missing credentials'
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }   

        $token = $repository->findOneBy(['user' => $user]);
        if (!$token->isValid()) {
            $entityManager->remove($token);
            $entityManager->flush();
            
            $token = new ApiToken($user);
            $entityManager->persist($token);
            $entityManager->flush();
        }

        return $this->json([
            'user' => $user->getUserIdentifier(),
            'token' => $token->getToken(),
        ]);
    }
}
