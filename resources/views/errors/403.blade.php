@extends('layouts.app')
@section('title', 'Accès refusé')
@section('content')
<div class="container-fluid py-20">
  <div class="max-w-md mx-auto text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-6">
      <svg class="w-10 h-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-5V9m0 0V7m0 2h2M12 9H10m9.293 9.293A8 8 0 115.707 5.707a8 8 0 0113.586 13.586z"/>
      </svg>
    </div>
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Accès refusé</h1>
    <p class="text-gray-500 mb-8">Vous n'avez pas les droits nécessaires pour accéder à cette ressource.</p>
    <div class="flex justify-center gap-4">
      <a href="{{ route('client.dashboard') }}"
        class="px-6 py-2 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition">
        Mon espace
      </a>
      <a href="{{ route('home') }}"
        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
        Accueil
      </a>
    </div>
  </div>
</div>
@endsection
