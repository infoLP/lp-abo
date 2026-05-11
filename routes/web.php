<?php
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\IssueController;
use App\Http\Controllers\Client\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

// ── Pages publiques ──────────────────────────────────────────────────
Route::get('/',          [PageController::class, 'home'])->name('home');
Route::get('/a-propos',  [PageController::class, 'about'])->name('about');
Route::get('/magazines', [PageController::class, 'magazines'])->name('magazines');
Route::get('/contact',   [ContactController::class, 'show'])->name('contact');
Route::post('/contact',  [ContactController::class, 'store'])->name('contact.store');
Route::get('/health',    fn() => response()->json(['status' => 'ok']));

// ── Authentification ─────────────────────────────────────────────────
Route::middleware('guest')->group(function () {

    Route::get('/inscription', fn() => view('auth.register'))->name('register');

    Route::post('/inscription', function (Request $r) {
        // ── Rate limiting : 5 inscriptions / 5 minutes par IP ───────
        $key = 'register:' . $r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ])->onlyInput('email');
        }
        RateLimiter::hit($key, 300); // fenêtre de 5 minutes

        $v = $r->validate([
            'name'     => 'required|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        $u = \App\Models\User::create([
            'name'              => $v['name'],
            'email'             => $v['email'],
            'password'          => bcrypt($v['password']),
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
        $u->assignRole('client');
        auth()->login($u);

        return redirect()->route('client.profile.create');
    })->name('register.store');

    Route::get('/connexion', fn() => view('auth.login'))->name('login');

    Route::post('/connexion', function (Request $r) {
        $c = $r->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // ── Rate limiting : 5 tentatives / minute par IP + email ────
        $key = 'login:' . $r->ip() . ':' . strtolower($c['email']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ])->onlyInput('email');
        }

        if (auth()->attempt($c, $r->boolean('remember'))) {
            // Vérifier que le compte est actif
            if (!auth()->user()->is_active) {
                auth()->logout();
                $r->session()->invalidate();
                RateLimiter::hit($key, 60);
                return back()->withErrors([
                    'email' => 'Votre compte est désactivé. Contactez le support.',
                ])->onlyInput('email');
            }

            RateLimiter::clear($key);
            $r->session()->regenerate();
            return redirect()->intended(route('client.dashboard'));
        }

        RateLimiter::hit($key, 60);

        return back()->withErrors([
            'email' => 'Identifiants incorrects.',
        ])->onlyInput('email');

    })->name('login.store');

    // Reset password
    Route::get('/mot-de-passe-oublie',
        [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'show']
    )->name('client.password.request');

    Route::post('/mot-de-passe-oublie',
        [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'send']
    )->name('client.password.email');
});

Route::post('/deconnexion', function (Request $r) {
    auth()->logout();
    $r->session()->invalidate();
    $r->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout')->middleware('auth');

// ── Activation compte (token par email) — accessible sans auth ───────
Route::get('/activer-compte/{token}',
    [\App\Http\Controllers\Auth\PasswordSetController::class, 'show']
)->name('password.set');

Route::post('/activer-compte',
    [\App\Http\Controllers\Auth\PasswordSetController::class, 'store']
)->name('password.set.store');

// ── Prévisualisation admin ───────────────────────────────────────────
Route::middleware(['auth'])->prefix('admin-preview')->name('admin.preview.')->group(function () {
    Route::get('/numeros/{issue}/lire',
        [\App\Http\Controllers\Admin\IssuePreviewController::class, 'reader']
    )->name('reader');
    Route::get('/numeros/{issue}/pdf',
        [\App\Http\Controllers\Admin\IssuePreviewController::class, 'stream']
    )->name('stream');
    Route::get('/invoice-pdf',
        [\App\Http\Controllers\Admin\InvoicePreviewController::class, 'preview']
    )->name('invoice.pdf');
});

// ── Espace client ─────────────────────────────────────────────────────
Route::middleware(['auth', 'client.profile'])
    ->prefix('espace-client')
    ->name('client.')
    ->group(function () {

    Route::get('/',             [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil',       [ProfileController::class, 'show'])->name('profile');
    Route::post('/profil',      [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/creer-profil', [ProfileController::class, 'createForm'])->name('profile.create');
    Route::post('/creer-profil',[ProfileController::class, 'store'])->name('profile.store');

    // Publications — accès conditionné à un abonnement actif (vérifié via Gate dans le controller)
    Route::get('/publication/{magazine}', [IssueController::class, 'publication'])->name('publication');
    Route::get('/lire/{issue}',           [IssueController::class, 'reader'])->name('issue.read');
    Route::get('/lire/{issue}/pdf',       [IssueController::class, 'stream'])->name('issue.stream');
});
