{{-- Thông tin bất động sản của tin đăng (dùng trong editorial-form khi resource = products). --}}
@php
    $v = fn (string $field, $default = '') => old($field, $record->{$field} ?? $default);
    $err = fn (string $field) => $errors->has($field) ? 'aria-invalid=true aria-describedby=error-'.$field : '';
    $galleryLines = old('gallery', implode("\n", json_decode($record->gallery ?? '[]', true) ?: []));
@endphp
<section class="panel editorial-panel listing-panel" aria-labelledby="listing-specs">
    <h2 id="listing-specs">Đặc điểm bất động sản</h2>
    <div class="form-grid">
        <div class="field">
            <label for="field-province">Tỉnh/Thành phố <span class="field-required" aria-hidden="true">*</span></label>
            <select id="field-province" name="province" {{ $err('province') }}>
                <option value="">Chọn tỉnh/thành</option>
                @foreach(config('cms.provinces') as $province)<option @selected($v('province') === $province)>{{ $province }}</option>@endforeach
            </select>
            @include('admin::field-error', ['field' => 'province'])
        </div>
        <div class="field">
            <label for="field-district">Quận/Huyện</label>
            <input id="field-district" name="district" value="{{ $v('district') }}" maxlength="80" {{ $err('district') }}>
            @include('admin::field-error', ['field' => 'district'])
        </div>
        <div class="field wide">
            <label for="field-address">Địa chỉ <span class="field-required" aria-hidden="true">*</span></label>
            <input id="field-address" name="address" value="{{ $v('address') }}" maxlength="255" placeholder="Số nhà, đường, phường/xã" {{ $err('address') }}>
            @include('admin::field-error', ['field' => 'address'])
        </div>
        <div class="field">
            <label for="field-area">Diện tích (m²) <span class="field-required" aria-hidden="true">*</span></label>
            <input id="field-area" name="area" type="number" step="0.01" min="1" value="{{ $v('area') !== '' ? rtrim(rtrim((string) $v('area'), '0'), '.') : '' }}" {{ $err('area') }}>
            @include('admin::field-error', ['field' => 'area'])
        </div>
        @foreach(['bedrooms' => 'Số phòng ngủ', 'toilets' => 'Số toilet', 'floors' => 'Số tầng'] as $field => $label)
            <div class="field">
                <label for="field-{{ $field }}">{{ $label }}</label>
                <input id="field-{{ $field }}" name="{{ $field }}" type="number" min="0" value="{{ $v($field) }}" {{ $err($field) }}>
                @include('admin::field-error', ['field' => $field])
            </div>
        @endforeach
        <div class="field">
            <label for="field-direction">Hướng nhà</label>
            <select id="field-direction" name="direction" {{ $err('direction') }}>
                <option value="">Không xác định</option>
                @foreach(config('cms.directions') as $direction)<option @selected($v('direction') === $direction)>{{ $direction }}</option>@endforeach
            </select>
            @include('admin::field-error', ['field' => 'direction'])
        </div>
        <div class="field">
            <label for="field-legal">Pháp lý</label>
            <select id="field-legal" name="legal" {{ $err('legal') }}>
                <option value="">Không xác định</option>
                @foreach(config('cms.legal_options') as $legal)<option @selected($v('legal') === $legal)>{{ $legal }}</option>@endforeach
            </select>
            @include('admin::field-error', ['field' => 'legal'])
        </div>
        <div class="field">
            <label for="field-contact_name">Tên liên hệ <span class="field-required" aria-hidden="true">*</span></label>
            <input id="field-contact_name" name="contact_name" value="{{ $v('contact_name', site_setting('company_name')) }}" maxlength="120" {{ $err('contact_name') }}>
            @include('admin::field-error', ['field' => 'contact_name'])
        </div>
        <div class="field">
            <label for="field-contact_phone">Điện thoại liên hệ <span class="field-required" aria-hidden="true">*</span></label>
            <input id="field-contact_phone" name="contact_phone" type="tel" value="{{ $v('contact_phone', preg_replace('/\D/', '', (string) site_setting('phone'))) }}" maxlength="20" {{ $err('contact_phone') }}>
            @include('admin::field-error', ['field' => 'contact_phone'])
        </div>
        <div class="field wide">
            <label for="field-contact_email">Email liên hệ</label>
            <input id="field-contact_email" name="contact_email" type="email" value="{{ $v('contact_email') }}" maxlength="254" {{ $err('contact_email') }}>
            @include('admin::field-error', ['field' => 'contact_email'])
        </div>
        <div class="field wide">
            <label for="field-gallery">Album ảnh (mỗi dòng một đường dẫn ảnh)</label>
            <textarea id="field-gallery" name="gallery" rows="4" placeholder="/storage/media/library/bds/can-ho-1.jpg" {{ $err('gallery') }}>{{ $galleryLines }}</textarea>
            <small>Dùng nút “Chọn ảnh” ở cột phải để lấy đường dẫn từ thư viện rồi dán vào đây.</small>
            @include('admin::field-error', ['field' => 'gallery'])
        </div>
    </div>
</section>
