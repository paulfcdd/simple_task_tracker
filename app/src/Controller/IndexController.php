<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'index')]
class IndexController
{
    public function __invoke(): void
    {
        echo 'Hello World!';
    }
}
