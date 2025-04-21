<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\TaskStatus;
use App\ValueObject\TaskUuid;
use App\ValueObject\UserUuid;
use DateTimeImmutable;
use DomainException;
use JsonSerializable;

final class Task implements JsonSerializable
{
    public function __construct(
        private readonly TaskUuid $id,
        private string $title,
        private ?string $description,
        private TaskStatus $status = TaskStatus::TODO,
        private ?UserUuid $assigneeId = null,
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {}

    public function updateStatus(TaskStatus $newStatus): void
    {
        if ($this->status === TaskStatus::DONE && $newStatus !== TaskStatus::DONE) {
            throw new DomainException('Finished tasks cannot be reopened.');
        }
        $this->status = $newStatus;
    }

    public function assignTo(?int $userId): void
    {
        if ($this->status === TaskStatus::DONE) {
            throw new DomainException('Cannot assign a completed task.');
        }
        $this->assigneeId = $userId;
    }

    public function updateDetails(string $title, ?string $description): void
    {
        $this->title       = $title;
        $this->description = $description;
    }

    public function getId(): TaskUuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function getAssigneeId(): ?int
    {
        return $this->assigneeId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function jsonSerialize(): array
    {
        return [
            'id'         => (string) $this->id,
            'title'      => $this->title,
            'description'=> $this->description,
            'status'     => $this->status->value,
            'assigneeId' => $this->assigneeId,
            'createdAt'  => $this->createdAt->format(DATE_ATOM),
        ];
    }

    public static function fromDatabase(
        TaskUuid $id,
        string $title,
        ?string $description,
        TaskStatus $status,
        ?UserUuid $assigneeId,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $title, $description, $status, $assigneeId, $createdAt);
    }
}
