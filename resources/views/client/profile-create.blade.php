<div class="container-fluid py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold text-center mb-8">Compléter votre profil</h1>
        <div class="bg-white rounded-xl border p-6">
            <form method="POST" action="{{ route('client.profile.store') }}"
                  class="space-y-4" x-data="{type:'individual'}">
                @csrf
                @if($errors->any())
                    <div class="text-sm text-red-600 bg-red-50 rounded p-3">
                        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select name="type" x-model="type" class="w-full px-4 py-2 border rounded-lg">
                        <option value="individual">Particulier</option>
                        <option value="company">Entreprise</option>
                    </select>
                </div>

                <div x-show="type === 'company'">
                    <label class="block text-sm font-medium mb-1">Raison sociale</label>
                    <input type="text" name="company_name"
                        class="w-full px-4 py-2 border rounded-lg">
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Civilité</label>
                        <select name="civility" class="w-full px-4 py-2 border rounded-lg">
                            <option value="">-</option>
                            <option value="M">M.</option>
                            <option value="Mme">Mme</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Nom *</label>
                        <input type="text" name="last_name"
                            class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Prénom *</label>
                        <input type="text" name="first_name"
                            class="w-full px-4 py-2 border rounded-lg" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Téléphone</label>
                        <input type="tel" name="phone"
                            class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Mobile</label>
                        <input type="tel" name="mobile"
                            class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>

                <p class="text-xs text-gray-400">
                    Vos adresses pourront être ajoutées depuis votre espace client après la création de votre compte.
                </p>

                <button type="submit"
                    class="w-full py-2 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700">
                    Valider
                </button>
            </form>
        </div>
    </div>
</div>
