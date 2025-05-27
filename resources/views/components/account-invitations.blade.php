@props(['account'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900">
        <h3 class="text-lg font-semibold mb-4">Invite Members</h3>

        <form id="invitationForm" class="mb-4">
            @csrf
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email (optional)</label>
                <input type="email" name="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="mb-4">
                <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role" id="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="member">Member</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Generate Invitation
            </button>
        </form>

        <div id="invitationResult" class="hidden">
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Invitation Link:</h4>
                <div class="flex items-center space-x-2">
                    <input type="text" id="invitationLink" readonly class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <button onclick="copyInvitationLink()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Copy
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">QR Code:</h4>
                <div id="qrCode" class="inline-block p-4 bg-white rounded-lg shadow"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('invitationForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch(`/accounts/{{ $account->id }}/invitations`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: formData.get('email'),
                role: formData.get('role')
            })
        });

        const data = await response.json();
        
        if (response.ok) {
            document.getElementById('invitationLink').value = data.invitation_url;
            document.getElementById('qrCode').innerHTML = data.qr_code;
            document.getElementById('invitationResult').classList.remove('hidden');
        } else {
            alert('Error creating invitation');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error creating invitation');
    }
});

function copyInvitationLink() {
    const linkInput = document.getElementById('invitationLink');
    linkInput.select();
    document.execCommand('copy');
    alert('Link copied to clipboard!');
}
</script>
@endpush 