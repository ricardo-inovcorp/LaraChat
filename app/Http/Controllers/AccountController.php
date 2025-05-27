<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::with(['members', 'invitations'])->get();
        return view('accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $account = Account::create([
            'name' => $request->name,
            'created_by' => Auth::id(),
        ]);

        // Adiciona o criador como membro da conta
        $account->members()->attach(Auth::id(), ['role' => 'admin']);

        return redirect()->route('accounts.index')
            ->with('success', 'Conta criada com sucesso!');
    }

    public function invitations(Account $account)
    {
        $this->authorize('invite', $account);
        return view('accounts.invitations', compact('account'));
    }
}
