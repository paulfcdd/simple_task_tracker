<?php

declare(strict_types=1);

namespace App\Http;

readonly class JsonResponse
{
    public function __construct(
        private mixed $data = null,
        private int   $status = 200,
        private array $headers = ['Content-Type' => 'application/json']
    ) {}

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
