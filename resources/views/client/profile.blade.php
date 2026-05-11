@extends('layouts.app')
@section('title', 'Mon profil')
@section('content')
<div class="container-fluid py-8">
  <div class="max-w-3xl mx-auto">

    {{-- En-tête --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
      <div>
        <a href="{{ route('client.dashboard') }}" class="text-sm text-gray-500 hover:text-primary-600">← Retour au tableau de bord</a>
        <h1 class="text-2xl font-bold mt-1">Mon profil</h1>
      </div>
    </div>

    @if(session('success'))
      <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,5000)"
        class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center justify-between mb-6">
        <span class="text-green-800 text-sm">{{ session('success') }}</span>
        <button @click="show=false" class="text-green-600 text-lg leading-none">&times;</button>
      </div>
    @endif

    @if($client)

    {{-- Carte identité --}}
    <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xl font-bold">
          {{ strtoupper(substr($client->first_name ?? '?', 0, 1)) }}{{ strtoupper(substr($client->last_name ?? '', 0, 1)) }}
        </div>
        <div>
          <h2 class="text-lg font-semibold text-gray-900">{{ $client->full_name }}</h2>
          <p class="text-sm text-gray-500">{{ $client->client_number }}</p>
          <p class="text-sm text-gray-500">{{ $client->email }}</p>
        </div>
        @if($client->is_payer)
          <span class="ml-auto px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">Compte payeur</span>
        @endif
      </div>
    </div>

    {{-- Coordonnées --}}
    <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
      <h2 class="text-lg font-semibold mb-4">Coordonnées</h2>
      <form method="POST" action="{{ route('client.profile.update') }}" class="space-y-4">
        @csrf

        @if($errors->any())
          <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
          </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tél principal</label>
            <input type="tel" name="phone" value="{{ old('phone', $client->phone) }}"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-300 text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tél mobile</label>
            <input type="tel" name="mobile" value="{{ old('mobile', $client->mobile) }}"
              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-300 text-sm">
          </div>
        </div>

        <div class="flex justify-end pt-2">
          <button type="submit"
            class="px-6 py-2 bg-primary-600 text-white rounded-lg font-semibold text-sm hover:bg-primary-700 transition">
            Enregistrer les modifications
          </button>
        </div>
      </form>
    </div>

    {{-- Adresses --}}
    <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
      <h2 class="text-lg font-semibold mb-4">Mes adresses</h2>

      @forelse($client->addresses as $address)
      <div class="border rounded-xl p-4 mb-3 hover:border-primary-200 transition">
        <div class="flex flex-wrap items-center gap-2 mb-2">
          <span class="font-medium text-sm text-gray-900">{{ $address->name }}</span>
          @if($address->is_default)
            <span class="text-xs bg-primary-100 text-primary-700 rounded-full px-2 py-0.5 font-medium">Par défaut</span>
          @endif
          <span class="text-xs rounded-full px-2 py-0.5 font-medium
            {{ $address->usage === 'billing'  ? 'bg-blue-100 text-blue-700'  :
               ($address->usage === 'delivery' ? 'bg-green-100 text-green-700' :
                                                 'bg-gray-100 text-gray-600') }}">
            {{ $address->usage === 'billing'  ? 'Facturation' :
               ($address->usage === 'delivery' ? 'Livraison'   : 'Facturation + Livraison') }}
          </span>
        </div>
        <div class="text-sm text-gray-600 font-mono leading-6">
          @foreach(array_filter([$address->l1, $address->l2, $address->l3, $address->l4, $address->l5]) as $line)
            <div>{{ strtoupper($line) }}</div>
          @endforeach
          <div>{{ implode(' ', array_filter([
            $address->l6_postal_code,
            strtoupper($address->l6_city ?? ''),
            $address->l6_cedex ? 'CEDEX '.$address->l6_cedex : null
          ])) }}</div>
          @if($address->l7_country && $address->l7_country !== 'FR')
            <div>{{ $address->l7_country }}</div>
          @endif
        </div>
      </div>
      @empty
        <div class="text-center py-6 text-gray-400">
          <p class="text-sm">Aucune adresse enregistrée.</p>
        </div>
      @endforelse

      <p class="text-xs text-gray-400 mt-3">
        Pour modifier vos adresses, <a href="{{ route('contact') }}" class="text-primary-600 hover:underline">contactez-nous</a>.
      </p>
    </div>

    {{-- Sécurité --}}
    <div class="bg-white rounded-xl shadow-sm border p-6">
      <h2 class="text-lg font-semibold mb-4">Sécurité</h2>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-700">Mot de passe</p>
          <p class="text-xs text-gray-500">Modifiez votre mot de passe de connexion.</p>
        </div>
        <a href="{{ route('client.password.request') }}"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
          Changer le mot de passe
        </a>
      </div>
    </div>

    @endif
  </div>
</div>
@endsection
