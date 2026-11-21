@extends('client::layout')
@section('title', $current->name ?? 'Tin tức')
@section('content')
@include('client::partials.page-head', ['title' => $current->name ?? 'Tin tức bất động sản', 'lead' => 'Cập nhật thị trường, kinh nghiệm mua bán và pháp lý nhà đất.', 'crumbs' => array_filter(['Tin tức' => $current ? route('client.posts') : null, ($current->name ?? '') => null], fn ($v, $k) => $k !== '', ARRAY_FILTER_USE_BOTH)])
<section class="section">
    <div class="container">
        @if($menus->isNotEmpty())
            <ul class="chip-list" style="margin-bottom:20px">
                <li><a href="{{ route('client.posts') }}" @if(!$current) aria-current="page" @endif>Tất cả</a></li>
                @foreach($menus as $menu)<li><a href="{{ route('client.posts', ['chuyen-muc' => $menu->key]) }}" @if($current?->id === $menu->id) aria-current="page" @endif>{{ $menu->name }}</a></li>@endforeach
            </ul>
        @endif
        @if($posts->isNotEmpty())
            <div class="grid-3">@foreach($posts as $post)@include('client::partials.post-card', ['showExcerpt' => true])@endforeach</div>
        @else
            <div class="empty-state"><i class="far fa-newspaper"></i><p>Chưa có bài viết.</p></div>
        @endif
        {{ $posts->onEachSide(1)->links('client::pagination') }}
    </div>
</section>
@endsection
