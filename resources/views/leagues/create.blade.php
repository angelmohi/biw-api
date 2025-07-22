@extends('layouts.app')

@section('content')
<div class="card mb-4">
    <div class="card-body m-2">
        <div class="d-flex justify-content-between">
            <div>
                <h4 class="card-title align-middle d-inline pt-2">Crear liga</h4>
            </div>

            <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with buttons">
                <a class="btn btn-outline-secondary" type="button" href="{{ route('leagues.index') }}" >
                    <i class="fas fa-chevron-left mr-2"></i> Volver
                </a>
            </div>
        </div>
        <hr>
        <form method="POST" action="{{ route('leagues.store') }}">
            @include('leagues._form', ['editing' => false])
        </form>
    </div>
</div>
@endsection
