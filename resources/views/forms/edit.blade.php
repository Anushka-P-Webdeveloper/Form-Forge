@extends('layouts.builder')
@section('title', 'Edit: ' . $form->title)

@section('content')
    @livewire('form-builder', ['form' => $form])
@endsection
