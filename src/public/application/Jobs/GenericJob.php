<?php

namespace App\Jobs;

use App\Libraries\Queue\Traits\Dispatchable;
use ReflectionClass;
use ReflectionProperty;

abstract class GenericJob
{
    use Dispatchable;

    public $attempts = 0;
    /**
     * Caso der erro no job atual, define o tempo em segundos multiplicado pelo número da tentativa de executar o job
     * que será tentado novamente
     * @var int
     */
    protected $retryDelaySeconds = 30;
    /**
     * Número máximo de tentativas de rodar o job até marcar como falho
     * @var int
     */
    protected $maxAttempts = 5;
    /**
     * Caso o job fique reservado muito tempo, temos esse tempo limite para tentar processar o mesmo job novamente
     * @var float|int
     */
    protected $reservedTimeoutSeconds = 60 * 5;
    /**
     * Define o nome/prioridade da fila que o job será inserido
     * @var string
     */
    protected $queueName = 'default';
    protected $originalQueueName = 'default';
    /**
     * Define a data que o job pode ser rodado
     * @var null
     */
    protected $availableAt = null;
    protected $driver = null;
    protected $failedAt;
    protected $failedReason;

    public function getFailedAt()
    {
        return $this->failedAt;
    }

    public function setFailedAt($failedAt): self
    {
        $this->failedAt = $failedAt;
        return $this;
    }

    public function getFailedReason()
    {
        return $this->failedReason;
    }

    public function setFailedReason($failedReason): self
    {
        $this->failedReason = $failedReason;
        return $this;
    }

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * @param  int  $attempts
     * @return $this
     */
    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;
        return $this;
    }

    /**
     * @return int
     */
    public function getRetryDelaySeconds(): int
    {
        return $this->retryDelaySeconds;
    }

    /**
     * @param  int  $retryDelaySeconds
     * @return $this
     */
    public function setRetryDelaySeconds(int $retryDelaySeconds): self
    {
        $this->retryDelaySeconds = $retryDelaySeconds;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @param  int  $maxAttempts
     * @return $this
     */
    public function setMaxAttempts(int $maxAttempts): self
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    /**
     * @return float|int
     */
    public function getReservedTimeoutSeconds()
    {
        return $this->reservedTimeoutSeconds;
    }

    /**
     * @param  float|int  $reservedTimeoutSeconds
     * @return $this
     */
    public function setReservedTimeoutSeconds($reservedTimeoutSeconds): self
    {
        $this->reservedTimeoutSeconds = $reservedTimeoutSeconds;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @param  string  $queueName
     * @return $this
     */
    public function setQueueName(string $queueName): self
    {
        $this->queueName = $queueName;
        return $this;
    }

    public function getDelayInSeconds(): int
    {
        $availableAt = $this->getAvailableAt();
        return $availableAt ? strtotime($availableAt) - time() : 0;
    }

    /**
     * @return string|null
     */
    public function getAvailableAt(): ?string
    {
        return $this->availableAt;
    }

    /**
     * @param  string|null  $availableAt
     * @return $this
     */
    public function setAvailableAt(?string $availableAt): self
    {
        $this->availableAt = $availableAt;
        return $this;
    }

    public function markAsHandled(bool $bool = true)
    {
        $this->sentToQueue = $bool;
    }

    public function computeClassHash(): string
    {
        return md5(get_class($this).json_encode($this->getRelevantAttributes()));
    }

    protected function getRelevantAttributes(): array
    {
        $ignore = [
            'availableAt', 'queueName', 'retryDelaySeconds', 'reservedTimeoutSeconds', 'maxAttempts', 'sentToQueue',
            'attempts', 'driver', 'failedReason', 'failedAt'
        ];
        $reflection = new ReflectionClass($this);
        $props = $reflection->getProperties(ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PRIVATE);

        $data = [];

        foreach ($props as $prop) {
            $name = $prop->getName();
            if (!in_array($name, $ignore)) {
                $prop->setAccessible(true);
                $data[$name] = $prop->getValue($this);
            }
        }

        return $data;
    }

    /**
     * @return mixed|null
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param  mixed|null  $driver
     * @return $this
     */
    public function setDriver($driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    public function getOriginalQueueName(): string
    {
        return $this->originalQueueName;
    }

    public function setOriginalQueueName(string $originalQueueName): self
    {
        $this->originalQueueName = $originalQueueName;
        return $this;
    }
}