<?php

namespace Joesama\StressPest;

use Pest\Stressless\Factory;
use Pest\Stressless\Result\Requests;
use function Pest\Stressless\stress;

trait StressCase
{
    use StressConfig;
    use StressData;
    use StressPrinter;

    protected Factory $result;

    protected ?Requests $requests = null;

    /**
     * @param array $endpoint
     * @param string|null $id
     * @return StressCase
     */
    public function executeSmoke(array $endpoint, ?string $id = null): static
    {
        $type = TestType::smokeTest->name;

        $this->executeTest($endpoint, $type, $id);

        return $this;
    }

    /**
     * @param array $endpoint
     * @param string|null $id
     * @return StressCase
     */
    public function executeAverage(array $endpoint, ?string $id = null): static
    {
        $type = TestType::averageTests->name;

        $this->executeTest($endpoint, $type, $id);

        return $this;
    }

    /**
     * @param array $endpoint
     * @param string|null $id
     * @return StressCase
     */
    public function executeStress(array $endpoint, ?string $id = null): static
    {
        $type = TestType::stressTest->name;

        $this->executeTest($endpoint, $type, $id);

        return $this;
    }

    /**
     * @param string $url
     * @param array $payload
     * @param string|null $method
     * @return StressCase
     */
    public function stressResult(string $url, array $payload = [], ?string $method = 'GET'): static
    {
        $result = stress($this->getFullUrl($url));

        $result = match (strtolower($method)) {
            'post' => $result->post($payload),
            'put' => $result->put($payload),
            'patch' => $result->patch($payload),
            'delete' => $result->delete(),
            default => $result->get(),
        };

        $this->result = $result->concurrently($this->getConcurrent())
            ->for($this->getDuration())
            ->seconds();

        $this->requests = $this->result->requests();

        return $this;
    }

    /**
     * @return void
     */
    public function useStressReporting(): void
    {
        if ($this->configs('enable')) {
            $this->stressTable();
        }
    }

    /**
     * @return Factory
     */
    public function getResult(): Factory
    {
        return $this->result;
    }

    /**
     * @return Requests
     */
    public function getRequest(): Requests
    {
        return $this->requests;
    }

    /**
     * @return $this
     */
    public function assertResult(): static
    {
        $request = $this->getRequest();

        expect($this->getSuccessPercentage($request))->toBe((float) $this->configs('success'))
            ->and($this->getTlsTime($request))->toBeLessThan((float) $this->configs('tls'))
            ->and($this->getDnsTime($request))->toBeLessThan((float) $this->configs('dns'));

        return $this;
    }

    /**
     * Capture result
     */
    public function captureResult(string $testType, ?string $testId = null): void
    {
        $duration = $this->getDuration();
        $concurrency = $this->getConcurrent();
        $testId = implode('_', [($testId ?? uniqid($testType)), $duration, $concurrency]);

        if ($this->configs('enable')) {
            $this->saveStressData(
                $testType,
                $testId,
                $this->getResult()->url(),
                $this->getRequest(),
                $duration,
                $concurrency
            );
        }
    }

    /**
     * Generate PDF Report
     */
    public function generatePdfReport(): void
    {
        expect($this->exportPdf($this->getStressData(now()->format('Y-m-d'))))->toBeTrue();
    }

    protected function getFullUrl(string $uri): string
    {
        return $this->configs('endpoint').'/'.str($uri)->replaceFirst('/', '');
    }

    /**
     * @param  mixed  $routes
     */
    protected function getUrlRoutes(array $routes): array
    {
        [$url, $method, $payload] = match (count($routes)) {
            1 => [current($routes), 'GET', []],
            2 => [current($routes), last($routes), []],
            default => $routes
        };

        return [$url, $method, $payload];
    }

    /**
     * @param array $endpoints
     * @param string $type
     * @param string|null $id
     * @return void
     */
    protected function executeTest(array $endpoints, string $type, ?string $id): void
    {
        foreach ($endpoints as $uriId => $routes) {
            [$url, $method, $payload] = $this->getUrlRoutes($routes);

            $testId = implode('_', [$type, $id, $uriId]);

            $this->renderHeader($testId);

            $this->stressResult($url, $payload, $method)
                ->assertResult()
                ->captureResult($type, $testId);
        }
    }
}