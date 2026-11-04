<x-guest-layout>
    <div x-data="{
        email: '{{ old('email', 'admin@elmartillo.cl') }}',
        password: 'password',
        fillCredentials(userEmail) {
            this.email = userEmail;
            this.password = 'password';
        }
    }">
        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <div class="mb-5 text-center">
            <h1 class="text-xl font-bold text-gray-900">SaaS Inventario Multi-Tenant</h1>
            <p class="text-xs text-gray-500 mt-1">Selecciona una cuenta de demostración o ingresa tus credenciales</p>
        </div>

        <!-- Panel de Credenciales a la Vista para el Evaluador -->
        <div class="mb-6 p-3.5 rounded-xl bg-indigo-50/80 border border-indigo-100 text-xs space-y-2.5">
            <div class="font-semibold text-indigo-950 flex items-center justify-between">
                <span>⚡ Acceso Rápido Demo (1 Clic)</span>
                <span class="font-mono text-[11px] text-indigo-700">Clave: password</span>
            </div>

            <div class="grid grid-cols-1 gap-2">
                <!-- Tenant 1: Ferretería -->
                <div class="bg-white p-2.5 rounded-lg border border-indigo-100 flex items-center justify-between gap-2">
                    <div>
                        <div class="font-bold text-gray-800">🔨 Ferretería El Martillo</div>
                        <div class="text-[11px] text-gray-500">Tenant #1 · Rubro Ferretero</div>
                    </div>
                    <div class="flex gap-1.5">
                        <button type="button" @click="fillCredentials('admin@elmartillo.cl')" class="px-2 py-1 rounded bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-semibold transition">
                            Admin
                        </button>
                        <button type="button" @click="fillCredentials('bodega@elmartillo.cl')" class="px-2 py-1 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold transition">
                            Staff
                        </button>
                    </div>
                </div>

                <!-- Tenant 2: Farmacia -->
                <div class="bg-white p-2.5 rounded-lg border border-indigo-100 flex items-center justify-between gap-2">
                    <div>
                        <div class="font-bold text-gray-800">💊 Farmacia BioSalud</div>
                        <div class="text-[11px] text-gray-500">Tenant #2 · Rubro Farmacéutico</div>
                    </div>
                    <div class="flex gap-1.5">
                        <button type="button" @click="fillCredentials('admin@biosalud.cl')" class="px-2 py-1 rounded bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-semibold transition">
                            Admin
                        </button>
                        <button type="button" @click="fillCredentials('dispensario@biosalud.cl')" class="px-2 py-1 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold transition">
                            Staff
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Correo Electrónico')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" x-model="email" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Contraseña')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" x-model="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600">{{ __('Recordarme en este equipo') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button class="w-full justify-center py-2.5">
                    {{ __('Iniciar Sesión') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
