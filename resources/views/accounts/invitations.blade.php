@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Convidar Membros para {{ $account->name }}</h5>
                </div>

                <div class="card-body">
                    <x-account-invitations :account="$account" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 