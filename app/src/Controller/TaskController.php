<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Incoming\CreateTaskDto;
use App\Enum\TaskStatus;
use App\Service\TaskService;
use App\ValueObject\UserUuid;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskController
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {}

    public function getCollection(Request $request): JsonResponse
    {
        $status = null;
        $assigneeUuid = null;

        try {
            $statusFilter = $request->query->get('status');
            $assigneeIdFilter = $request->query->get('assigneeId');

            if ($statusFilter !== null) {
                $status = TaskStatus::tryFrom(strtolower($statusFilter));
                if ($status === null) {
                    throw new \InvalidArgumentException("Invalid status value provided: '{$statusFilter}'.");
                    // Or provide allowed values in the message
                }
            }

            if ($assigneeIdFilter !== null) {
                try {
                    $assigneeUuid = new UserUuid($assigneeIdFilter);
                } catch (InvalidUuidStringException $e) {
                    throw new \InvalidArgumentException("Invalid assignee UUID format provided: '{$assigneeIdFilter}'.");
                }
            }

            $tasks = $this->taskService->getCollection($status, $assigneeUuid);

            return new JsonResponse($tasks);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
            return new JsonResponse(['error' => 'An internal error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->getContent();

        try {
            $dto = $this->serializer->deserialize($data, CreateTaskDto::class, 'json');
            $violations = $this->validator->validate($dto);

            if (count($violations) > 0) {
                $errors = [];

                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()][] = $violation->getMessage();
                }

                return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $taskDto = $this->taskService->create($dto);

            return new JsonResponse($taskDto, Response::HTTP_CREATED);
        } catch (\RuntimeException $exception) {
            return new JsonResponse(['message' => 'Error'], 500);
        }
    }
}
