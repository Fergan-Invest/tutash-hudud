<?php

namespace App\Http\Controllers;

use App\Models\Mahalla;
use App\Models\Street;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StreetController extends Controller
{
    public function store(Request $request, AuditLogger $auditLogger)
    {
        $this->authorize('create', Street::class);

        $data = $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'mahalla_id' => ['required', 'integer', 'exists:mahallas,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['kocha', 'shohkocha', 'tor_kocha', 'berk_kocha'])],
        ]);

        if ($request->user()->isTuman() && (int) $data['district_id'] !== (int) $request->user()->district_id) {
            abort(403);
        }

        $mahalla = Mahalla::where('id', $data['mahalla_id'])
            ->where('district_id', $data['district_id'])
            ->firstOrFail();

        $street = Street::firstOrCreate(
            ['mahalla_id' => $mahalla->id, 'name' => $data['name'], 'type' => $data['type']],
            [
                'district_id' => $data['district_id'],
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        $auditLogger->log($street, $street->wasRecentlyCreated ? 'street_created' : 'street_reused', [], $street->toArray(), $request);

        if ($request->expectsJson()) {
            return response()->json($street);
        }

        return back()->with('success', 'Ko‘cha saqlandi.')->withInput(['street_id' => $street->id]);
    }
}
