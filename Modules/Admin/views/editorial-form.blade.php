@extends('admin::layout')
@php
    $isProduct = $resource === 'products';
    $label = config('cms.resources.'.$resource.'.name');
    $canEdit = admin_can($resource.'.edit');
    $publication = isset($record->published_at) ? \Illuminate\Support\Carbon::parse($record->published_at) : null;
    $publicationTimes = config('cms.post_publication_times');
    $hasPicker = $canEdit && admin_can('media.view');
@endphp
@section('title', $label)
@section('content')
<div class="intro">
    <div><a class="text-link back" href="{{ route('admin.index', $resource) }}">← {{ $label }}</a><h1>{{ $record ? $record->name : 'Thêm '.mb_strtolower($label) }}</h1></div>
</div>
<form method="post" novalidate action="{{ $record ? route('admin.update', [$resource, $record->id]) : route('admin.store', $resource) }}">
    @csrf
    @if($record) @method('PUT') @endif
    <fieldset class="editorial-layout" @disabled(!$canEdit)>
        <section class="panel editorial-panel" aria-labelledby="post-content">
            <h2 id="post-content">{{ $isProduct ? 'Nội dung tin đăng' : 'Nội dung bài viết' }}</h2>
            <div class="field"><label for="field-name">{{ $isProduct ? 'Tiêu đề tin' : 'Tiêu đề' }} <span class="field-required" aria-hidden="true">*</span></label><input id="field-name" @error('name') aria-invalid="true" aria-describedby="error-name" @enderror name="name" value="{{ old('name', $record->name ?? '') }}" maxlength="180" required>@include('admin::field-error', ['field' => 'name'])</div>
            <div class="field">
                <label for="field-slug">Đường dẫn (slug)</label>
                <input id="field-slug" @error('slug') aria-invalid="true" aria-describedby="error-slug" @enderror name="slug" value="{{ old('slug', $record->slug ?? '') }}" placeholder="Tự tạo khi để trống" maxlength="180" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" @if(!$record && !session()->hasOldInput('slug')) data-slug-field @endif>
                <small>{{ $record ? 'Đổi slug sẽ đổi URL công khai.' : 'Để trống để hệ thống tự tạo slug khi lưu.' }}</small>
                @if($record && $record->active && ($record->status ?? 'approved') === 'approved' && (!$record->published_at || $record->published_at <= now()->toDateTimeString()))
                    <a class="text-link" target="_blank" rel="noopener" href="{{ route($isProduct ? 'client.product' : 'client.post', $record->slug) }}">Xem trên website ↗</a>
                @endif
            @include('admin::field-error', ['field' => 'slug'])</div>
            <div class="field"><label for="field-description">Tóm tắt <span class="field-required" aria-hidden="true">*</span></label><textarea name="description" required id="field-description" @error('description') aria-invalid="true" aria-describedby="error-description" @enderror rows="3" @if($canEdit) class="ckeditor-mini" @endif>{{ old('description', $record->description ?? '') }}</textarea>@include('admin::field-error', ['field' => 'description'])</div>
            <div class="field"><label for="field-body">Nội dung <span class="field-required" aria-hidden="true">*</span></label><textarea name="body" required id="field-body" @error('body') aria-invalid="true" aria-describedby="error-body" @enderror rows="13" @if($canEdit) class="ckeditor-full" @endif>{{ old('body', $record->body ?? '') }}</textarea>@include('admin::field-error', ['field' => 'body'])</div>
            @include('admin::seo-fields', ['seoTitle' => $record->seo_title ?? null, 'seoDescription' => $record->seo_description ?? null])
        </section>
        @if($isProduct)
            @include('admin::listing-fields')
        @endif
        <aside class="panel editorial-panel" aria-labelledby="post-publishing">
            <h2 id="post-publishing">Xuất bản</h2>
            @include('admin::image-field', ['field' => 'image', 'label' => 'Ảnh đại diện', 'value' => old('image', $record->image ?? ''), 'pickerEnabled' => $hasPicker, 'required' => true])
            @if($isProduct)
                @php
                    $owner = !empty($record?->user_id) ? \Illuminate\Support\Facades\DB::table('users')->find($record->user_id, ['id', 'name', 'email', 'phone']) : null;
                    $status = old('status', $record->status ?? 'approved');
                @endphp
                @if($owner)
                    <div class="listing-owner">
                        <strong><i class="fas fa-user"></i> Người đăng: {{ $owner->name }}</strong>
                        <small>{{ $owner->email }} · {{ $owner->phone }}</small>
                        @if(admin_can('members.view'))<a class="text-link" href="{{ route('admin.members.edit', $owner->id) }}">Xem thành viên →</a>@endif
                        @if($record->fee)<small>Phí: {{ cms_money($record->fee) }} {{ $record->fee_settled ? '(đã chốt)' : '(đang giữ – hoàn lại nếu từ chối)' }}</small>@endif
                    </div>
                @endif
                <div class="field">
                    <label for="field-listing_type">Hình thức <span class="field-required" aria-hidden="true">*</span></label>
                    <select id="field-listing_type" name="listing_type" @error('listing_type') aria-invalid="true" aria-describedby="error-listing_type" @enderror>
                        @foreach(config('cms.listing_types') as $key => $label)<option value="{{ $key }}" @selected(old('listing_type', $record->listing_type ?? 'sale') === $key)>{{ $label }}</option>@endforeach
                    </select>
                    @include('admin::field-error', ['field' => 'listing_type'])
                </div>
                <div class="field">
                    <label for="field-status">Trạng thái duyệt</label>
                    <select id="field-status" name="status" @error('status') aria-invalid="true" aria-describedby="error-status" @enderror>
                        @foreach(config('cms.listing_statuses') as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach
                    </select>
                    @include('admin::field-error', ['field' => 'status'])
                </div>
                <div class="field">
                    <label for="field-reject_reason">Lý do từ chối</label>
                    <textarea id="field-reject_reason" name="reject_reason" rows="2" maxlength="500" @error('reject_reason') aria-invalid="true" aria-describedby="error-reject_reason" @enderror>{{ old('reject_reason', $record->reject_reason ?? '') }}</textarea>
                    <small>Bắt buộc khi chọn “Bị từ chối”. Phí đang giữ sẽ được hoàn vào ví thành viên.</small>
                    @include('admin::field-error', ['field' => 'reject_reason'])
                </div>
                <div class="field">
                    <label for="field-package_id">Gói tin</label>
                    <select id="field-package_id" name="package_id" @error('package_id') aria-invalid="true" aria-describedby="error-package_id" @enderror>
                        <option value="">Không gắn gói</option>
                        @foreach(\Illuminate\Support\Facades\DB::table('listing_packages')->orderBy('position')->get() as $package)
                            <option value="{{ $package->id }}" @selected((string) old('package_id', $record->package_id ?? '') === (string) $package->id)>{{ $package->name }} – {{ cms_money($package->price_per_day) }}/ngày</option>
                        @endforeach
                    </select>
                    @include('admin::field-error', ['field' => 'package_id'])
                </div>
                <div class="field">
                    <label for="field-days">Số ngày đăng</label>
                    <input type="number" id="field-days" name="days" min="1" max="365" value="{{ old('days', $record->days ?? '') }}" @error('days') aria-invalid="true" aria-describedby="error-days" @enderror>
                    @include('admin::field-error', ['field' => 'days'])
                </div>
                <div class="field">
                    <label for="field-expires_at">Ngày hết hạn</label>
                    <input type="datetime-local" id="field-expires_at" name="expires_at" value="{{ old('expires_at', !empty($record?->expires_at) ? \Illuminate\Support\Carbon::parse($record->expires_at)->format('Y-m-d\TH:i') : '') }}" @error('expires_at') aria-invalid="true" aria-describedby="error-expires_at" @enderror>
                    <small>Để trống: tự tính theo số ngày khi duyệt, hoặc không hết hạn.</small>
                    @include('admin::field-error', ['field' => 'expires_at'])
                </div>
                <div class="field"><label for="field-sku">Mã tin nội bộ</label><input id="field-sku" @error('sku') aria-invalid="true" aria-describedby="error-sku" @enderror name="sku" value="{{ old('sku', $record->sku ?? '') }}" maxlength="80">@include('admin::field-error', ['field' => 'sku'])</div>
                <div class="field"><label for="field-price">Giá (₫) <span class="field-required" aria-hidden="true">*</span></label><input type="number" id="field-price" @error('price') aria-invalid="true" aria-describedby="error-price" @enderror name="price" value="{{ old('price', $record->price ?? 0) }}" min="0" max="999999999999" required><small>Nhập 0 nếu “Thỏa thuận”. Tin cho thuê nhập giá theo tháng.</small>@include('admin::field-error', ['field' => 'price'])</div>
            @endif
            <div class="field">
                <label for="field-menu_id">Danh mục <span class="field-required" aria-hidden="true">*</span></label>
                <select name="menu_id" required id="field-menu_id" @error('menu_id') aria-invalid="true" aria-describedby="error-menu_id" @enderror>
                    <option value="">Chọn danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('menu_id', $record->menu_id ?? '') === (string) $category->id)>{{ str_repeat('— ', $category->depth) }}{{ $category->name }}</option>
                    @endforeach
                </select>
                @if(!$categories)<small>Thêm menu loại {{ $isProduct ? 'Sản phẩm' : 'Bài viết' }} để chọn danh mục.</small>@endif
            @include('admin::field-error', ['field' => 'menu_id'])</div>
            <div class="field">
                <label for="field-active">Trạng thái</label>
                <select name="active" id="field-active" @error('active') aria-invalid="true" aria-describedby="error-active" @enderror>
                    <option value="0" @selected(!(int) old('active', $record->active ?? 1))>Bản nháp</option>
                    <option value="1" @selected((int) old('active', $record->active ?? 1))>Xuất bản</option>
                </select>
            @include('admin::field-error', ['field' => 'active'])</div>
            <div class="field" data-publication>
                <label for="field-publication_at">Thời điểm xuất bản</label>
                <input type="datetime-local" name="publication_at" id="field-publication_at" @error('publication_at') aria-invalid="true" aria-describedby="error-publication_at" @enderror value="{{ old('publication_at', ($publication ?? now())->format('Y-m-d\TH:i')) }}" required>
                <input type="hidden" name="publication_time" value="{{ old('publication_time', $publication ? 'custom' : 'now') }}">
                @if($canEdit)
                    <div class="publication-shortcuts" aria-label="Chọn nhanh thời điểm xuất bản" role="group">
                        <button type="button" class="button" data-publication-choice="now">Xuất bản ngay</button>
                        @foreach($publicationTimes as $time)
                            <button type="button" class="button" data-publication-choice="{{ $time }}">{{ $time }}</button>
                        @endforeach
                    </div>
                @endif
                <small>Giờ Việt Nam</small>
                @include('admin::field-error', ['field' => 'publication_at'])
                @include('admin::field-error', ['field' => 'publication_time'])
            </div>
        </aside>
    </fieldset>
    @if($canEdit)<p data-editor-status role="status"></p>@endif
    <div class="form-actions">
        @if($canEdit)<button class="button primary" type="submit"><i class="fas fa-check"></i> Lưu thay đổi</button>@endif
        <a class="button" href="{{ route('admin.index', $resource) }}">{{ $canEdit ? 'Hủy' : 'Quay lại' }}</a>
    </div>
</form>
@include('admin::editor-assets', ['hasEditor' => $canEdit, 'hasPicker' => $hasPicker])
@endsection
