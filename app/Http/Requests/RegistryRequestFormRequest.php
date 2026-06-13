<?php

namespace App\Http\Requests;

use App\Models\RegistryRequest;
use App\Models\RequestImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistryRequestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $registryRequest = $this->route('registryRequest');

        return $registryRequest
            ? $this->user()->can('update', $registryRequest)
            : $this->user()->can('create', RegistryRequest::class);
    }

    public function rules(): array
    {
        $requestId = $this->route('registryRequest')?->id;
        $districtId = (int) $this->input('district_id');
        $mahallaId = (int) $this->input('mahalla_id');

        return [
            'building_cadastr_number' => ['required', 'string', 'max:100', 'regex:/^\d{2}:\d{2}:\d{2}:\d{2}:\d{2}:\d{4}([\/:].+)?$/'],
            'hokimyatga_biriktirilgan_kadastr_raqami' => ['nullable', 'string', 'max:100', 'regex:/^\d{2}:\d{2}:\d{2}:\d{2}:\d{2}:\d{4}([\/:].+)?$/'],
            'owner_type' => ['required', Rule::in(['jismoniy', 'yuridik'])],
            'owner_stir_pinfl' => ['required', 'digits_between:9,14'],
            'owner_name' => ['required', 'string', 'max:255'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'mahalla_id' => [
                'required',
                'integer',
                Rule::exists('mahallas', 'id')->where('district_id', $districtId),
            ],
            'street_id' => [
                'required',
                'integer',
                Rule::exists('streets', 'id')
                    ->where('district_id', $districtId)
                    ->where('mahalla_id', $mahallaId),
            ],
            'house_number' => ['required', 'string', 'max:80'],
            'street_type' => ['required', Rule::in(['kocha', 'shohkocha', 'tor_kocha', 'berk_kocha', 'mavjud_emas'])],
            'director_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'regex:/^\+998 \(\d{2}\) \d{3}-\d{2}-\d{2}$/'],
            'area_length' => ['required', 'numeric', 'min:0.01'],
            'area_width' => ['required', 'numeric', 'min:0.01'],
            'calculated_land_area' => ['required', 'numeric', 'min:0.01'],
            'total_area' => ['required', 'numeric', 'min:0.01'],
            'building_facade_length' => ['nullable', 'numeric', 'min:0'],
            'summer_terrace_sides' => ['nullable', 'numeric', 'min:0'],
            'distance_to_roadway' => ['required', 'numeric', 'min:0'],
            'distance_to_sidewalk' => ['required', 'numeric', 'min:0'],
            'usage_purpose' => ['required', Rule::in(['savdo', 'xizmat', 'umumiy_ovqatlanish', 'boshqa'])],
            'activity_type' => ['required', 'string', 'max:255'],
            'terrace_buildings_available' => ['required', 'boolean'],
            'terrace_buildings_permanent' => ['required', 'boolean'],
            'has_permit' => ['required', 'boolean'],
            'has_tenant' => ['nullable', 'boolean'],
            'tenant_stir_pinfl' => ['nullable', 'required_if:has_tenant,1', 'string', 'max:32'],
            'tenant_name' => ['nullable', 'required_if:has_tenant,1', 'string', 'max:255'],
            'tenant_activity_type' => ['nullable', 'required_if:has_tenant,1', 'string', 'max:255'],
            'adjacent_activity_type' => ['nullable', 'string', 'max:255'],
            'adjacent_activity_land' => ['required', 'numeric', 'min:0'],
            'adjacent_facilities' => ['nullable', 'array'],
            'adjacent_facilities.*' => ['string', 'max:120'],
            'additional_info' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'polygon_coordinates' => ['required', 'json'],
            'images' => [$requestId ? 'nullable' : 'required', 'array', $requestId ? 'min:0' : 'min:4'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'act_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'design_code_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'qayta_organish_akti_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $polygon = json_decode((string) $this->input('polygon_coordinates'), true);
            $coords = $polygon['geometry']['coordinates'][0] ?? $polygon['coordinates'][0] ?? null;
            if (! is_array($polygon) || ! is_array($coords) || count($coords) < 4) {
                $validator->errors()->add('polygon_coordinates', 'Poligon kamida 3 nuqta va yopuvchi koordinatadan iborat GeoJSON bo‘lishi kerak.');
            }

            $hashes = [];
            foreach ($this->file('images', []) as $index => $image) {
                $hash = hash_file('sha256', $image->getRealPath());
                if (in_array($hash, $hashes, true)) {
                    $validator->errors()->add("images.$index", 'Bir xil rasmni qayta yuklash mumkin emas.');
                }
                $hashes[] = $hash;
            }

            $requestId = $this->route('registryRequest')?->id;
            if ($requestId && $hashes) {
                $exists = RequestImage::where('registry_request_id', $requestId)
                    ->whereIn('sha256', $hashes)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('images', 'Bu arizada oldin yuklangan rasm qayta qo‘shilmoqda.');
                }
            }

            if ($this->user()?->isTuman() && (int) $this->input('district_id') !== (int) $this->user()->district_id) {
                $validator->errors()->add('district_id', 'Tuman foydalanuvchisi faqat o‘z hududi bo‘yicha ma’lumot kiritadi.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute maydoni majburiy.',
            'required_if' => ':attribute maydoni majburiy.',
            'string' => ':attribute matn ko‘rinishida bo‘lishi kerak.',
            'integer' => ':attribute butun son bo‘lishi kerak.',
            'numeric' => ':attribute raqam bo‘lishi kerak.',
            'boolean' => ':attribute qiymati to‘g‘ri/noto‘g‘ri bo‘lishi kerak.',
            'array' => ':attribute ro‘yxat ko‘rinishida bo‘lishi kerak.',
            'json' => ':attribute JSON formatida bo‘lishi kerak.',
            'exists' => 'Tanlangan :attribute topilmadi.',
            'in' => 'Tanlangan :attribute noto‘g‘ri.',
            'min' => ':attribute kamida :min bo‘lishi kerak.',
            'max' => ':attribute :max dan oshmasligi kerak.',
            'between' => ':attribute :min va :max oralig‘ida bo‘lishi kerak.',
            'digits_between' => ':attribute :min va :max xonali raqam bo‘lishi kerak.',
            'regex' => ':attribute formati noto‘g‘ri.',
            'image' => ':attribute rasm fayli bo‘lishi kerak.',
            'file' => ':attribute fayl bo‘lishi kerak.',
            'mimes' => ':attribute faqat quyidagi formatlarda bo‘lishi mumkin: :values.',
            'images.required' => 'Kamida 4 ta rasm yuklang.',
            'images.min' => 'Kamida 4 ta rasm yuklang.',
            'images.*.image' => 'Yuklangan fayllar rasm bo‘lishi kerak.',
            'images.*.mimes' => 'Rasmlar faqat jpg, jpeg, png yoki webp formatida bo‘lishi kerak.',
            'images.*.max' => 'Har bir rasm 10 MB dan oshmasligi kerak.',
            'act_file.mimes' => 'Akt fayli faqat pdf, jpg, jpeg yoki png formatida bo‘lishi kerak.',
            'design_code_file.mimes' => 'Loyiha kodi fayli faqat pdf, jpg, jpeg yoki png formatida bo‘lishi kerak.',
            'qayta_organish_akti_file.mimes' => 'Qayta o‘rganish akti faqat pdf, jpg, jpeg yoki png formatida bo‘lishi kerak.',
        ];
    }

    public function attributes(): array
    {
        return [
            'building_cadastr_number' => 'Kadastr raqami',
            'hokimyatga_biriktirilgan_kadastr_raqami' => 'Hokimiyatga biriktirilgan kadastr raqami',
            'owner_type' => 'Mulk egasi turi',
            'owner_stir_pinfl' => 'STIR/PINFL',
            'owner_name' => 'Egasi nomi',
            'district_id' => 'Tuman',
            'mahalla_id' => 'Mahalla',
            'street_id' => 'Ko‘cha',
            'house_number' => 'Uy raqami',
            'street_type' => 'Ko‘cha turi',
            'director_name' => 'Rahbar F.I.SH',
            'phone_number' => 'Telefon raqami',
            'area_length' => 'Uzunlik',
            'area_width' => 'Kenglik',
            'calculated_land_area' => 'Hisoblangan maydon',
            'total_area' => 'Umumiy maydon',
            'building_facade_length' => 'Fasad uzunligi',
            'summer_terrace_sides' => 'Yozgi terassa tomonlari',
            'distance_to_roadway' => 'Yo‘lgacha masofa',
            'distance_to_sidewalk' => 'Trotuargacha masofa',
            'usage_purpose' => 'Foydalanish maqsadi',
            'activity_type' => 'Faoliyat turi',
            'tenant_stir_pinfl' => 'Ijarachi STIR/PINFL',
            'tenant_name' => 'Ijarachi nomi',
            'tenant_activity_type' => 'Ijarachi faoliyat turi',
            'adjacent_activity_type' => 'Tutash hududdagi faoliyat',
            'adjacent_activity_land' => 'Tutash hudud maydoni',
            'adjacent_facilities' => 'Tutash hududdagi obyektlar',
            'latitude' => 'Xarita kengligi',
            'longitude' => 'Xarita uzunligi',
            'polygon_coordinates' => 'Xarita poligoni',
            'images' => 'Rasmlar',
            'images.*' => 'Rasm',
            'act_file' => 'Akt fayli',
            'design_code_file' => 'Loyiha kodi fayli',
            'qayta_organish_akti_file' => 'Qayta o‘rganish akti',
        ];
    }

    protected function prepareForValidation(): void
    {
        $totalArea = null;
        if (is_numeric($this->input('area_length')) && is_numeric($this->input('area_width'))) {
            $totalArea = round((float) $this->input('area_length') * (float) $this->input('area_width'), 2);
        }

        $this->merge([
            'has_tenant' => $this->boolean('has_tenant'),
            'terrace_buildings_available' => $this->boolean('terrace_buildings_available'),
            'terrace_buildings_permanent' => $this->boolean('terrace_buildings_permanent'),
            'has_permit' => $this->boolean('has_permit'),
            'total_area' => $totalArea ?? $this->input('total_area'),
            'calculated_land_area' => $totalArea ?? $this->input('total_area'),
        ]);
    }
}
