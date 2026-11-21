@extends('admin::layout')
@section('title', 'Dashboard')
@section('content')
<div class="intro">
    <div><h1>Dashboard</h1><p>Tổng quan hoạt động và nội dung trên website.</p></div>
    <span class="date-pill"><i class="far fa-calendar-alt"></i> {{ now()->format('d/m/Y') }}</span>
</div>
<section class="hero">
    <div class="hero-art"></div>
    <div class="hero-copy">
        <h2>Xin chào, {{ auth()->user()->name }}!</h2>
        <p>{{ site_setting('site_name', config('app.name')) }} · {{ config('cms.roles.'.auth()->user()->role) }}</p>
        @if(admin_can('posts.edit'))
            <a class="hero-button" href="{{ route('admin.create', 'posts') }}"><i class="fas fa-plus"></i> Viết bài mới</a>
        @endif
    </div>
</section>
@if($marketplace)
<section class="member-summary" aria-label="Sàn rao vặt" style="margin-top:18px">
    @foreach($marketplace as [$label, $value, $icon, $url])
        <a href="{{ $url }}"><div><span><i class="fas fa-{{ $icon }}"></i> {{ $label }}</span><strong>{{ is_int($value) ? number_format($value, 0, ',', '.') : $value }}</strong></div></a>
    @endforeach
</section>
@endif
@if($stats)
<section class="stats" aria-label="Thống kê nội dung">
    @foreach($stats as $key => $total)
        @php($item = config('cms.resources.'.$key))
        <a class="stat stat-{{ $key }}" href="{{ route('admin.index', $key) }}">
            <span class="stat-icon"><i class="fas fa-{{ $item['icon'] }}"></i></span>
            <i class="stat-arrow fas fa-arrow-right"></i>
            <strong>{{ number_format($total, 0, ',', '.') }}</strong>
            <span>{{ $item['name'] }}</span>
        </a>
    @endforeach
</section>
@endif
@if($traffic)
<section class="dashboard-grid">
    <article class="panel chart-panel">
        <div class="panel-head"><div><h2>Tương tác website</h2><p>7 ngày gần nhất</p></div><i class="fas fa-chart-bar panel-icon"></i></div>
        @php($max = max(1, ...array_merge(array_column($traffic, 'contacts'), array_column($traffic, 'subscribers'))))
        <div class="chart" role="group" aria-label="Tương tác theo ngày">
            @foreach($traffic as $day)
                <div class="chart-day">
                    <div class="bars">
                        @foreach(['contacts' => 'Liên hệ', 'subscribers' => 'Đăng ký'] as $key => $label)
                            @if(isset($stats[$key]))
                                <span class="bar {{ $key }}" title="{{ $label }} {{ $day['day'] }}: {{ $day[$key] }}" style="--height: {{ 100 * $day[$key] / $max }}%"><span class="sr-only">{{ $label }} {{ $day['day'] }}: {{ $day[$key] }}</span></span>
                            @endif
                        @endforeach
                    </div>
                    <span>{{ $day['day'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="legend">
            @if(isset($stats['contacts']))<span><i></i> Liên hệ</span>@endif
            @if(isset($stats['subscribers']))<span class="alt"><i></i> Đăng ký email</span>@endif
        </div>
    </article>
    @if(isset($stats['contacts']))
    <article class="panel">
        <div class="panel-head"><div><h2>Liên hệ gần đây</h2><p>Tin nhắn từ website</p></div><a class="text-link" href="{{ route('admin.index', 'contacts') }}">Xem tất cả <i class="fas fa-arrow-right"></i></a></div>
        @forelse($recent as $contact)
            <a class="contact-row" href="{{ route('admin.form', ['contacts', $contact->id]) }}"><span class="avatar">{{ mb_strtoupper(mb_substr($contact->name, 0, 1)) }}</span><span><strong>{{ $contact->name }}</strong><small>{{ $contact->email }}</small></span><i class="fas fa-chevron-right"></i></a>
        @empty
            <p class="empty"><i class="far fa-envelope"></i> Chưa có liên hệ.</p>
        @endforelse
    </article>
    @endif
</section>
@endif
@endsection
