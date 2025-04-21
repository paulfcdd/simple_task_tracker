<?php

declare(strict_types=1);

namespace App\Dto\Incoming;

use App\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;

class CreateTaskDto
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $title;

    #[Assert\Length(max: 1000)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: 'getValidStatuses', message: 'Invalid status value. Allowed values are: {{ choices }}')]
    public string $status;

    #[Assert\PositiveOrZero]
    public ?int $assigneeId = null;

    public static function getValidStatuses(): array
    {
        return array_column(TaskStatus::cases(), 'value');
    }
}
