<?php

namespace App\Modules;

interface ModuleDefinition
{
    public function code(): string;

    public function name(): string;

    public function description(): string;

    public function routeFile(): string;

    /**
     * @return array{title: string, icon: string, route: string}
     */
    public function navigation(): array;
}
