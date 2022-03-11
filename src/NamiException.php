<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class NamiException extends \Exception
{
    private array $data;
    private array $response;
    private string $requestUrl;

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function request(string $url, ?array $data = []): self
    {
        $this->requestUrl = $url;
        $this->data = $data;

        return $this;
    }

    public function response(array $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function report(): void
    {
        \Log::error($this->getMessage(), [
            'requestUrl' => $this->requestUrl,
            'data' => $this->data,
            'response' => json_encode($this->response),
        ]);

        throw ValidationException::withMessages(['id' => 'Unbekannter Fehler']);
    }

    public function outputToConsole(Command $command): void
    {
        $command->info('Request URL: '.$this->requestUrl);
        $command->info('response: '.json_encode($this->response));
        $command->info($this->getMessage());
    }
}
