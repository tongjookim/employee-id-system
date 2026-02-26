@extends('layouts.admin')
@section('title', '비밀번호 변경')
@section('page-title', '비밀번호 변경')

@section('content')
<div class="card" style="max-width: 500px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.password.update') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">현재 비밀번호</label>
                <input type="password" name="current_password" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">새 비밀번호 (최소 4자)</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label">새 비밀번호 확인</label>
                <input type="password" name="new_password_confirmation" class="form-control" required>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">변경하기</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">취소</a>
            </div>
        </form>
    </div>
</div>
@endsection
