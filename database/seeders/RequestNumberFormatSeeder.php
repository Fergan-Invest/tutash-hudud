<?php

namespace Database\Seeders;

use App\Models\RegistryRequest;
use App\Services\RequestNumberGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RequestNumberFormatSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(RequestNumberGenerator::class);

        DB::transaction(function () use ($generator) {
            $requests = RegistryRequest::withTrashed()
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'request_number', 'created_at']);

            $rows = $requests->map(fn (RegistryRequest $request) => [
                'id' => $request->id,
                'request_number' => $request->request_number,
                'created_at' => $request->created_at,
            ]);

            $requests->each(function (RegistryRequest $request) {
                RegistryRequest::withTrashed()->whereKey($request->id)->update([
                    'request_number' => '__tmp_request_number_'.$request->id,
                ]);
            });

            $incrementsByYear = [];

            $rows->each(function (array $request) use (&$incrementsByYear, $generator) {
                $year = $generator->yearFromExisting($request['request_number'], $request['created_at']);
                $incrementsByYear[$year] = ($incrementsByYear[$year] ?? 0) + 1;

                RegistryRequest::withTrashed()
                    ->whereKey($request['id'])
                    ->update([
                        'request_number' => $generator->format($year, $incrementsByYear[$year]),
                    ]);
            });
        });
    }
}
