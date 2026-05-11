<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LPAbonnements')</title>

    {{-- Assets compilés localement (Tailwind v4 + Alpine.js) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        .container-fluid {
            width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        @media (min-width: 640px)  { .container-fluid { padding-left: 1.5rem; padding-right: 1.5rem; } }
        @media (min-width: 1024px) { .container-fluid { padding-left: 2rem;   padding-right: 2rem;   } }
        @media (min-width: 1536px) { .container-fluid { padding-left: 4rem;   padding-right: 4rem;   } }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">

<nav class="bg-white shadow-sm border-b" x-data="{open:false}">
    <div class="container-fluid">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="text-xl font-bold text-gray-900">
                    LP<span class="text-primary-600">Abonnements</span>
                </a>
            </div>
            <div class="hidden md:flex items-center space-x-6">
                <a href="{{ route('home') }}"     class="text-gray-700 hover:text-primary-600 font-medium">Accueil</a>
                <a href="{{ route('magazines') }}" class="text-gray-700 hover:text-primary-600 font-medium">Publications</a>
                <a href="{{ route('contact') }}"   class="text-gray-700 hover:text-primary-600 font-medium">Contact</a>
                @auth
                    @if(auth()->user()->hasAnyRole(['admin','director','manager','accountant']))
                        <a href="/admin" class="px-3 py-2 bg-gray-800 text-white rounded-lg text-sm">Admin</a>
                    @endif
                    <a href="{{ route('client.dashboard') }}" class="px-3 py-2 bg-primary-600 text-white rounded-lg text-sm">Mon espace</a>
                @else
                    <a href="{{ route('login') }}"    class="text-gray-700 hover:text-primary-600">Connexion</a>
                    <a href="{{ route('register') }}" class="px-3 py-2 bg-primary-600 text-white rounded-lg text-sm">S'inscrire</a>
                @endauth
            </div>
            <div class="md:hidden flex items-center">
                <button @click="open=!open" class="p-2 text-gray-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <div x-show="open" x-cloak class="md:hidden border-t bg-white px-4 py-3 space-y-2">
        <a href="{{ route('home') }}"     class="block px-3 py-2 rounded text-gray-700 hover:bg-gray-100">Accueil</a>
        <a href="{{ route('magazines') }}" class="block px-3 py-2 rounded text-gray-700 hover:bg-gray-100">Publications</a>
        <a href="{{ route('contact') }}"   class="block px-3 py-2 rounded text-gray-700 hover:bg-gray-100">Contact</a>
        @auth
            <a href="{{ route('client.dashboard') }}" class="block px-3 py-2 rounded bg-primary-600 text-white text-center">Mon espace</a>
        @else
            <a href="{{ route('login') }}" class="block px-3 py-2 rounded text-gray-700 hover:bg-gray-100">Connexion</a>
        @endauth
    </div>
</nav>

@if(session('success'))
<div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,5000)" class="container-fluid mt-4">
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center justify-between">
        <span class="text-green-800">{{ session('success') }}</span>
        <button @click="show=false" class="text-green-600">&times;</button>
    </div>
</div>
@endif

@if(session('error'))
<div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,8000)" class="container-fluid mt-4">
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <span class="text-red-800">{{ session('error') }}</span>
    </div>
</div>
@endif

<main class="flex-1">@yield('content')</main>

<footer class="bg-gray-900 text-gray-300 mt-auto">
    <div class="container-fluid py-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-white font-semibold mb-3">LPAbonnements</h3>
                <p class="text-gray-400 text-sm">Gestion d'abonnements publications professionnelles.</p>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-3">Publications</h3>
                <ul class="space-y-1 text-sm">
                    <li>La Vie Economique</li>
                    <li>Les Annonces Landaises</li>
                    <li>Les Echos Judiciaires Girondins</li>
                    <li>7 Jours</li>
                    <li>L'Informateur Judiciaire</li>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-3">Liens</h3>
                <ul class="space-y-1 text-sm">
                    <li><a href="{{ route('magazines') }}" class="hover:text-white">Publications</a></li>
                    <li><a href="{{ route('contact') }}"   class="hover:text-white">Contact</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-white font-semibold mb-3">Contact</h3>
                <p class="text-gray-400 text-sm">contact@lpabonnements.fr</p>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-6 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} LPAbonnements
        </div>
    </div>
</footer>

</body>
</html>
