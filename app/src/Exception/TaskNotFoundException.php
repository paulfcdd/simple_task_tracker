<?php

declare(strict_types=1);

namespace App\Exception;

class TaskNotFoundException extends \RuntimeException
{
    public function __construct(string $taskId)
    {
        parent::__construct("Task with ID '{$taskId}' not found.");
    }
}
