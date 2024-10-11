<?php

declare(strict_types=1);

namespace Shin1x1\OpenTelemetry\Auto\Db\Pdo;

use JsonSerializable;

final class BoundParameters implements JsonSerializable
{
    /**
     * @param array<array-key, mixed> $parameters
     */
    public function __construct(private array $parameters = []) {}

    public function add(string|int $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    /**
     * @return list<mixed>
     */
    public function toArray(): array
    {
        return array_values($this->parameters);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
