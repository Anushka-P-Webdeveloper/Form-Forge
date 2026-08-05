@extends('layouts.builder')
@section('title', 'Submissions: ' . $form->title)

@section('content')
    <h4>{{ $form->title }} — Submissions</h4>

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @livewire('submissions-list', ['form' => $form])
@endsection
