@if($hasEditor)
    @once
    @push('assets')
        <link rel="stylesheet" href="{{ asset('studio/editor.css') }}">
        <script src="{{ route('admin.editor', $resource) }}" defer></script>
    @endpush
    <span hidden data-editor-license="{{ config('cms.editor_license') }}"></span>
    @endonce
@endif
@if($hasPicker)
    @once
    @push('dialogs')
    <dialog class="file-dialog" data-file-picker data-url="{{ route('admin.media', ['type' => 'image', 'picker' => 1], absolute: false) }}">
        <header><strong>Thư viện ảnh</strong><form method="dialog"><button class="button">Đóng</button></form></header>
        <iframe title="UniSharp File Manager"></iframe>
    </dialog>
    @endpush
    @endonce
@endif
