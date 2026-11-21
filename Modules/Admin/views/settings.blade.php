@extends('admin::layout')
@section('title', 'Cấu hình website')
@section('content')
@php($canEdit = admin_can('settings.edit'))
<div class="intro"><div><h1>Cấu hình website</h1><p>Nhận diện, liên hệ, bản đồ, thanh toán và quy định đăng tin.</p></div></div>
<form novalidate class="panel edit-panel" method="post" action="{{ route('admin.settings.save') }}">
    @csrf @method('PUT')
    <fieldset @disabled(!$canEdit) class="form-grid">
        <h2 class="settings-heading wide">Thông tin chung</h2>
        @foreach(config('cms.settings') as $field => $label)
        @continue(in_array($field, ['seo_title', 'seo_description'], true))
        @php($value = old($field, site_setting($field, $field === 'site_name' ? config('app.name') : '')))
        <div class="field @if(in_array($field,['logo','seo_description'])) wide @endif">
            @if($field !== 'logo')<label for="field-{{ $field }}">{{ $label }} @if($field === 'site_name')<span class="field-required" aria-hidden="true">*</span>@endif</label>@endif
            @if($field === 'logo')
                @include('admin::image-field', ['label' => 'Logo dự phòng (logo chính lấy từ WebFX)', 'pickerEnabled' => $canEdit && admin_can('media.view')])
            @else
                <input id="field-{{ $field }}" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ $value }}" @required($field === 'site_name')>
            @endif
            @if($field !== 'logo') @include('admin::field-error') @endif
        </div>
        @endforeach

        @foreach(config('cms.setting_groups') as $group => $fields)
            <h2 class="settings-heading wide">{{ $group }}</h2>
            @foreach($fields as $field => [$label, $type, $rule])
                @php($value = old($field, site_setting($field, '')))
                @php($required = str_contains(is_array($rule) ? implode('|', $rule) : $rule, 'required'))
                <div class="field @if($type === 'textarea' || $field === 'map_embed') wide @endif">
                    <label for="field-{{ $field }}">{{ $label }} @if($required)<span class="field-required" aria-hidden="true">*</span>@endif</label>
                    @if($type === 'textarea')
                        <textarea id="field-{{ $field }}" name="{{ $field }}" rows="4" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>{{ $value }}</textarea>
                    @elseif($type === 'select')
                        <select id="field-{{ $field }}" name="{{ $field }}" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>
                            <option value="0" @selected((string) $value !== '1')>Không – admin duyệt từng tin</option>
                            <option value="1" @selected((string) $value === '1')>Có – tin lên ngay sau khi thanh toán</option>
                        </select>
                    @else
                        <input id="field-{{ $field }}" name="{{ $field }}" type="{{ $type === 'number' ? 'number' : ($type === 'url' ? 'url' : 'text') }}" value="{{ $value }}" @if($type === 'number') min="0" @endif @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>
                    @endif
                    @if($field === 'map_embed')<small>Google Maps → Chia sẻ → Nhúng bản đồ → sao chép phần <code>src="…"</code>.</small>@endif
                    @include('admin::field-error')
                </div>
            @endforeach
        @endforeach

        @include('admin::seo-fields', [
            'seoClass' => 'seo-wide', 'seoTitle' => site_setting('seo_title'),
            'seoDescription' => site_setting('seo_description'), 'seoDescriptionLimit' => 300,
        ])
    </fieldset>
    @if($canEdit)<div class="form-actions"><button type="submit" class="button primary"><i class="fas fa-check"></i> Lưu cấu hình</button></div>@endif
</form>
@include('admin::editor-assets', ['hasEditor' => false, 'hasPicker' => $canEdit && admin_can('media.view')])
@endsection
