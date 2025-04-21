<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Incoming\CreateTaskDto;
use App\Enum\TaskStatus;
use App\Exception\TaskNotFoundException;
use App\Service\TaskService;
use App\ValueObject\TaskUuid;
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
            return new JsonResponse(['message' => 'Error while creating task'], 500);
        }
    }

    public function updateStatus(string $id, Request $request): JsonResponse
    {
        try {
            $taskUuid = new TaskUuid($id);
            $data = $request->toArray();
            $newStatus = $data['status'] ?? null;

            if (!isset($newStatus) || !is_string($newStatus) || trim($newStatus) === '') {
                return new JsonResponse(
                    ['error' => 'Missing or invalid required field: status (must be non-empty string)'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $statusEnum = TaskStatus::tryFrom(strtolower($newStatus));
            if ($statusEnum === null) {
                $allowed = implode(', ', array_column(TaskStatus::cases(), 'value'));
                throw new \InvalidArgumentException("Invalid status value provided: '{$newStatus}'. Allowed values are: {$allowed}");
            }

            $taskDTO = $this->taskService->updateTaskStatus($taskUuid, $statusEnum);

            return new JsonResponse($taskDTO);

        } catch (\JsonException $e) {
            return new JsonResponse(
                ['error' => 'Invalid JSON payload: ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        } catch (TaskNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'Invalid input: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => 'Update rejected: ' . $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
            return new JsonResponse(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignTask(string $id, Request $request): JsonResponse
    {
        try {
            $taskUuid = new TaskUuid($id);
            $data = $request->toArray();

            if (!array_key_exists('assigneeId', $data)) {
                return new JsonResponse(
                    ['error' => 'Missing required field: assigneeId (can be null to unassign)'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            $assigneeIdValue = $data['assigneeId'];

            if ($assigneeIdValue !== null && !is_string($assigneeIdValue)) {
                return new JsonResponse(
                    ['error' => 'Invalid assigneeId field: must be null or a string UUID'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $assigneeUuid = null;
            if ($assigneeIdValue !== null) {
                if (!is_string($assigneeIdValue)) {
                    throw new \InvalidArgumentException('Invalid assigneeId field: must be null or a string UUID');
                }
                try {
                    $assigneeUuid = new UserUuid($assigneeIdValue);
                } catch (\InvalidArgumentException $e) {
                    throw new \InvalidArgumentException("Invalid assignee UUID format provided: '{$assigneeIdValue}'.");
                }
            }


            $taskDTO = $this->taskService->assignTask($taskUuid, $assigneeUuid);

            return new JsonResponse($taskDTO);

        } catch (\JsonException $e) {
            return new JsonResponse(
                ['error' => 'Invalid JSON payload: ' . $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        } catch (TaskNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => 'Invalid input: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => 'Assignment rejected: ' . $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY); // Or 409
        } catch (\Throwable $e) {
            error_log(__METHOD__ . ': ' . $e->getMessage());
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
