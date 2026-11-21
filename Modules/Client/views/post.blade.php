@extends('client::layout')
@push('assets')<link rel="stylesheet" href="{{ asset('studio/editor.css') }}">@endpush
@section('content')
@include('client::partials.page-head', ['title' => $item->name, 'lead' => $item->published_at ? 'Đăng ngày '.\Illuminate\Support\Carbon::parse($item->published_at)->format('d/m/Y') : null, 'crumbs' => ['Tin tức' => route('client.posts'), \Illuminate\Support\Str::limit($item->name, 50) => null]])
<section class="section">
    <div class="container with-sidebar">
        <article class="box" style="padding:28px">
            @if($item->description)<div class="rich-text" style="font-weight:500;color:var(--ink);margin-bottom:16px">{!! $item->description !!}</div>@endif
            @if($item->image)<img src="{{ $item->image }}" alt="{{ $item->name }}" style="width:100%;border-radius:8px;margin-bottom:18px">@endif
            <div class="rich-text ck-content">{!! $item->body !!}</div>
        </article>
        <aside class="sticky">
            @if($related->isNotEmpty())
                <div class="box"><h3>Bài viết khác</h3>
                    <div style="display:grid;gap:14px">@foreach($related as $post)@include('client::partials.post-card')@endforeach</div>
                </div>
            @endif
        </aside>
    </div>
</section>
@if($listings->isNotEmpty())
<section class="section section-white"><div class="container">
    <div class="section-head"><h2>Tin đăng nổi bật</h2></div>
    <div class="grid-4">@foreach($listings as $listing)@include('client::partials.listing-card')@endforeach</div>
</div></section>
@endif
@endsection
