<?php

namespace Zoomyboy\LaravelNami\Exceptions;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

abstract class HttpException extends Exception
{
    /** @var array<string, mixed> */
    private array $data;
    /** @var array<string, mixed> */
    private array $response;
    private string $requestUrl;

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function request(string $url, ?array $data = []): self
    {
        $this->requestUrl = $url;
        $this->data = $data;

        return $this;
    }

    /**
     * @param array<string, mixed> $response
     */
    public function response(array $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function render(): void
    {
        throw ValidationException::withMessages(['id' => 'Unbekannter Fehler']);
    }

    public function report(): void
    {
        \Log::error($this->getMessage(), [
            'requestUrl' => $this->requestUrl,
            'data' => $this->data,
            'response' => json_encode($this->response),
        ]);
    }

    public function outputToConsole(Command $command): void
    {
        $command->info('Request URL: '.$this->requestUrl);
        $command->info('response: '.json_encode($this->response));
        $command->info($this->getMessage());
    }
}
