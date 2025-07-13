<?php

namespace Tests\Fakes;

class FakeJobModel
{
    public function add(
        string $jobClass,
        string $payload,
        ?string $queue,
        ?string $classHash,
        ?string $availableAt,
        ?string $payloadHash,
        ?int $reservedTimeoutSeconds,
        ?int $maxAttempts,
        ?int $retryDelaySeconds,
        ?string $trace = null
    ) {
        // Será mockado nos testes
    }
}
