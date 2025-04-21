<?php

namespace App\Repository\Task;

use App\Entity\Task;
use App\Enum\TaskStatus;
use App\ValueObject\TaskUuid;
use App\ValueObject\UserUuid;

interface TaskRepositoryInterface
{
    /**
     * @return Task[]
     */
    public function findAll(?TaskStatus $status = null, ?UserUuid $assigneeId = null): array;

    public function save(Task $task): void;

    public function findById(TaskUuid $taskUuid): ?Task;
}
