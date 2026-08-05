@extends('layouts.builder')
@section('title', 'My Forms')

@section('content')
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="row">
        <div class="col-md-5">
            @livewire('ai-form-generator')
        </div>
        <div class="col-md-7">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">My Forms</h4>
                <div class="d-flex gap-2">
                    <a href="{{ route('forms.import') }}" class="btn btn-outline-secondary">Import Word/Excel</a>
                    <form method="POST" action="{{ route('forms.create') }}">
                        @csrf
                        <button class="btn btn-outline-primary">+ New blank form</button>
                    </form>
                </div>
            </div>

            <table class="table bg-white">
                <thead><tr><th>Title</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($forms as $form)
                    <tr>
                        <td>{{ $form->title }} @if($form->ai_generated)<span class="badge bg-info">AI</span>@endif</td>
                        <td><span class="badge bg-secondary">{{ $form->status }}</span></td>
                        <td>
                            <a href="{{ route('forms.edit', $form) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <a href="{{ route('forms.submissions', $form) }}" class="btn btn-sm btn-outline-secondary">Submissions</a>
                            @if($form->status === 'published')
                                <a href="{{ route('forms.fill', $form->slug) }}" target="_blank" class="btn btn-sm btn-outline-success">Public link</a>
                            @endif
                            <form method="POST" action="{{ route('forms.destroy', $form) }}" class="d-inline"
                                onsubmit="return confirm('Delete &quot;{{ $form->title }}&quot;? This also deletes its submissions. This can\'t be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $forms->links() }}
        </div>
    </div>
@endsection
