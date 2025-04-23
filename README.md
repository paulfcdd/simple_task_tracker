# Task Tracker API - Test Assignment

This document outlines the architecture and design decisions for the Task Tracker API service developed as part of the test assignment.

## Architectural Overview

The service implements a **Layered Architecture**, drawing inspiration from Clean Architecture and Hexagonal Architecture principles. This approach emphasizes separation of concerns, making the application more testable, maintainable, and adaptable to change.

The main layers are:

1.  **Presentation Layer (Infrastructure - HTTP):**
    * **Components:** `TaskController`, Symfony Routing (`routes.yaml`), Symfony HttpFoundation (`Request`, `JsonResponse`).
    * **Responsibility:** Acts as the entry point for external interactions (HTTP API). It receives HTTP requests, routes them to the appropriate controller action, handles request/response formats (JSON), parses input (using Serializer), triggers input validation (using Validator), calls the Application Layer service, and formats the result into an HTTP response. It contains no business logic.

2.  **Application Layer:**
    * **Components:** `TaskService`, Data Transfer Objects (`CreateTaskDto`, `TaskDTO`), Application-level Exceptions (`TaskNotFoundException`). Interfaces like `SerializerInterface` and `ValidatorInterface` are *used* here (or in the Controller acting on its behalf) but *configured* in Infrastructure.
    * **Responsibility:** Orchestrates the application's use cases (e.g., "Create Task", "List Tasks"). It contains application-specific logic, coordinates domain objects (Entities, Value Objects) and Repository interfaces, and performs data transformations between layers (e.g., Entity to DTO). It remains independent of UI details and specific infrastructure implementations (like the database or HTTP).

3.  **Domain Layer:**
    * **Components:** `Task` Entity, Value Objects (`Uuid`, `UserUuid`, `TaskUuid`), Enums (`TaskStatus`), `TaskRepositoryInterface`. Potentially Domain Exceptions.
    * **Responsibility:** Represents the core business logic, rules, and data structures of the application. Contains Entities with state and behavior (methods enforcing invariants), Value Objects ensuring data integrity, and interfaces defining contracts for infrastructure dependencies (like data persistence). This layer has **zero dependencies** on any outer layer (Application, Presentation, Infrastructure).

4.  **Infrastructure Layer:**
    * **Components:** `InMemoryTaskRepository` (Persistence implementation), `services.yaml` (DI Configuration, Serializer/Validator setup), external libraries (`Ramsey\Uuid`), framework components (Symfony DI, HttpKernel, Routing, etc.). Future: `DoctrineTaskRepository`.
    * **Responsibility:** Implements interfaces defined by inner layers (primarily Domain) and handles all interactions with external concerns and technical details – databases, caches, framework specifics, third-party libraries, file systems, network calls, etc. Contains the concrete implementations of abstractions.

**Component Interaction Flow Example (`POST /tasks`):**

HTTP Request -> Routing -> `TaskController` -> `Serializer` (deserialize) -> `CreateTaskDto` -> `Validator` (validate) -> `TaskService` -> Create `Task` Entity & `TaskUuid` VO -> `TaskRepositoryInterface` (`save`) -> `InMemoryTaskRepository` (implements interface, saves to array) -> `TaskDTO` (created by Service) -> `TaskController` -> `JsonResponse` -> HTTP Response

## Installation
1. Run `docker compose up -d --build` in project folder
2. Run `docker compose exec app bash` to enter app container
3. Rub `composer install` to install dependencies

## Usage
1. Import Postman collection `Task Management API.postman_collection.json` to Your Postman app and make requests 
**ASCII Diagram:**

```ascii
+-------------------------------------------------------------+
| Presentation Layer (HTTP)                                   |
| (TaskController, Routing, Request/Response)                 |
| * Responsibility: Handle HTTP, I/O, routing, data format.   |
+-----------------------↓-------------------------------------+
                        | Depends on (Uses)
+-----------------------↓-------------------------------------+
| Application Layer                                           |
| (TaskService, DTOs - CreateTaskDto/TaskDTO)                 |
| * Responsibility: Orchestrate use cases, app logic.         |
+-----------------------↓-------------------------------------+
                        | Depends on (Uses)
+-----------------------↓-------------------------------------+
| Domain Layer                                                |
| (Task Entity, Uuid/UserUuid/TaskUuid VOs, TaskStatus Enum,  |
|  TaskRepositoryInterface)                                   |
| * Responsibility: Core business logic, entities, rules.     |
| * NO dependencies on outer layers.                          |
+-----------------------↑-------------------------------------+
                        | Implements (DI provides concrete)
+-----------------------↑-------------------------------------+
| Infrastructure Layer                                        |
| (InMemoryTaskRepository, Serializer/Validator Config, DI,   |
|  DoctrineTaskRepository [Future])                           |
| * Responsibility: Implement interfaces, interact with       |
|   external systems (DB, framework tools, libraries).        |
+-------------------------------------------------------------+

Design Decisions & Pattern Justification
The chosen architecture and patterns aim for a clean, maintainable, testable, and extensible system adhering to SOLID principles.

Layered Architecture:

Justification: Provides strong Separation of Concerns. Changes in UI or database technology minimally impact the core Domain or Application logic. Each layer can be developed, tested, and maintained more independently.
Repository Pattern:

Justification: Decouples the Application and Domain layers from the specifics of data storage (InMemoryTaskRepository vs. a future DoctrineTaskRepository). This Abstraction allows persistence mechanisms to be swapped easily (as required by the extensibility plan) and significantly improves Testability by allowing TaskRepositoryInterface to be mocked in tests for the TaskService.
Dependency Injection (DI) / Inversion of Control (IoC):

Justification: Managed via the Symfony DI component (services.yaml). Promotes Loose Coupling – classes depend on abstractions (interfaces like TaskRepositoryInterface, SerializerInterface, ValidatorInterface) rather than concrete implementations. This enhances Flexibility (swap implementations via config), Testability (inject mocks), and Maintainability. Follows the Dependency Inversion Principle (SOLID 'D').
DTO (Data Transfer Object):

Justification: Used for input (CreateTaskDto) and output (TaskDTO). Input DTOs provide a clear structure for incoming data and a place to attach validation rules (#[Assert]). Output DTOs define a stable contract for the API response, preventing the exposure of internal Entity details and decoupling the API structure from the Domain model.
Value Object:

Justification: Classes like Uuid, UserUuid, TaskUuid, and the TaskStatus Enum encapsulate primitive values with domain meaning. They improve Type Safety, enforce Immutability (readonly), ensure validity (e.g., UUID format via fromString), prevent primitive obsession, and make the domain language clearer in the code.
Service Layer:

Justification: TaskService encapsulates the logic for application use cases. This keeps Controllers Thin, focused only on HTTP concerns. It promotes Reusability of the application workflows (e.g., the same service could potentially be called by a CLI command).
Entity:

Justification: The Task class represents the core domain concept. It holds state and can encapsulate related business logic and invariants (e.g., logic within updateStatus or assignTo methods to ensure valid state transitions).
Static Factory Method:

Justification: Used in the Uuid base class (::fromString, ::generate). Provides named constructors for clarity, allows validation logic (fromString) during instantiation, and enables returning static for correct type instantiation in subclasses (UserUuid::fromString returns UserUuid).
