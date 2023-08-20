<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'api_registration')]
    public function index(
        UserPasswordHasherInterface $passwordHasher, 
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $user = new User();

        $requestBody = json_decode($request->getContent());
        $email = $requestBody->email;
        $plainPassword = $requestBody->password;

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plainPassword
        );
        $user->setEmail($email);
        $user->setPassword($hashedPassword);
        
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            return $this->json((string)$errors, 400);
        }

        $entityManager->persist($user);

        $token = new ApiToken($user);
        $entityManager->persist($token);
        $entityManager->flush();

        return $this->json([
            'user' => $user->getEmail(),
            'token' => $token->getToken(),
        ]);
    }
}
