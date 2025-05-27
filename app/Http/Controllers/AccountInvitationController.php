<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountInvitation;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class AccountInvitationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $account = $user->accounts()->first();

        // Se não existir, cria uma conta principal para o admin
        if (!$account) {
            $account = \App\Models\Account::create([
                'name' => $user->name . ' Principal',
                'created_by' => $user->id,
            ]);
            $account->users()->attach($user->id, ['role' => 'admin']);
        }

        $invitations = $account->invitations()->with('inviter')->latest()->get();

        return view('admin.invitations', compact('account', 'invitations'));
    }

    public function create(Request $request)
    {
        try {
            $account = auth()->user()->accounts()->first();

            $validated = $request->validate([
                'role' => 'required|in:member,admin',
                'email' => 'nullable|email',
            ]);

            // Gere o token ANTES de criar o registro
            $token = Str::random(32);

            $invitation = $account->invitations()->create([
                'invited_by' => auth()->id(),
                'email' => $request->email,
                'role' => $request->role ?? 'member',
                'expires_at' => now()->addDays(7),
                'token' => $token,
            ]);

            return response()->json([
                'invitation' => $invitation,
                'invitation_url' => route('invitations.accept', $invitation->token),
                'qr_code' => \QrCode::size(300)->generate(route('invitations.accept', $invitation->token))
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar convite: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro ao criar convite',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function accept($token)
    {
        $invitation = AccountInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        if (auth()->check()) {
            $invitation->account->users()->syncWithoutDetaching([
                auth()->id() => ['role' => $invitation->role]
            ]);

            $invitation->update(['accepted_at' => now()]);

            return redirect()->route('home')->with('success', 'Você entrou com sucesso na conta!');
        }

        return view('invitations.accept', compact('invitation'));
    }

    public function acceptWithLogin(Request $request, $token)
    {
        $invitation = AccountInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $invitation->account->users()->attach(auth()->id(), [
            'role' => $invitation->role
        ]);

        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('home')->with('success', 'Você entrou com sucesso na conta!');
    }

    public function latest()
    {
        $account = auth()->user()->accounts()->first();
        $invitation = $account->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($invitation) {
            return response()->json([
                'invitation' => $invitation,
                'invitation_url' => route('invitations.accept', $invitation->token),
                'qr_code' => \QrCode::size(300)->generate(route('invitations.accept', $invitation->token))
            ]);
        } else {
            return response()->json(null);
        }
    }
}
