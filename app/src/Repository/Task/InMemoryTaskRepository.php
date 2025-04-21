<?php

declare(strict_types=1);

namespace App\Repository\Task;

use App\Entity\Task;
use App\Enum\TaskStatus;
use App\ValueObject\TaskUuid;
use App\ValueObject\UserUuid;
use Ramsey\Uuid\Uuid;

class InMemoryTaskRepository implements TaskRepositoryInterface
{

    /**
     * @var Task[]
     */
    private array $tasks = [];
    private static ?UserUuid $mockUser1Uuid = null;
    private static ?UserUuid $mockUser2Uuid = null;
    private static ?TaskUuid $mockTask1Uuid = null;
    public function __construct(

    )
    {
        if (self::$mockUser1Uuid === null) {
            self::$mockUser1Uuid = new UserUuid();
            self::$mockUser2Uuid = new UserUuid();
            self::$mockTask1Uuid = new TaskUuid();
        }
        $this->seedWithMockData();
    }

    public function findAll(
        ?TaskStatus $status = null,
        ?UserUuid $assigneeId = null
    ): array
    {
        $filteredTasks = $this->tasks;

        if ($status !== null) {
            $filteredTasks = array_filter(
                $filteredTasks,
                fn(Task $task) => $task->getStatus() === $status
            );
        }

        if ($assigneeId !== null) {
            $filteredTasks = array_filter(
                $filteredTasks,
                fn(Task $task) => $task->getAssigneeId() !== null && $task->getAssigneeId()->equals($assigneeId)
            );
        }

        return array_values(array_map(fn(Task $task) => clone $task, $filteredTasks));
    }

    public function save(Task $task): void
    {
        $this->tasks[(string) $task->getId()] = clone $task;
    }

    public function findById(TaskUuid $taskUuid): ?Task
    {
        $task = $this->tasks[$taskUuid->value()] ?? null;

        return $task ? clone $task : null;
    }

    private function seedWithMockData(): void
    {
        // Clear any previous state (though usually empty on instantiation)
        $this->tasks = [];

        // Create sample tasks using the static UUIDs for predictability
        $task1 = new Task(self::$mockTask1Uuid, 'Design Architecture', 'Define layers and components', TaskStatus::DONE, self::$mockUser1Uuid);
        $task2 = new Task(new TaskUuid(), 'Implement Controller', 'Create TaskController actions', TaskStatus::IN_PROGRESS, self::$mockUser1Uuid);
        $task3 = new Task(new TaskUuid(), 'Implement Service', 'Create TaskService logic', TaskStatus::TODO, self::$mockUser2Uuid);
        $task4 = new Task(new TaskUuid(), 'Write Repository Tests', 'Add tests for repository', TaskStatus::TODO);
        $task5 = new Task(new TaskUuid(), 'Refactor Validation', 'Improve DTO validation', TaskStatus::IN_PROGRESS, self::$mockUser2Uuid);

        $this->save($task1);
        $this->save($task2);
        $this->save($task3);
        $this->save($task4);
        $this->save($task5);
    }
}
