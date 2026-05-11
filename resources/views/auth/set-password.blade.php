@extends('layouts.app')
@section('title', 'Créer mon mot de passe')
@section('content')
<div class="container-fluid py-12">
  <div class="max-w-md mx-auto">

    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 rounded-full mb-4">
        <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-gray-900">Créer mon mot de passe</h1>
      <p class="text-gray-500 mt-2 text-sm">Choisissez un mot de passe sécurisé pour accéder à votre espace.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-6">
      <form method="POST" action="{{ route('password.set.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
          <input type="email" name="email" value="{{ old('email', $email) }}"
            class="w-full px-4 py-2 border rounded-lg bg-gray-50 text-gray-500 @error('email') border-red-500 @enderror"
            readonly>
          @error('email')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div x-data="{ show: false }">
          <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
          <div class="relative">
            <input :type="show ? 'text' : 'password'" name="password"
              class="w-full px-4 py-2 border rounded-lg pr-10 @error('password') border-red-500 @enderror"
              placeholder="Minimum 8 caractères" required>
            <button type="button" @click="show=!show"
              class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
              <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <svg x-show="show" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
              </svg>
            </button>
          </div>
          @error('password')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
          <input type="password" name="password_confirmation"
            class="w-full px-4 py-2 border rounded-lg"
            placeholder="Répétez le mot de passe" required>
        </div>

        <button type="submit"
          class="w-full py-2.5 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition">
          Activer mon compte →
        </button>
      </form>
    </div>

    <p class="text-center text-sm text-gray-500 mt-6">
      Déjà un mot de passe ?
      <a href="{{ route('login') }}" class="text-primary-600 hover:underline">Se connecter</a>
    </p>
  </div>
</div>
@endsection
