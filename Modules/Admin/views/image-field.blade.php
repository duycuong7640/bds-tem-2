@php
    $inputId = $inputId ?? 'field-'.$field;
    $label = $label ?? 'Hình ảnh';
    $hasImage = is_string($value) && preg_match('~^(?:/(?!/)|https?://)~i', $value);
@endphp
<div class="field image-field" data-image-field>
    <label for="{{ $inputId }}">{{ $label }} @if($required ?? false)<span class="field-required" aria-hidden="true">*</span>@endif</label>
    <figure class="image-field-preview" data-image-preview @if(!$hasImage) hidden @endif>
        <img class="image-preview" @if($hasImage) src="{{ $value }}" @endif alt="{{ $label }} xem trước">
        @if($pickerEnabled)
            <button class="button remove-image" type="button" data-remove-image aria-label="Xóa {{ mb_strtolower($label) }}" title="Xóa ảnh"><i class="far fa-trash-alt" aria-hidden="true"></i></button>
        @endif
    </figure>
    <div class="image-field-row">
        <input type="text" id="{{ $inputId }}" name="{{ $field }}" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror value="{{ $value }}" placeholder="URL ảnh" readonly @if($required ?? false) aria-required="true" @endif data-image-url>
        @if($pickerEnabled)
            <button class="button" type="button" data-pick-image title="Chọn từ thư viện"><i class="far fa-image" aria-hidden="true"></i> Chọn ảnh</button>
        @endif
    </div>
    @include('admin::field-error')
</div>
