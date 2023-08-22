<?php

namespace App\Controller;

use App\Entity\Todo;
use App\Entity\User;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TodoController extends AbstractController
{
    #[Route('/todos', methods: ['GET'], name: 'all_todos')]
    public function index(TodoRepository $repository, Request $request): JsonResponse
    {
        $isDone = $request->query->get('isDone', null);
        $priority = $request->query->get('priority', null);
        $orderBy = $request->query->get('orderBy', null);

        $filterQueryParams = [
            'parent' => null
        ];
        $orderByParams = [];
        if ($isDone) {
            $filterQueryParams['isDone'] = $isDone;
        }
        if ($priority) {
            $filterQueryParams['priority'] = $priority;
        }
        if ($orderBy) {
            if (substr($orderBy, 0, 1) === '-') {
                $orderByParams[substr($orderBy, 1)] = 'DESC';
            }
            else {
                $orderByParams[$orderBy] = 'ASC';
            }
        }

        $todos = $repository->findBy($filterQueryParams, $orderByParams);

        $data = $this->getData($todos);

        return $this->json($data);
    }

    #[Route('/todos', methods: ['POST'], name: 'create_todos')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        TodoRepository $repository,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        $todo = new Todo;

        $requestBody = json_decode($request->getContent());
        $todo->setTitle($requestBody->title);
        $todo->setDescription($requestBody->description);
        $todo->setIsDone(false);
        $todo->setCreatedAt(new \DateTimeImmutable("now"));

        if ($requestBody->priority <= 0 || $requestBody->priority > 5) {
            return $this->json(['Message' => 'Priority must be from 1 to 5.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $todo->setPriority($requestBody->priority);

        $todo->setUser($user);

        $parent = $repository->findOneBy(['id' => $requestBody->parent, 'user' => $user]);
        if (!$parent) {
            return $this->json(['Message' => 'The parent task must your own.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $todo->setParent($parent);

        $errors = $validator->validate($todo);

        if (count($errors) > 0) {
            return $this->json((string)$errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($todo);
        $entityManager->flush();

        return $this->json(null, JsonResponse::HTTP_CREATED);
    }

    #[Route('/todos/{id}', methods: ['PUT'])]
    public function update(
        Request $request,
        TodoRepository $repository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        #[CurrentUser] ?User $user,
        int $id
    ): JsonResponse {
        $todo = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$todo) {
            return $this->json(['Message' => 'Not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $requestBody = json_decode($request->getContent());

        $todo->setTitle($requestBody->title ?? $todo->getTitle());
        $todo->setDescription($requestBody->description ?? $todo->getDescription());

        $errors = $validator->validate($todo);

        if (count($errors) > 0) {
            return $this->json((string)$errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($todo);
        $entityManager->flush();

        return $this->json(null, JsonResponse::HTTP_OK);
    }

    #[Route('/todos/{id}', methods: ['DELETE'])]
    public function delete(
        int $id,
        #[CurrentUser] ?User $user,
        TodoRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $todo = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (null === $todo) {
            return $this->json(['Message' => 'Not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($todo->isIsDone()) {
            return $this->json(['Message' => 'You cannot delete completed tasks.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($todo);
        $entityManager->flush();

        return $this->json(null, JsonResponse::HTTP_GONE);
    }

    #[Route('/todos/search', methods: ['GET'])]
    public function search(TodoRepository $repository, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $query = $request->query->getString('q');
        $todos = $repository->searchByTitle($query);

        $data = [];

        foreach ($todos as $todo) {
            $data[] = [
                'id' => $todo->getId(),
                'title' => $todo->getTitle(),
                'description' => $todo->getDescription(),
                'priority' => $todo->getPriority(),
                'createdAt' => $todo->getCreatedAt(),
                'completedAt' => $todo->getCompletedAt()
            ];
        }

        return $this->json($data);
    }

    #[Route('/todos/complete/{id}')]
    public function complete(
        int $id,
        #[CurrentUser] ?User $user,
        TodoRepository $repository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $todo = $repository->findOneBy(['id' => $id, 'user' => $user]);

        if (null === $todo) {
            return $this->json(['Message' => 'Not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $isAllChildrenComplete = true;
        foreach ($todo->getChildren() as $child) {
            if ($child->isIsDone() !== true) {
                $isAllChildrenComplete = false;
                break;
            }
        }

        if ($isAllChildrenComplete) {
            $todo->setIsDone(true);
            $todo->setCompletedAt(new \DateTimeImmutable('now'));
            $entityManager->persist($todo);
            $entityManager->flush();

            return $this->json(null, JsonResponse::HTTP_OK);
        }

        return $this->json(['Message' => 'Some subtasks are not completed.'], JsonResponse::HTTP_BAD_REQUEST);
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
                'priority' => $todo->getPriority(),
                'isDone' => $todo->isIsDone(),
                'createdAt' => $todo->getCreatedAt(),
                'completedAt' => $todo->getCompletedAt(),
                'children' => $this->getData($todo->getChildren())
            ];
        }

        return $data;
    }
}
