<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Mahalla;
use App\Models\RegistryRequest;
use App\Models\Street;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isInvest(), 403);

        $districts = District::query()
            ->withCount(['mahallas', 'streets'])
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->orderBy('name')
            ->get();

        return view('addresses.index', [
            'districts' => $districts,
            'totalDistricts' => District::count(),
            'totalMahallas' => Mahalla::count(),
            'totalStreets' => Street::count(),
        ]);
    }

    public function show(Request $request, District $district)
    {
        abort_unless($request->user()->isInvest(), 403);

        [$period, $periodLabel, $dateFrom, $dateTo] = $this->statisticsPeriod($request);

        $registries = RegistryRequest::query()
            ->where('district_id', $district->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at')
            ->get(['id', 'request_number', 'mahalla_id', 'total_area', 'created_at']);

        $mahallaNames = $district->mahallas()->pluck('name', 'id');
        $mahallaStatistics = $mahallaNames->map(function ($name, $id) use ($registries) {
            $items = $registries->where('mahalla_id', $id);

            return [
                'id' => (int) $id,
                'name' => $name,
                'count' => $items->count(),
                'area' => (float) $items->sum(fn ($item) => (float) $item->total_area),
            ];
        })->sortByDesc('count')->values();

        $activeMahallas = $mahallaStatistics->where('count', '>', 0);
        $maxMahallaCount = max(1, (int) $activeMahallas->max('count'));

        $timeline = collect();
        if ($period === 'today') {
            foreach (range(0, 23) as $hour) {
                $items = $registries->filter(fn ($item) => (int) $item->created_at->format('G') === $hour);
                $timeline->push([
                    'label' => sprintf('%02d:00', $hour),
                    'count' => $items->count(),
                    'area' => (float) $items->sum(fn ($item) => (float) $item->total_area),
                ]);
            }
        } else {
            $cursor = $dateFrom->copy()->startOfDay();
            while ($cursor->lte($dateTo) && $timeline->count() < 62) {
                $date = $cursor->toDateString();
                $items = $registries->filter(fn ($item) => $item->created_at->toDateString() === $date);
                $timeline->push([
                    'label' => $cursor->format('d.m'),
                    'count' => $items->count(),
                    'area' => (float) $items->sum(fn ($item) => (float) $item->total_area),
                ]);
                $cursor->addDay();
            }
        }

        $maxTimelineCount = max(1, (int) $timeline->max('count'));

        $selectedMahalla = null;
        if ($request->filled('mahalla_id')) {
            $selectedMahalla = $district->mahallas()
                ->with(['streets' => fn ($query) => $query->orderBy('name')])
                ->findOrFail($request->integer('mahalla_id'));
        }

        return view('addresses.show', [
            'district' => $district->loadCount(['mahallas', 'streets']),
            'mahallas' => $district->mahallas()
                ->withCount('streets')
                ->orderBy('name')
                ->paginate(50)
                ->withQueryString(),
            'selectedMahalla' => $selectedMahalla,
            'streetTypes' => Street::TYPES,
            'statistics' => [
                'period' => $period,
                'period_label' => $periodLabel,
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'count' => $registries->count(),
                'area' => (float) $registries->sum(fn ($item) => (float) $item->total_area),
                'active_mahallas' => $activeMahallas->count(),
                'mahallas' => $activeMahallas,
                'max_mahalla_count' => $maxMahallaCount,
                'timeline' => $timeline,
                'max_timeline_count' => $maxTimelineCount,
                'recent' => $registries->sortByDesc('created_at')->take(8)->values(),
                'mahalla_names' => $mahallaNames,
            ],
        ]);
    }

    private function statisticsPeriod(Request $request): array
    {
        $period = in_array($request->query('period'), ['today', 'week', 'month', 'custom'], true)
            ? $request->query('period')
            : 'today';

        $now = now();

        if ($period === 'week') {
            return [$period, 'Bu hafta', $now->copy()->startOfWeek()->startOfDay(), $now->copy()->endOfDay()];
        }

        if ($period === 'month') {
            return [$period, 'Bu oy', $now->copy()->startOfMonth()->startOfDay(), $now->copy()->endOfDay()];
        }

        if ($period === 'custom') {
            try {
                $from = Carbon::createFromFormat('Y-m-d', (string) $request->query('date_from'))->startOfDay();
                $to = Carbon::createFromFormat('Y-m-d', (string) $request->query('date_to'))->endOfDay();

                if ($from->lte($to) && $from->diffInDays($to) <= 61) {
                    return [$period, $from->format('d.m.Y').' — '.$to->format('d.m.Y'), $from, $to];
                }
            } catch (\Throwable $exception) {
                // Noto'g'ri sana kiritilsa, xavfsiz standart davrga qaytamiz.
            }
        }

        return ['today', 'Bugun', $now->copy()->startOfDay(), $now->copy()->endOfDay()];
    }
}
