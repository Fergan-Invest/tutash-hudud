<?php

namespace App\Http\Controllers;

use App\Models\Mahalla;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MahallaController extends Controller
{
    public function store(Request $request, AuditLogger $auditLogger)
    {
        $this->authorize('create', Mahalla::class);

        $data = $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mahallas')->where('district_id', $request->integer('district_id')),
            ],
        ]);

        $mahalla = Mahalla::create($data);
        $auditLogger->log($mahalla, 'mahalla_created', [], $mahalla->toArray(), $request);

        return redirect()
            ->route('addresses.show', ['district' => $mahalla->district_id, 'mahalla_id' => $mahalla->id])
            ->with('success', 'MFY qo‘shildi.');
    }

    public function update(Request $request, Mahalla $mahalla, AuditLogger $auditLogger)
    {
        $this->authorize('update', $mahalla);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mahallas')
                    ->where('district_id', $mahalla->district_id)
                    ->ignore($mahalla->id),
            ],
        ]);

        $oldValues = $mahalla->toArray();
        $mahalla->update($data);
        $auditLogger->log($mahalla, 'mahalla_updated', $oldValues, $mahalla->fresh()->toArray(), $request);

        return redirect()
            ->route('addresses.show', ['district' => $mahalla->district_id, 'mahalla_id' => $mahalla->id])
            ->with('success', 'MFY ma’lumoti yangilandi.');
    }
}
