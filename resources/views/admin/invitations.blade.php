@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4" style="background: #f8f7f4; border-radius: 16px;">
                <h5 class="mb-3 text-center">Partilhe o link ou o QR Code para convidar mais pessoas</h5>
                <div class="d-flex align-items-center mb-3" style="background: #fff; border-radius: 8px; padding: 12px;">
                    <span class="me-2" style="font-size: 1.5rem;"><i class="bi bi-person-plus"></i></span>
                    <input type="text" id="invitationLink" class="form-control border-0 bg-transparent" readonly style="font-size: 1.1rem;" value="" />
                </div>
                <div class="d-flex justify-content-center gap-3 mb-2">
                    <button id="showQrBtn" class="btn btn-light border" title="Show QR Code"><i class="bi bi-qr-code" style="font-size: 1.5rem;"></i></button>
                    <button id="copyBtn" class="btn btn-light border" title="Copy Link"><i class="bi bi-clipboard" style="font-size: 1.5rem;"></i></button>
                    <button id="refreshBtn" class="btn btn-light border" title="Generate New Link"><i class="bi bi-arrow-clockwise" style="font-size: 1.5rem;"></i></button>
                </div>
                <div id="qrCodeContainer" class="text-center mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentInvitation = null;

async function fetchLatestInvitation() {
    try {
        const response = await fetch('{{ route('invitations.latest') }}');
        const data = await response.json();
        if (data && data.invitation_url) {
            currentInvitation = data;
            document.getElementById('invitationLink').value = data.invitation_url;
        } else {
            document.getElementById('invitationLink').value = '';
            currentInvitation = null;
        }
    } catch (error) {
        document.getElementById('invitationLink').value = '';
        currentInvitation = null;
    }
}

async function generateInvitation() {
    const btn = document.getElementById('refreshBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    document.getElementById('qrCodeContainer').style.display = 'none';
    try {
        const response = await fetch('{{ route('invitations.create') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ role: 'member' })
        });
        const data = await response.json();
        if (response.ok) {
            currentInvitation = data;
            document.getElementById('invitationLink').value = data.invitation_url;
        } else {
            alert('Erro ao gerar convite: ' + (data.message || ''));
        }
    } catch (error) {
        alert('Erro ao gerar convite');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise" style="font-size: 1.5rem;"></i>';
    }
}

document.getElementById('copyBtn').onclick = function() {
    const linkInput = document.getElementById('invitationLink');
    linkInput.select();
    document.execCommand('copy');
    alert('Link copiado!');
};

document.getElementById('refreshBtn').onclick = function() {
    generateInvitation();
};

document.getElementById('showQrBtn').onclick = function() {
    if (currentInvitation && currentInvitation.qr_code) {
        document.getElementById('qrCodeContainer').innerHTML = currentInvitation.qr_code;
        document.getElementById('qrCodeContainer').style.display = 'block';
    } else {
        alert('Gere um convite primeiro!');
    }
};

// Ao carregar a página, busca o convite mais recente
window.addEventListener('DOMContentLoaded', fetchLatestInvitation);
</script>
@endpush
@endsection 