<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Incoming\CreateTaskDto;
use App\Dto\Outcoming\TaskDto;
use App\Entity\Task;
use App\Enum\TaskStatus;
use App\Exception\TaskNotFoundException;
use App\Repository\Task\TaskRepositoryInterface;
use App\ValueObject\TaskUuid;
use App\ValueObject\UserUuid;

class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
    )
    {
    }

    public function getCollection(
        ?TaskStatus $status = null,
        ?UserUuid $assigneeId = null
    ): array
    {
        $tasks = $this->repository->findAll($status, $assigneeId);

        return array_map(
            fn(Task $task): TaskDTO => TaskDTO::fromEntity($task),
            $tasks
        );
    }

    public function create(CreateTaskDto $dto): TaskDto
    {
        $task = new Task(
            id: new TaskUuid(),
            title: $dto->title,
            description: $dto->description,
            status: TaskStatus::tryFromString($dto->status),
            assigneeId: $dto->assigneeId,
        );

        $this->repository->save($task);

        return TaskDto::fromEntity($task);
    }

    public function updateTaskStatus(TaskUuid $taskUuid, TaskStatus $status): TaskDTO
    {
        $task = $this->repository->findById($taskUuid);

        if (!$task) {
            throw new TaskNotFoundException($taskUuid->value());
        }

        $task->updateStatus($status);
        $this->repository->save($task);

        return TaskDTO::fromEntity($task);
    }

    public function assignTask(TaskUuid $taskUuid, ?UserUuid $assigneeUuid): TaskDTO
    {
        $task = $this->repository->findById($taskUuid);

        if (!$task) {
            throw new TaskNotFoundException($taskUuid->value());
        }

        $task->assignTo($assigneeUuid);
        $this->repository->save($task);

        return TaskDTO::fromEntity($task);
    }
}
