<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistryRequestFormRequest;
use App\Models\District;
use App\Models\Mahalla;
use App\Models\RegistryRequest;
use App\Models\RequestFile;
use App\Models\RequestImage;
use App\Models\Street;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', RegistryRequest::class);

        $query = RegistryRequest::with(['district', 'mahalla', 'street', 'creator'])
            ->latest();

        if ($request->user()->isTuman()) {
            $query->where('district_id', $request->user()->district_id);
        }

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('street_type'), fn ($q) => $q->where('street_type', $request->street_type));
        $query->when($request->filled('district_id'), fn ($q) => $q->where('district_id', $request->district_id));
        $query->when($request->filled('mahalla_id'), fn ($q) => $q->where('mahalla_id', $request->mahalla_id));
        $query->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from));
        $query->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to));
        $query->when($request->filled('q'), function ($q) use ($request) {
            $term = '%'.$request->q.'%';
            $q->where(function ($inner) use ($term) {
                $inner->where('request_number', 'like', $term)
                    ->orWhere('building_cadastr_number', 'like', $term)
                    ->orWhere('owner_stir_pinfl', 'like', $term)
                    ->orWhere('owner_name', 'like', $term);
            });
        });

        return view('requests.index', [
            'requests' => $query->paginate(15)->withQueryString(),
            'districts' => District::orderBy('name')->get(),
            'mahallas' => Mahalla::orderBy('name')->get(),
            'statuses' => RegistryRequest::STATUSES,
            'streetTypes' => RegistryRequest::STREET_TYPES,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', RegistryRequest::class);

        return view('requests.form', $this->formData($request));
    }

    public function store(RegistryRequestFormRequest $request, AuditLogger $auditLogger)
    {
        $registryRequest = DB::transaction(function () use ($request, $auditLogger) {
            $data = $this->validatedPayload($request);
            $data['request_number'] = $this->nextRequestNumber();
            $data['status'] = 'submitted';
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;

            $registryRequest = RegistryRequest::create($data);
            $this->storeMedia($request, $registryRequest);
            $auditLogger->log($registryRequest, 'created', [], $registryRequest->fresh()->toArray(), $request);

            return $registryRequest;
        });

        return redirect()->route('requests.show', $registryRequest)->with('success', 'Ariza saqlandi.');
    }

    public function validateForm(RegistryRequestFormRequest $request, ?RegistryRequest $registryRequest = null)
    {
        return response()->json([
            'ok' => true,
            'message' => 'Maʼlumotlar to‘g‘ri.',
        ]);
    }

    public function show(RegistryRequest $registryRequest)
    {
        $this->authorize('view', $registryRequest);

        return view('requests.show', [
            'requestItem' => $registryRequest->load(['district', 'mahalla', 'street', 'creator', 'images', 'files', 'audits.user']),
        ]);
    }

    public function edit(Request $request, RegistryRequest $registryRequest)
    {
        $this->authorize('update', $registryRequest);

        return view('requests.form', $this->formData($request, $registryRequest));
    }

    public function keepAlive(Request $request)
    {
        $request->session()->put('last_keep_alive_at', now()->timestamp);

        return response()->json([
            'ok' => true,
            'csrf_token' => csrf_token(),
            'session_lifetime' => (int) config('session.lifetime'),
            'server_time' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function update(RegistryRequestFormRequest $request, RegistryRequest $registryRequest, AuditLogger $auditLogger)
    {
        DB::transaction(function () use ($request, $registryRequest, $auditLogger) {
            $old = $registryRequest->getOriginal();
            $data = $this->validatedPayload($request);
            $data['updated_by'] = $request->user()->id;

            $registryRequest->update($data);
            $this->storeMedia($request, $registryRequest);
            $auditLogger->log($registryRequest, 'updated', $old, $registryRequest->fresh()->toArray(), $request);
        });

        return redirect()->route('requests.show', $registryRequest)->with('success', 'Ariza yangilandi.');
    }

    public function destroy(Request $request, RegistryRequest $registryRequest, AuditLogger $auditLogger)
    {
        $this->authorize('delete', $registryRequest);

        DB::transaction(function () use ($request, $registryRequest, $auditLogger) {
            $old = $registryRequest->toArray();
            $auditLogger->log($registryRequest, 'deleted', $old, [], $request);
            $registryRequest->delete();
        });

        return redirect()->route('requests.index')->with('success', 'Ariza o‘chirildi.');
    }

    public function checkCadastreRestriction(Request $request)
    {
        $data = $request->validate([
            'cadastre_number' => ['required', 'string', 'max:100', 'regex:/^\d{2}:\d{2}:\d{2}:\d{2}:\d{2}:\d{4}([\/:].+)?$/'],
        ]);

        $existing = RegistryRequest::query()
            ->where('building_cadastr_number', $data['cadastre_number'])
            ->whereIn('status', ['submitted', 'in_review', 'approved'])
            ->latest()
            ->first();

        return response()->json([
            'restricted' => filled($existing),
            'message' => filled($existing)
                ? "Bu kadastr raqami {$existing->request_number} arizasida ishlatilgan. Holati: ".$this->statusLabels()[$existing->status].'.'
                : 'Kadastr raqami bo‘yicha cheklov topilmadi.',
        ]);
    }

    private function statusLabels(): array
    {
        return [
            'draft' => 'Qoralama',
            'submitted' => 'Yuborilgan',
            'in_review' => 'Ko‘rib chiqilmoqda',
            'approved' => 'Tasdiqlangan',
            'rejected' => 'Rad etilgan',
        ];
    }

    private function formData(Request $request, ?RegistryRequest $registryRequest = null): array
    {
        $districts = District::orderBy('name')->get();
        $mahallas = Mahalla::orderBy('name')->get();
        $streets = Street::orderBy('name')->get();

        if ($request->user()->isTuman()) {
            $districts = $districts->where('id', $request->user()->district_id)->values();
            $mahallas = $mahallas->where('district_id', $request->user()->district_id)->values();
            $streets = $streets->where('district_id', $request->user()->district_id)->values();
        }

        return [
            'requestItem' => $registryRequest,
            'districts' => $districts,
            'mahallas' => $mahallas,
            'streets' => $streets,
            'streetTypes' => RegistryRequest::STREET_TYPES,
            'usagePurposes' => ['savdo' => 'Savdo', 'xizmat' => 'Xizmat', 'umumiy_ovqatlanish' => 'Umumiy ovqatlanish', 'boshqa' => 'Boshqa'],
            'facilities' => ['soyabon', 'stol_stul', 'vitrina', 'yengil_konstruksiya', 'reklama'],
        ];
    }

    private function validatedPayload(RegistryRequestFormRequest $request): array
    {
        $data = $request->safe()->except(['images', 'act_file', 'design_code_file', 'qayta_organish_akti_file']);
        $data['polygon_coordinates'] = json_decode($request->input('polygon_coordinates'), true);
        $data['adjacent_facilities'] = $request->input('adjacent_facilities', []);

        return $data;
    }

    private function storeMedia(RegistryRequestFormRequest $request, RegistryRequest $registryRequest): void
    {
        foreach ($request->file('images', []) as $image) {
            $path = $image->store("requests/{$registryRequest->id}/images", 'public');
            RequestImage::create([
                'registry_request_id' => $registryRequest->id,
                'uploaded_by' => $request->user()->id,
                'path' => $path,
                'original_name' => $image->getClientOriginalName(),
                'mime' => $image->getClientMimeType(),
                'size' => $image->getSize(),
                'sha256' => hash_file('sha256', $image->getRealPath()),
            ]);
        }

        foreach (['act_file', 'design_code_file', 'qayta_organish_akti_file'] as $type) {
            if (! $request->hasFile($type)) {
                continue;
            }
            $file = $request->file($type);
            $path = $file->store("requests/{$registryRequest->id}/files", 'public');
            RequestFile::create([
                'registry_request_id' => $registryRequest->id,
                'uploaded_by' => $request->user()->id,
                'type' => $type,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function nextRequestNumber(): string
    {
        do {
            $number = 'THR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (RegistryRequest::where('request_number', $number)->exists());

        return $number;
    }
}
