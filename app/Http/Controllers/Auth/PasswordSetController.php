<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordSetController extends Controller
{
    public function show(Request $request, string $token)
    {
        return view('auth.set-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ], [
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password'          => Hash::make($request->password),
                    'remember_token'    => Str::random(60),
                    'email_verified_at' => now(),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $user = \App\Models\User::where('email', $request->email)->first();

            // Vérifier que le compte est actif avant de connecter
            if (!$user || !$user->is_active) {
                return back()->withErrors([
                    'email' => 'Ce compte est désactivé. Contactez le support.',
                ]);
            }

            auth()->login($user);

            // Redirection selon le rôle
            if ($user->hasAnyRole(['admin', 'director', 'manager', 'accountant'])) {
                return redirect('/admin')
                    ->with('success', 'Mot de passe mis à jour. Bienvenue !');
            }

            return redirect()->route('client.dashboard')
                ->with('success', 'Bienvenue ! Votre compte est activé.');
        }

        return back()->withErrors(['email' => __($status)])->withInput();
    }
}
