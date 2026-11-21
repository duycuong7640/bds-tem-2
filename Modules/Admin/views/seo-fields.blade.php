<section class="editorial-seo {{ $seoClass ?? '' }}" aria-labelledby="seo-heading">
    <h2 id="seo-heading">SEO</h2>
    <div class="field">
        <label for="field-seo_title">Tiêu đề SEO</label>
        <input type="text" id="field-seo_title" name="seo_title" maxlength="70"
            value="{{ old('seo_title', $seoTitle ?? '') }}"
            @error('seo_title') aria-invalid="true" aria-describedby="error-seo_title" @enderror>
        @include('admin::field-error', ['field' => 'seo_title'])
    </div>
    <div class="field">
        <label for="field-seo_description">Mô tả SEO</label>
        <textarea id="field-seo_description" name="seo_description" rows="3" maxlength="{{ $seoDescriptionLimit ?? 170 }}"
            @error('seo_description') aria-invalid="true" aria-describedby="error-seo_description" @enderror>{{ old('seo_description', $seoDescription ?? '') }}</textarea>
        @include('admin::field-error', ['field' => 'seo_description'])
    </div>
</section>
