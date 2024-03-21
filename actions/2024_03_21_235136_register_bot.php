<?php

declare(strict_types=1);

use DefStudio\Telegraph\Models\TelegraphBot;
use DragonCode\LaravelActions\Action;

return new class extends Action {
    public function __invoke(): void
    {
        $this->create($this->token(), $this->name());
    }

    protected function create(string $token, string $name): void
    {
        TelegraphBot::create(compact('token', 'name'));
    }

    protected function token(): string
    {
        return config('services.telegram.token');
    }

    protected function name(): string
    {
        return config('services.telegram.name');
    }
};
