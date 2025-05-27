@extends('layouts.app')

@section('content')
<div class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="mb-4">Entrar em {{ $invitation->account->name }}</h2>
                        <p class="mb-4">Você foi convidado para entrar em <strong>{{ $invitation->account->name }}</strong> como <strong>{{ $invitation->role }}</strong>.</p>

                        @guest
                            <div class="mb-4">
                                <p class="mb-2">Faça login ou registre-se para aceitar este convite:</p>
                                <a href="{{ route('login') }}" class="btn btn-primary me-2">Login</a>
                                <a href="{{ route('register') }}" class="btn btn-secondary">Registrar</a>
                            </div>
                        @else
                            <form action="{{ route('invitations.accept-with-login', $invitation->token) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success">Aceitar Convite</button>
                            </form>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 