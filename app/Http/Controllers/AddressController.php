<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Mahalla;
use App\Models\Street;
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
        ]);
    }
}
