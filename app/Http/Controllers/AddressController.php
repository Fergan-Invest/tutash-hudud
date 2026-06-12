<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Mahalla;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $districts = District::query()
            ->withCount(['mahallas', 'streets'])
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->orderBy('name')
            ->get();

        return view('addresses.index', [
            'districts' => $districts,
            'totalDistricts' => District::count(),
            'totalMahallas' => Mahalla::count(),
            'totalStreets' => \App\Models\Street::count(),
        ]);
    }

    public function show(District $district)
    {
        return view('addresses.show', [
            'district' => $district->loadCount(['mahallas', 'streets']),
            'mahallas' => $district->mahallas()
                ->withCount('streets')
                ->orderBy('name')
                ->paginate(50),
        ]);
    }
}
