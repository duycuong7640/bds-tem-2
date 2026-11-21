@extends('admin::layout')
@section('title', 'Thư viện ảnh')
@section('content')
    <iframe
        class="media-library-frame"
        title="Thư viện ảnh UniSharp"
        src="{{ route('admin.media', ['type' => 'image', 'embedded' => 1], absolute: false) }}"
    ></iframe>
@endsection
