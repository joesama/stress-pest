<?php

namespace Joesama\StressPest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Pest\Stressless\Result\Requests;

trait StressData
{
    use Renderer;

    protected string $table = 'stress_test';

    public function stressTable(): void
    {
        if (! Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->id();
                $table->date('test_date');
                $table->string('test_id');
                $table->string('test_type');
                $table->json('result');
                $table->timestamps();
            });
        }
    }

    public function destroyStressTable(): void
    {
        Schema::dropIfExists($this->table);
    }

    public function getStressData(?string $date = null): Collection
    {
        $now = $date ? Carbon::parse($date) : Carbon::now();

        return $this->sourceData($now)
            ->groupBy('type')
            ->sortBy('created')
            ->map(function ($item, $type) {
                return $item->pluck('result')
                    ->groupBy('duration')->map(function ($result) {
                        return $result->groupBy('concurrency')->map(function ($request) {
                            return $request->map(function ($data) {
                                return [
                                    'duration' => data_get($data, 'request.duration'),
                                    'request' => data_get($data, 'request'),
                                    'endpoint' => data_get($data, 'endpoint'),
                                ];
                            });
                        });
                    });
            });
    }

    public function saveStressData(string $testType, string $testId, string $endpoint, Requests $requests, int $duration = 5, int $concurrent = 1): void
    {
        $now = Carbon::now();

        $this->twoColumn($endpoint, number_format($requests->duration()->med(), 2).' ms');

        DB::table($this->table)->updateOrInsert([
            'test_id' => $testId,
            'test_date' => $now->format('Y-m-d'),
            'test_type' => $testType,
        ], [
            'created_at' => $now,
            'updated_at' => $now,
            'result' => json_encode($this->stressData($endpoint, $requests, $duration, $concurrent)),
        ]);
    }

    protected function stressData(string $endpoint, Requests $request, int $duration = 5, int $concurrent = 1): array
    {
        $ttfb = $request->ttfb()->duration()->med();
        $upload = $request->upload()->duration()->med();
        $download = $request->download()->duration()->med();
        $requestDuration = $request->duration()->med();

        return [
            'duration' => number_format($duration, 2),
            'concurrency' => $concurrent,
            'endpoint' => $endpoint,
            'request' => [
                'success' => $this->getSuccessPercentage($request),
                'duration' => number_format($requestDuration, 2),
                'count' => $request->count(),
                'rate' => number_format($request->rate(), 2),
                'dns' => number_format($this->getDnsTime($request), 2),
                'tls' => number_format($this->getTlsTime($request), 2),
                'ttfb' => [
                    'duration' => number_format($ttfb, 2),
                    'percent' => ($ttfb / $requestDuration) * 100,
                ],
                'download' => [
                    'duration' => number_format($download, 2),
                    'percent' => ($download / $requestDuration) * 100,
                    'count' => number_format($request->download()->data()->count() / 1024, 2),
                    'rate' => number_format($request->download()->data()->rate() / 1024, 2),
                ],
                'upload' => [
                    'duration' => number_format($upload, 2),
                    'percent' => ($upload / $requestDuration) * 100,
                    'count' => number_format($request->upload()->data()->count() / 1024, 2),
                    'rate' => number_format($request->upload()->data()->rate() / 1024, 2),
                ],
            ],
        ];
    }

    protected function getSuccessPercentage(Requests $request): float
    {
        return (float) (($request->count() - $request->failed()->count()) / $request->count()) * 100;
    }

    protected function getTlsTime(Requests $request): float
    {
        return $request->tlsHandshake()->duration()->med();
    }

    protected function getDnsTime(Requests $request): float
    {
        return $request->dnsLookup()->duration()->med();
    }

    /**
     * @return mixed
     */
    protected function sourceData(Carbon $now)
    {
        return DB::table($this->table)->where('test_date', $now->format('Y-m-d'))
            ->orderBy('created_at')
            ->get()
            ->map(function ($data) {
                return [
                    'id' => $data->test_id,
                    'type' => $data->test_type,
                    'date' => Carbon::parse($data->test_date)->format('d-m-Y'),
                    'created' => $data->created_at,
                    'result' => json_decode($data->result, true),
                ];
            });
    }
}