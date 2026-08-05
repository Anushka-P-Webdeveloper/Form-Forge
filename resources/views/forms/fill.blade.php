@extends('layouts.builder')
@section('title', $form->title)

@section('content')
    @livewire('public-form-fill', ['form' => $form])
@endsection
