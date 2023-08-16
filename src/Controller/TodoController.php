<?php

namespace App\Controller;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TodoController extends AbstractController
{
    #[Route('/', name: 'app_todo')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TodoController.php',
        ]);
    }

    #[Route('/todos', name: 'all_todos')]
    public function allTodos(TodoRepository $repository): JsonResponse
    {
        $todos = $repository->findBy(['parent' => null]);

        $data = $this->getData($todos);

        return $this->json($data);
    }

    private function getData($todos)
    {
        if ($todos === null) {
            return [];
        }
        $data = [];

        foreach ($todos as $todo) {
            $data[] = [
                'id' => $todo->getId(),
                'title' => $todo->getTitle(),
                'description' => $todo->getDescription(),
                'children' => $this->getData($todo->getChildren())
            ];
        }

        return $data;
    }
}
