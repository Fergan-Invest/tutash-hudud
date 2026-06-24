<?php

namespace App\Services;

use App\Models\RegistryRequest;

class RequestNumberGenerator
{
    public function next(): string
    {
        $year = now()->format('y');
        $prefix = $this->prefix($year);

        $lastIncrement = RegistryRequest::withTrashed()
            ->where('request_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->get(['request_number'])
            ->map(fn (RegistryRequest $request) => $this->incrementFromNumber($request->request_number, $year))
            ->filter()
            ->max() ?? 0;

        return $this->format($year, $lastIncrement + 1);
    }

    public function format(string $year, int $increment): string
    {
        return $this->prefix($year).str_pad((string) $increment, 4, '0', STR_PAD_LEFT);
    }

    public function yearFromExisting(?string $requestNumber, $createdAt = null): string
    {
        if ($createdAt) {
            return $createdAt->format('y');
        }

        if (preg_match('/^THR-(\d{4})/', (string) $requestNumber, $matches)) {
            return substr($matches[1], -2);
        }

        if (preg_match('/^T\/H-(\d{2})\s+/', (string) $requestNumber, $matches)) {
            return $matches[1];
        }

        return now()->format('y');
    }

    private function prefix(string $year): string
    {
        return 'T/H-'.$year.' ';
    }

    private function incrementFromNumber(string $requestNumber, string $year): ?int
    {
        if (! preg_match('/^T\/H-'.preg_quote($year, '/').'\s+(\d+)$/', $requestNumber, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
