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

        $query = $this->filteredRequestsQuery($request);
        $perPage = $this->requestsPerPage($request);

        return view('requests.index', [
            'requests' => $query->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
            'perPageOptions' => [15, 25, 50, 100],
            'districts' => $this->availableDistricts($request),
            'mahallas' => $this->availableMahallas($request),
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
        $this->authorize('update', $registryRequest);

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

    public function export(Request $request)
    {
        $this->authorize('viewAny', RegistryRequest::class);

        $requests = $this->filteredRequestsQuery($request)->get();
        $statusLabels = $this->statusLabels();
        $streetTypes = RegistryRequest::STREET_TYPES;
        $ownerTypeLabels = ['jismoniy' => 'Jismoniy shaxs', 'yuridik' => 'Yuridik shaxs'];
        $usagePurposeLabels = ['savdo' => 'Savdo', 'xizmat' => 'Xizmat', 'umumiy_ovqatlanish' => 'Umumiy ovqatlanish', 'boshqa' => 'Boshqa'];
        $yesNo = fn ($value) => $value ? 'Ha' : 'Yo‘q';
        $filename = 'tutash-hududlar-'.now()->format('Y-m-d-H-i').'.xls';

        return response()->streamDownload(function () use ($requests, $statusLabels, $streetTypes, $ownerTypeLabels, $usagePurposeLabels, $yesNo) {
            echo "\xEF\xBB\xBF";
            echo '<html><head><meta charset="UTF-8"></head><body><table border="1"><tr>';

            foreach ($this->exportHeadings() as $heading) {
                echo '<th>'.$this->excelCell($heading).'</th>';
            }

            echo '</tr>';

            foreach ($requests as $item) {
                $row = [
                    $item->request_number,
                    $statusLabels[$item->status] ?? $item->status,
                    $item->created_at?->format('d.m.Y H:i'),
                    $item->district?->name,
                    $item->mahalla?->name,
                    $item->street?->name,
                    $item->house_number,
                    $streetTypes[$item->street_type] ?? $item->street_type,
                    $item->building_cadastr_number,
                    $item->hokimyatga_biriktirilgan_kadastr_raqami,
                    $ownerTypeLabels[$item->owner_type] ?? $item->owner_type,
                    $item->owner_stir_pinfl,
                    $item->owner_name,
                    $item->director_name,
                    $item->phone_number,
                    $item->area_length,
                    $item->area_width,
                    $item->total_area,
                    $item->building_facade_length,
                    $item->summer_terrace_sides,
                    $item->distance_to_roadway,
                    $item->distance_to_sidewalk,
                    $usagePurposeLabels[$item->usage_purpose] ?? $item->usage_purpose,
                    $item->activity_type,
                    $yesNo($item->terrace_buildings_available),
                    $yesNo($item->terrace_buildings_permanent),
                    $yesNo($item->has_permit),
                    $yesNo($item->has_tenant),
                    $item->tenant_stir_pinfl,
                    $item->tenant_name,
                    $item->tenant_activity_type,
                    $item->adjacent_activity_type,
                    $item->adjacent_activity_land,
                    collect($item->adjacent_facilities ?? [])->implode(', '),
                    $item->additional_info,
                    $item->latitude,
                    $item->longitude,
                    $item->creator?->name,
                ];

                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>'.$this->excelCell($value).'</td>';
                }
                echo '</tr>';
            }

            echo '</table></body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function checkCadastreRestriction(Request $request)
    {
        $data = $request->validate([
            'cadastre_number' => ['required', 'string', 'max:100', 'regex:/^\d{2}:\d{2}:\d{2}:\d{2}:\d{2}:\d{4}([\/:].+)?$/'],
            'registry_request_id' => ['nullable', 'integer', 'exists:registry_requests,id'],
        ]);

        $existing = RegistryRequest::query()
            ->where('building_cadastr_number', $data['cadastre_number'])
            ->when($data['registry_request_id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
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

    private function filteredRequestsQuery(Request $request)
    {
        $query = RegistryRequest::with(['district', 'mahalla', 'street', 'creator'])
            ->latest();

        if ($request->user()->isTuman()) {
            $query->where('district_id', $request->user()->district_id);
        }

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('street_type'), fn ($q) => $q->where('street_type', $request->street_type));
        $query->when($request->filled('district_id') && ! $request->user()->isTuman(), fn ($q) => $q->where('district_id', $request->district_id));
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

        return $query;
    }

    private function requestsPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 15);

        return in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 15;
    }

    private function availableDistricts(Request $request)
    {
        $query = District::orderBy('name');

        if ($request->user()->isTuman()) {
            $query->where('id', $request->user()->district_id);
        }

        return $query->get();
    }

    private function availableMahallas(Request $request)
    {
        $query = Mahalla::orderBy('name');

        if ($request->user()->isTuman()) {
            $query->where('district_id', $request->user()->district_id);
        } elseif ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        return $query->get();
    }

    private function exportHeadings(): array
    {
        return [
            'Ariza raqami',
            'Holati',
            'Sana',
            'Tuman',
            'Mahalla',
            'Ko‘cha',
            'Uy raqami',
            'Ko‘cha turi',
            'Kadastr raqami',
            'Hokimiyat kadastri',
            'Mulk egasi turi',
            'STIR/PINFL',
            'Egasi nomi',
            'Rahbar F.I.SH',
            'Telefon',
            'Uzunlik',
            'Kenglik',
            'Umumiy maydon',
            'Fasad uzunligi',
            'Yozgi terassa tomonlari',
            'Yo‘lgacha masofa',
            'Trotuargacha masofa',
            'Foydalanish maqsadi',
            'Faoliyat turi',
            'Terassada qurilmalar bor',
            'Doimiy qurilmalar bor',
            'Ruxsatnoma bor',
            'Ijarachi mavjud',
            'Ijarachi STIR/PINFL',
            'Ijarachi nomi',
            'Ijarachi faoliyat turi',
            'Tutash hududdagi faoliyat',
            'Tutash hudud maydoni',
            'Tutash hudud obyektlari',
            'Qo‘shimcha ma’lumot',
            'Xarita kengligi',
            'Xarita uzunligi',
            'Yaratuvchi',
        ];
    }

    private function excelCell($value): string
    {
        return e((string) ($value ?? ''));
    }

    private function formData(Request $request, ?RegistryRequest $registryRequest = null): array
    {
        $districts = $this->availableDistricts($request);
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
