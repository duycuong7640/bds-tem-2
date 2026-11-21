<div class="page-head">
    <div class="container">
        <ol class="breadcrumb">
            <li><a href="{{ route('home') }}">Trang chủ</a></li>
            @foreach($crumbs ?? [] as $label => $url)
                <li>@if($url)<a href="{{ $url }}">{{ $label }}</a>@else<span aria-current="page">{{ $label }}</span>@endif</li>
            @endforeach
        </ol>
        <h1>{{ $title }}</h1>
        @isset($lead)<p>{{ $lead }}</p>@endisset
    </div>
</div>
