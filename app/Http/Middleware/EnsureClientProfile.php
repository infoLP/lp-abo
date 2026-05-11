<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureClientProfile
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) return $next($request);

        if ($request->routeIs('client.profile.create', 'client.profile.store', 'logout')) {
            return $next($request);
        }

        if (!$user->client_id || !$user->client) {
            return redirect()->route('client.profile.create');
        }

        if ($user->client->isArchived()) {
            auth()->logout();
            $request->session()->invalidate();
            return redirect()->route('login')
                ->withErrors(['email' => 'Votre compte a été désactivé. Contactez-nous.']);
        }

        return $next($request);
    }
}
