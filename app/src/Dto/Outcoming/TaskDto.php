<?php

declare(strict_types=1);

namespace App\Dto\Outcoming;

use App\Entity\Task;

class TaskDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $status,
        public readonly ?string $assigneeId,
        public readonly \DateTimeImmutable $createdAt
    ) {}

    public static function fromEntity(Task $task): self
    {
        return new self(
            $task->getId()->value(),
            $task->getTitle(),
            $task->getDescription(),
            $task->getStatus()->value,
            $task->getAssigneeId()?->value(),
            $task->getCreatedAt()
        );
    }
}
