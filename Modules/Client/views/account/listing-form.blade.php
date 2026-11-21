@extends('client::layout')
@section('title', $listing ? 'Sửa tin #'.$listing->id : 'Đăng tin mới')
@section('content')
@php
    $user = auth()->user();
    $invalid = fn (string $field) => $errors->has($field) ? 'aria-invalid=true aria-describedby=f-'.str_replace('.', '-', $field).'-error' : '';
    $category = $listing ? $categories->firstWhere('id', $listing->menu_id) : null;
    $price = (int) ($listing->price ?? 0);
    $defaultUnit = $listing ? ($price === 0 ? 'thoa-thuan' : ($price >= 1_000_000_000 && $listing->listing_type !== 'rent' ? 'ty' : 'trieu')) : 'ty';
    $defaultValue = match ($defaultUnit) { 'ty' => $price ? rtrim(rtrim(number_format($price / 1e9, 3, '.', ''), '0'), '.') : '', 'trieu' => rtrim(rtrim(number_format($price / 1e6, 2, '.', ''), '0'), '.'), default => '' };
    $selectedPackage = (string) old('package_id', $listing->package_id ?? $packages->firstWhere('priority', '>', 0)?->id ?? $packages->first()?->id);
    $selectedDays = (string) old('days', 15);
    $groups = $categories->groupBy('type');
@endphp
<div class="container account-layout">
    @include('client::account.nav')
    <form method="post" enctype="multipart/form-data" novalidate data-fee-form data-balance="{{ $user->balance }}"
          action="{{ $listing ? route('client.listings.update', $listing->id) : route('client.listings.store') }}">
        @csrf
        @if($listing) @method('PUT') @endif
        <div class="section-head"><div>
            <h2>{{ $listing ? 'Sửa tin #'.$listing->id : 'Đăng tin bất động sản' }}</h2>
            <p>
                @if(!$listing) Điền đầy đủ thông tin, chọn gói tin và thanh toán bằng số dư ví.
                @elseif($paying) Tin {{ $listing->status === 'rejected' ? 'bị từ chối' : 'đã hết hạn' }} – chỉnh sửa và chọn gói để đăng lại.
                @else Tin sau khi sửa sẽ được kiểm duyệt lại. Gói tin và hạn đăng giữ nguyên. @endif
            </p>
        </div></div>
        @if($listing && $listing->status === 'rejected' && $listing->reject_reason)
            <div class="alert alert-error"><i class="fas fa-info-circle"></i><span>Lý do từ chối lần trước: {{ $listing->reject_reason }}</span></div>
        @endif

        <section class="form-card">
            <h2><i class="fas fa-info-circle"></i> Thông tin cơ bản</h2>
            <div class="form-grid">
                <div class="field wide">
                    <label for="category_id">Loại bất động sản <span class="req">*</span></label>
                    <select id="category_id" name="category_id" {{ $invalid('category_id') }}>
                        <option value="">-- Chọn loại nhà đất --</option>
                        @foreach(['sale' => 'Nhà đất bán', 'rent' => 'Nhà đất cho thuê'] as $type => $label)
                            @if($groups->has($type))
                                <optgroup label="{{ $label }}">
                                    @foreach($groups[$type] as $cat)<option value="{{ $cat->id }}" data-type="{{ $type }}" @selected((string) old('category_id', $listing->menu_id ?? '') === (string) $cat->id)>{{ $cat->name }}</option>@endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    @include('client::field-error', ['field' => 'category_id'])
                </div>
                <div class="field">
                    <label for="province">Tỉnh/Thành phố <span class="req">*</span></label>
                    <select id="province" name="province" {{ $invalid('province') }}>
                        <option value="">-- Chọn tỉnh/thành --</option>
                        @foreach(config('cms.provinces') as $province)<option @selected(old('province', $listing->province ?? '') === $province)>{{ $province }}</option>@endforeach
                    </select>
                    @include('client::field-error', ['field' => 'province'])
                </div>
                <div class="field">
                    <label for="district">Quận/Huyện <span class="req">*</span></label>
                    <input id="district" name="district" value="{{ old('district', $listing->district ?? '') }}" maxlength="80" placeholder="VD: Quận Cầu Giấy" {{ $invalid('district') }}>
                    @include('client::field-error', ['field' => 'district'])
                </div>
                <div class="field wide">
                    <label for="address">Địa chỉ cụ thể <span class="req">*</span></label>
                    <input id="address" name="address" value="{{ old('address', $listing->address ?? '') }}" maxlength="255" placeholder="Số nhà, tên đường, phường/xã" {{ $invalid('address') }}>
                    <small>Địa chỉ dùng để hiển thị bản đồ trên tin đăng.</small>
                    @include('client::field-error', ['field' => 'address'])
                </div>
            </div>
        </section>

        <section class="form-card">
            <h2><i class="fas fa-align-left"></i> Thông tin mô tả</h2>
            <div class="form-grid">
                <div class="field wide">
                    <label for="name">Tiêu đề <span class="req">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $listing->name ?? '') }}" maxlength="180" placeholder="VD: Bán nhà mặt phố Trần Duy Hưng 80m², 5 tầng, kinh doanh tốt" {{ $invalid('name') }}>
                    <small>Tối thiểu 15 ký tự, tối đa 180 ký tự.</small>
                    @include('client::field-error', ['field' => 'name'])
                </div>
                <div class="field wide">
                    <label for="body">Mô tả chi tiết <span class="req">*</span></label>
                    <textarea id="body" name="body" rows="8" maxlength="5000" placeholder="Mô tả vị trí, tiện ích, hiện trạng, pháp lý, giá có thương lượng không..." {{ $invalid('body') }}>{{ old('body', $listing ? trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p><p>'], ["\n", "\n\n"], (string) $listing->body)), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '') }}</textarea>
                    <small>Tối thiểu 50 ký tự. Không chèn số điện thoại vào tiêu đề.</small>
                    @include('client::field-error', ['field' => 'body'])
                </div>
            </div>
        </section>

        <section class="form-card">
            <h2><i class="fas fa-ruler-combined"></i> Giá & đặc điểm</h2>
            <div class="form-grid cols-3">
                <div class="field">
                    <label for="price_value">Mức giá <span class="req">*</span></label>
                    <div class="input-group">
                        <input id="price_value" name="price_value" type="number" step="0.01" min="0" value="{{ old('price_value', $defaultValue) }}" {{ $invalid('price_value') }}>
                        <select name="price_unit" aria-label="Đơn vị giá" {{ $invalid('price_unit') }}>
                            <option value="ty" @selected(old('price_unit', $defaultUnit) === 'ty')>Tỷ</option>
                            <option value="trieu" @selected(old('price_unit', $defaultUnit) === 'trieu')>Triệu</option>
                            <option value="thoa-thuan" @selected(old('price_unit', $defaultUnit) === 'thoa-thuan')>Thỏa thuận</option>
                        </select>
                    </div>
                    @include('client::field-error', ['field' => 'price_value'])
                </div>
                <div class="field">
                    <label for="area">Diện tích (m²) <span class="req">*</span></label>
                    <input id="area" name="area" type="number" step="0.1" min="1" value="{{ old('area', $listing ? rtrim(rtrim((string) $listing->area, '0'), '.') : '') }}" {{ $invalid('area') }}>
                    @include('client::field-error', ['field' => 'area'])
                </div>
                <div class="field">
                    <label for="legal">Pháp lý</label>
                    <select id="legal" name="legal" {{ $invalid('legal') }}><option value="">-- Chọn --</option>@foreach(config('cms.legal_options') as $option)<option @selected(old('legal', $listing->legal ?? '') === $option)>{{ $option }}</option>@endforeach</select>
                    @include('client::field-error', ['field' => 'legal'])
                </div>
                @foreach(['bedrooms' => 'Số phòng ngủ', 'toilets' => 'Số toilet', 'floors' => 'Số tầng'] as $field => $label)
                    <div class="field">
                        <label for="{{ $field }}">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" max="100" value="{{ old($field, $listing->{$field} ?? '') }}" {{ $invalid($field) }}>
                        @include('client::field-error', ['field' => $field])
                    </div>
                @endforeach
                <div class="field">
                    <label for="direction">Hướng nhà</label>
                    <select id="direction" name="direction" {{ $invalid('direction') }}><option value="">-- Chọn --</option>@foreach(config('cms.directions') as $option)<option @selected(old('direction', $listing->direction ?? '') === $option)>{{ $option }}</option>@endforeach</select>
                    @include('client::field-error', ['field' => 'direction'])
                </div>
            </div>
        </section>

        <section class="form-card">
            <h2><i class="far fa-images"></i> Hình ảnh</h2>
            @if($gallery)
                <p class="hint">Ảnh hiện tại – tích “Bỏ ảnh” để xóa khỏi tin. Ảnh đầu tiên là ảnh đại diện.</p>
                <div class="image-grid" style="margin-bottom:14px">
                    @foreach($gallery as $index => $photo)
                        <figure>
                            <img src="{{ $photo }}" alt="Ảnh {{ $index + 1 }}">
                            @if($index === 0)<span class="cover">Ảnh đại diện</span>@endif
                            <label><input type="checkbox" name="remove_images[]" value="{{ $photo }}" @checked(in_array($photo, old('remove_images', []), true))> Bỏ ảnh</label>
                        </figure>
                    @endforeach
                </div>
            @endif
            <label class="upload-zone @if($errors->has('images') || $errors->has('images.*')) field-invalid @endif" data-upload="#upload-preview">
                <i class="fas fa-cloud-upload-alt"></i>
                <strong>Bấm để chọn ảnh từ máy</strong>
                <span>JPG, PNG, WEBP · tối đa 5MB/ảnh · tối đa {{ $maxImages }} ảnh mỗi tin</span>
                <span data-upload-count></span>
                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple @if($errors->has('images')) aria-invalid="true" aria-describedby="f-images-error" @endif>
            </label>
            <div class="image-grid" id="upload-preview"></div>
            @include('client::field-error', ['field' => 'images'])
            @foreach($errors->getMessages() as $key => $messages)
                @if(str_starts_with($key, 'images.'))<p class="field-error" role="alert">Ảnh {{ ((int) substr($key, 7)) + 1 }}: {{ $messages[0] }}</p>@break @endif
            @endforeach
            @if($errors->any())<p class="hint" style="margin-top:6px"><i class="fas fa-info-circle"></i> Vì lý do bảo mật, trình duyệt không giữ lại ảnh đã chọn khi form có lỗi – vui lòng chọn lại ảnh.</p>@endif
        </section>

        <section class="form-card">
            <h2><i class="far fa-address-card"></i> Thông tin liên hệ</h2>
            <div class="form-grid cols-3">
                <div class="field">
                    <label for="contact_name">Tên liên hệ <span class="req">*</span></label>
                    <input id="contact_name" name="contact_name" value="{{ old('contact_name', $listing->contact_name ?? $user->name) }}" maxlength="120" {{ $invalid('contact_name') }}>
                    @include('client::field-error', ['field' => 'contact_name'])
                </div>
                <div class="field">
                    <label for="contact_phone">Số điện thoại <span class="req">*</span></label>
                    <input id="contact_phone" name="contact_phone" type="tel" value="{{ old('contact_phone', $listing->contact_phone ?? $user->phone) }}" maxlength="20" {{ $invalid('contact_phone') }}>
                    @include('client::field-error', ['field' => 'contact_phone'])
                </div>
                <div class="field">
                    <label for="contact_email">Email</label>
                    <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $listing->contact_email ?? $user->email) }}" maxlength="254" {{ $invalid('contact_email') }}>
                    @include('client::field-error', ['field' => 'contact_email'])
                </div>
            </div>
        </section>

        @if($paying)
        <section class="form-card">
            <h2><i class="fas fa-crown"></i> Chọn gói tin & thanh toán</h2>
            <div class="package-options" role="radiogroup" aria-label="Gói tin">
                @foreach($packages as $package)
                    <label class="package-option" style="--pkg: {{ $package->color }}">
                        <input type="radio" name="package_id" value="{{ $package->id }}" data-price="{{ $package->price_per_day }}" data-min-days="{{ $package->min_days }}" data-name="{{ $package->name }}" @checked($selectedPackage === (string) $package->id)>
                        <strong>{{ $package->name }}</strong>
                        <b>{{ $package->price_per_day ? number_format($package->price_per_day, 0, ',', '.').' ₫' : 'Miễn phí' }}<small style="font-weight:400">/ngày</small></b>
                        <span>{{ $package->description }}</span>
                    </label>
                @endforeach
            </div>
            @include('client::field-error', ['field' => 'package_id'])
            <div class="form-grid" style="margin-top:14px">
                <div class="field">
                    <label for="days">Số ngày đăng <span class="req">*</span></label>
                    <select id="days" name="days" {{ $invalid('days') }}>
                        @foreach(config('cms.listing_days') as $days)<option value="{{ $days }}" @selected($selectedDays === (string) $days)>{{ $days }} ngày</option>@endforeach
                    </select>
                    @include('client::field-error', ['field' => 'days'])
                </div>
                <div class="field"><span class="label">Thời gian hiển thị</span><span class="hint" style="padding-top:10px">Tính từ khi tin được duyệt{{ (string) site_setting('auto_approve') === '1' ? ' (tin được duyệt tự động)' : '' }}.</span></div>
            </div>
            <div class="fee-summary" data-fee-output aria-live="polite">
                <div><span>Gói tin</span><strong data-fee-package>—</strong></div>
                <div><span>Thời gian</span><strong data-fee-days>—</strong></div>
                <div><span>Số dư hiện tại</span><strong>{{ cms_money($user->balance) }}</strong></div>
                <div class="total"><span>Tổng thanh toán</span><span data-fee-total>—</span></div>
                <div><span>Số dư sau thanh toán</span><strong data-fee-after>—</strong></div>
                <div class="short" data-fee-short hidden><span><i class="fas fa-exclamation-triangle"></i> Thiếu <span data-fee-short-amount></span> – vui lòng</span><a class="link-red" href="{{ route('client.wallet') }}" target="_blank">nạp thêm tiền</a></div>
            </div>
            <div class="field" style="margin-top:14px">
                <label class="check"><input type="checkbox" name="agree" value="1" @checked(old('agree')) {{ $invalid('agree') }}> Tôi cam kết thông tin chính xác và đồng ý với <a class="link-red" href="{{ route('client.pricing') }}" target="_blank">quy định đăng tin</a>. Phí được hoàn 100% nếu tin bị từ chối.</label>
                @include('client::field-error', ['field' => 'agree'])
            </div>
        </section>
        @endif

        <div class="form-actions">
            <button class="btn btn-primary btn-lg" type="submit"><i class="fas fa-paper-plane"></i> {{ $paying ? 'Thanh toán & đăng tin' : 'Lưu thay đổi' }}</button>
            <a class="btn btn-outline btn-lg" href="{{ route('client.account') }}">Hủy</a>
        </div>
    </form>
</div>
@endsection
