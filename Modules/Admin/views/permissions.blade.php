@extends('admin::layout')
@section('title', 'Quản lý quyền')
@section('content')
<div class="intro"><div><h1>Quản lý quyền</h1><p>Phân quyền xem, chỉnh sửa và xóa cho Manager và Editor. Admin có toàn quyền.</p></div></div>
<form method="post" action="{{ route('admin.permissions.save') }}" class="panel table-panel">
    @csrf @method('PUT')
    <div class="table-scroll">
        <table class="permission-table">
            <thead><tr><th>Chức năng</th><th>Manager</th><th>Editor</th></tr></thead>
            <tbody>
            @foreach(config('cms.permission_groups') as $key => $label)
                <tr><th scope="row">{{ $label }}</th>
                    @foreach(['manager', 'editor'] as $role)
                        <td><div class="permission-options">
                            @foreach(['view' => 'Xem', 'edit' => 'Sửa', 'delete' => 'Xóa'] as $scope => $text)
                                @continue(($key === 'dashboard' && $scope !== 'view') || ($key === 'settings' && $scope === 'delete'))
                                <label class="checkbox"><input type="checkbox" name="roles[{{ $role }}][]" value="{{ $key }}.{{ $scope }}" @checked(in_array($key.'.'.$scope, old('roles.'.$role, $permissions[$role]), true)) aria-label="{{ $role }} · {{ $text }} {{ $label }}">{{ $text }}</label>
                            @endforeach
                        </div></td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="form-actions padded"><button type="submit" class="button primary"><i class="fas fa-check"></i> Lưu quyền</button></div>
</form>
@endsection
