@extends('layouts.builder')
@section('title', 'Review Import')

@section('content')
    @livewire('import-mapper', ['formImport' => $formImport])
@endsection
