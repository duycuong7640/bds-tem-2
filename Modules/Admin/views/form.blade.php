@extends('admin::layout')
@php
    $item = config('cms.resources.'.$resource);
    $canEdit = admin_can($resource.'.edit');
    $hasEditor = isset($item['fields']['body']) && $canEdit;
    $hasPicker = $canEdit && admin_can('media.view') && isset($item['fields']['image']);
    $action = $record ? route('admin.update', [$resource, $record->id]) : route('admin.store', $resource);
@endphp
@section('title', $item['name'])
@section('content')
<div class="intro">
    <div><a class="text-link back" href="{{ route('admin.index', $resource) }}">← {{ $item['name'] }}</a><h1>{{ $record ? ($record->name ?? $record->email) : 'Thêm '.$item['name'] }}</h1></div>
</div>
<form novalidate class="panel edit-panel" method="post" action="{{ $action }}">
    @csrf
    @if($record) @method('PUT') @endif
    <fieldset @disabled(!$canEdit)>
        <div class="form-grid">
        @foreach($item['fields'] as $field => $label)
            @php($value = $field === 'password' ? null : old($field, $record->{$field} ?? (in_array($field, ['position', 'price', 'price_per_day', 'priority'], true) ? 0 : ($field === 'min_days' ? 1 : ($field === 'color' ? '#e03c31' : null)))))
            @if($field === 'password' && !$canEdit) @continue @endif
            <div class="field @if(in_array($field, ['body', 'message', 'image'])) wide @endif">
                @if($field !== 'image')<label for="field-{{ $field }}">{{ $label }}</label>@endif
                @switch($field)
                    @case('body')
                        <textarea id="field-body" name="body" rows="11" @if($hasEditor) class="ckeditor-full" @endif>{{ $value }}</textarea>
                        @break
                    @case('description')
                        <textarea id="field-description" name="description" rows="3" maxlength="500" @error('description') aria-invalid="true" aria-describedby="error-description" @enderror>{{ $value }}</textarea>
                        @break
                    @case('message')
                        <textarea id="field-message" rows="6" readonly>{{ $value }}</textarea>
                        @break
                    @case('image')
                        @include('admin::image-field', ['field' => $field, 'value' => $value, 'pickerEnabled' => $hasPicker, 'required' => $resource === 'banners'])
                        @break
                    @case('content_type')
                        <select id="field-content_type" name="content_type">
                            <option value="">Điều hướng</option>
                            <option value="posts" @selected($value === 'posts')>Bài viết</option>
                            <option value="products" @selected($value === 'products')>Sản phẩm</option>
                        </select>
                        @break
                    @case('parent_id')
                        <select id="field-parent_id" name="parent_id"><option value="">Menu gốc</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((string) $value === (string) $parent->id)>{{ str_repeat('— ', min(10, $parent->depth)) }}{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        @break
                    @case('role')
                    @case('placement')
                    @case('status')
                    @case('active')
                        @php($options = match($field) {
                            'role' => config('cms.roles'),
                            'placement' => ['slide' => 'Slide', 'advertisement' => 'Quảng cáo'],
                            'status' => ['new' => 'Mới', 'read' => 'Đã đọc', 'resolved' => 'Đã xử lý'],
                            default => $resource === 'packages' ? [0 => 'Ngừng bán', 1 => 'Đang bán'] : [0 => 'Tắt', 1 => 'Bật'],
                        })
                        <select id="field-{{ $field }}" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror name="{{ $field }}">
                            @foreach($options as $key => $option)<option value="{{ $key }}" @selected((string) ($value ?? (in_array($resource, ['menus', 'banners', 'users', 'packages'], true) ? '1' : '0')) === (string) $key)>{{ $option }}</option>@endforeach
                        </select>
                        @break
                    @case('password')
                        <input id="field-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" @required(!$record)>
                        @if($record)<small>Để trống để giữ mật khẩu hiện tại.</small>@endif
                        @break
                    @default
                        <input id="field-{{ $field }}" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror name="{{ $field }}" value="{{ $value }}" type="{{ match($field) {'email' => 'email', 'price', 'position', 'price_per_day', 'priority', 'min_days' => 'number', 'color' => 'color', default => 'text'} }}"
                            @required(in_array($field, ['name','email','price','position','price_per_day','priority','min_days','color'])) @readonly(in_array($resource, ['contacts','subscribers']) && in_array($field, ['name','email','phone']))
                            @if(in_array($field,['price','position','price_per_day','priority','min_days'])) min="0" @endif
                            >
                @endswitch
                @if($field !== 'image') @include('admin::field-error') @endif
            </div>
        @endforeach
        @if($resource === 'products')
            <div class="field"><label for="field-slug">Đường dẫn (slug)</label>
                <input id="field-slug" value="{{ $record->slug ?? '' }}" placeholder="Tự tạo từ tên sản phẩm" @if(!$record) data-slug-field @endif readonly>
            </div>
        @endif
        @if($resource === 'menus')
            @include('admin::seo-fields', ['seoClass' => 'seo-wide', 'seoTitle' => $record->seo_title ?? null, 'seoDescription' => $record->seo_description ?? null])
        @endif
        </div>
    </fieldset>
    @if($hasEditor)<p data-editor-status role="status"></p>@endif
    <div class="form-actions">
        @if($canEdit)<button class="button primary" type="submit"><i class="fas fa-check"></i> Lưu thay đổi</button>@endif
        <a class="button" href="{{ route('admin.index', $resource) }}">{{ $canEdit ? 'Hủy' : 'Quay lại' }}</a>
    </div>
</form>
@include('admin::editor-assets', ['hasEditor' => $hasEditor, 'hasPicker' => $hasPicker])
@endsection
