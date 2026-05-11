<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $client = $request->user()->client;
        if (!$client) return redirect()->route('client.profile.create');
        return view('client.profile', compact('client'));
    }

    public function createForm()
    {
        // Déjà un profil ? → rediriger
        if (auth()->user()->client) {
            return redirect()->route('client.dashboard');
        }
        return view('client.profile-create');
    }

    public function store(Request $request)
    {
        // Déjà un profil ? → bloquer
        if ($request->user()->client) {
            return redirect()->route('client.dashboard');
        }

        $rules = [
            'type'       => 'required|in:individual,company',
            'civility'   => 'nullable|in:M,Mme,Dr,Pr',
            'first_name' => 'required|max:100',
            'last_name'  => 'required|max:100',
            'phone'      => 'nullable|max:20',
            'mobile'     => 'nullable|max:20',
        ];

        if ($request->input('type') === 'company') {
            $rules['company_name'] = 'required|max:255';
        }

        $v = $request->validate($rules);

        DB::transaction(function () use ($v, $request) {
            $client = Client::create([
                'type'         => $v['type'],
                'civility'     => $v['civility']    ?? null,
                'first_name'   => $v['first_name'],
                'last_name'    => $v['last_name'],
                'company_name' => $v['company_name'] ?? null,
                'email'        => $request->user()->email,
                'user_id'      => $request->user()->id,
                'phone'        => $v['phone']        ?? null,
                'mobile'       => $v['mobile']       ?? null,
                'status'       => 'active',
                'is_active'    => true,
            ]);

            // Rattacher le client à l'utilisateur
            $request->user()->update(['client_id' => $client->id]);
        });

        return redirect()->route('client.dashboard')
            ->with('success', 'Profil créé avec succès.');
    }

    public function update(Request $request)
    {
        $client = $request->user()->client;

        if (!$client) {
            return redirect()->route('client.profile.create');
        }

        // Le formulaire profil ne contient que phone et mobile
        // Les adresses sont gérées dans la table addresses (lecture seule côté client)
        $v = $request->validate([
            'phone'  => 'nullable|max:20',
            'mobile' => 'nullable|max:20',
        ]);

        $client->update($v);

        return redirect()->route('client.profile')
            ->with('success', 'Profil mis à jour.');
    }
}
