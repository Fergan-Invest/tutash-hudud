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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('streets')
                    ->where('mahalla_id', $request->integer('mahalla_id'))
                    ->where('type', $request->input('type')),
            ],
            'type' => ['required', Rule::in(array_keys(Street::TYPES))],
        ]);

        if ($request->user()->isTuman() && (int) $data['district_id'] !== (int) $request->user()->district_id) {
            abort(403, 'Tuman foydalanuvchisi faqat o‘z hududiga ko‘cha qo‘sha oladi.');
        }

        $mahalla = Mahalla::where('id', $data['mahalla_id'])
            ->where('district_id', $data['district_id'])
            ->firstOrFail();

        $street = Street::create([
            ...$data,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $auditLogger->log($street, 'street_created', [], $street->toArray(), $request);

        if ($request->expectsJson()) {
            return response()->json($street);
        }

        return redirect()
            ->route('addresses.show', ['district' => $street->district_id, 'mahalla_id' => $street->mahalla_id])
            ->with('success', 'Ko‘cha qo‘shildi.');
    }

    public function update(Request $request, Street $street, AuditLogger $auditLogger)
    {
        $this->authorize('update', $street);

        $data = $request->validate([
            'mahalla_id' => ['required', 'integer', 'exists:mahallas,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('streets')
                    ->where('mahalla_id', $request->integer('mahalla_id'))
                    ->where('type', $request->input('type'))
                    ->ignore($street->id),
            ],
            'type' => ['required', Rule::in(array_keys(Street::TYPES))],
        ]);

        $mahalla = Mahalla::where('id', $data['mahalla_id'])
            ->where('district_id', $street->district_id)
            ->firstOrFail();

        $oldValues = $street->toArray();
        $street->update([
            ...$data,
            'mahalla_id' => $mahalla->id,
            'updated_by' => $request->user()->id,
        ]);
        $auditLogger->log($street, 'street_updated', $oldValues, $street->fresh()->toArray(), $request);

        return redirect()
            ->route('addresses.show', ['district' => $street->district_id, 'mahalla_id' => $street->mahalla_id])
            ->with('success', 'Ko‘cha ma’lumoti yangilandi.');
    }
}
