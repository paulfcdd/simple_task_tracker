<?php

declare(strict_types=1);

namespace App\Repository\Task;

use App\Entity\Task;
use App\Enum\TaskStatus;
use App\ValueObject\TaskUuid;
use App\ValueObject\UserUuid;

class InMemoryTaskRepository implements TaskRepositoryInterface
{
    private const MOCK_USER_1_UUID_STR = 'a7a4b8f0-5c1a-4f7e-8d3b-9e6c1b9a2e8d';
    private const MOCK_USER_2_UUID_STR = 'f3e9c1a8-6b2d-4c8e-9a1d-0b3a5e7d1c9f';
    private const MOCK_TASK_1_UUID_STR = 'e1d8c5b4-7a3f-4b9d-8e5c-1a9b3d7f0e2a';
    private const MOCK_TASK_2_UUID_STR = 'f0a1b7e6-5d3c-4a8b-9e1d-7c2f5a8b3d1e';
    private const MOCK_TASK_3_UUID_STR = 'c1b2e8f7-6e4d-4b9c-8f2e-8d3a6b9c4e2f';
    private const MOCK_TASK_4_UUID_STR = 'd2c3f9a8-7f5e-4cad-8a3f-9e4b7cad5f3a';
    private const MOCK_TASK_5_UUID_STR = 'e3d4a0b9-8a6f-4dbe-9b4a-0f5c8dbe6a4b';

    /**
     * @var Task[]
     */
    private array $tasks = [];
    private static ?UserUuid $mockUser1Uuid = null;
    private static ?UserUuid $mockUser2Uuid = null;
    private static ?TaskUuid $mockTask1Uuid = null;
    private static ?TaskUuid $mockTask2Uuid = null;
    private static ?TaskUuid $mockTask3Uuid = null;
    private static ?TaskUuid $mockTask4Uuid = null;
    private static ?TaskUuid $mockTask5Uuid = null;

    public function __construct(

    )
    {
        if (self::$mockUser1Uuid === null) {
            self::$mockUser1Uuid = new UserUuid(self::MOCK_USER_1_UUID_STR);
            self::$mockUser2Uuid = new UserUuid(self::MOCK_USER_2_UUID_STR);
            self::$mockTask1Uuid = new TaskUuid(self::MOCK_TASK_1_UUID_STR);
            self::$mockTask2Uuid = new TaskUuid(self::MOCK_TASK_2_UUID_STR);
            self::$mockTask3Uuid = new TaskUuid(self::MOCK_TASK_3_UUID_STR);
            self::$mockTask4Uuid = new TaskUuid(self::MOCK_TASK_4_UUID_STR);
            self::$mockTask5Uuid = new TaskUuid(self::MOCK_TASK_5_UUID_STR);
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

        $task1 = new Task(self::$mockTask1Uuid, 'Design Architecture', 'Define layers and components', TaskStatus::DONE, self::$mockUser1Uuid);
        $task2 = new Task(self::$mockTask2Uuid, 'Implement Controller', 'Create TaskController actions', TaskStatus::IN_PROGRESS, self::$mockUser1Uuid);
        $task3 = new Task(self::$mockTask3Uuid, 'Implement Service', 'Create TaskService logic', TaskStatus::TODO, self::$mockUser2Uuid);
        $task4 = new Task(self::$mockTask4Uuid, 'Write Repository Tests', 'Add tests for repository', TaskStatus::TODO);
        $task5 = new Task(self::$mockTask5Uuid, 'Refactor Validation', 'Improve DTO validation', TaskStatus::IN_PROGRESS, self::$mockUser2Uuid);

        $this->save($task1);
        $this->save($task2);
        $this->save($task3);
        $this->save($task4);
        $this->save($task5);
    }
}
