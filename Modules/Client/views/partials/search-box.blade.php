@php
    $website = app(\Modules\Client\Services\WebsiteService::class);
    $current = $filters ?? ['type' => 'sale', 'province' => null, 'q' => '', 'category' => null, 'price' => null, 'area' => null];
    $type = $current['type'] ?? 'sale';
@endphp
<form class="search-box" method="get" action="{{ route($type === 'rent' ? 'client.rent' : 'client.sale') }}" role="search" data-search
      data-sale-action="{{ route('client.sale') }}" data-rent-action="{{ route('client.rent') }}"
      data-ranges='@json(config('cms.price_ranges'))'>
    <input type="hidden" name="loai" value="{{ $type }}">
    <div class="search-tabs" role="tablist" aria-label="Hình thức">
        <button type="button" role="tab" data-tab="sale" aria-selected="{{ $type !== 'rent' ? 'true' : 'false' }}">Nhà đất bán</button>
        <button type="button" role="tab" data-tab="rent" aria-selected="{{ $type === 'rent' ? 'true' : 'false' }}">Nhà đất cho thuê</button>
    </div>
    <div class="search-panel">
        <div class="search-row">
            <select name="tinh" aria-label="Tỉnh/Thành phố">
                <option value="">Toàn quốc</option>
                @foreach(config('cms.provinces') as $province)<option @selected(($current['province'] ?? null) === $province)>{{ $province }}</option>@endforeach
            </select>
            <label class="keyword"><i class="fas fa-search"></i><span class="sr-only">Từ khóa</span><input name="q" value="{{ $current['q'] ?? '' }}" maxlength="120" placeholder="Nhập khu vực, dự án, tên đường... Ví dụ: Cầu Giấy"></label>
            <button class="btn btn-primary" type="submit">Tìm kiếm</button>
        </div>
        <div class="search-filters">
            <select name="danh-muc" aria-label="Loại nhà đất">
                <option value="">Loại nhà đất</option>
                @foreach($website->categories() as $category)
                    <option value="{{ $category->key }}" data-type="{{ $category->type }}" @selected(($current['category'] ?? null) === $category->key)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="dien-tich" aria-label="Diện tích">
                <option value="">Diện tích</option>
                @foreach(config('cms.area_ranges') as $value => $label)<option value="{{ $value }}" @selected(($current['area'] ?? null) === $value)>{{ $label }}</option>@endforeach
            </select>
            <select name="gia" aria-label="Mức giá">
                <option value="">Mức giá</option>
                @foreach(config('cms.price_ranges.'.$type) as $value => $label)<option value="{{ $value }}" @selected(($current['price'] ?? null) === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
    </div>
</form>
