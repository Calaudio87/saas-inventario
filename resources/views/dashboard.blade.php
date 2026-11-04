<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                        Tenant #{{ $user->tenant->id }} · {{ $user->tenant->industry ?? 'General' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user->isAdmin() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        Rol: {{ strtoupper($user->role) }}
                    </span>
                </div>
                <h2 class="mt-1 font-bold text-2xl text-gray-900 leading-tight">
                    {{ $user->tenant->name }}
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-2" x-data>
                <button
                    type="button"
                    @click="$dispatch('open-movement-modal', { type: 'in' })"
                    class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                >
                    + Registrar Movimiento
                </button>

                @can('manage-catalog')
                    <button
                        type="button"
                        @click="$dispatch('open-product-modal', { mode: 'create' })"
                        class="inline-flex items-center px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                    >
                        + Nuevo Producto
                    </button>
                @endcan

                @can('manage-users')
                    <button
                        type="button"
                        @click="$dispatch('open-user-modal')"
                        class="inline-flex items-center px-3.5 py-2 bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg shadow-sm transition"
                    >
                        + Nuevo Usuario
                    </button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div
        class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6"
        x-data="inventoryApp({
            products: {{ Js::from($products->map(fn($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category,
                'description' => $p->description,
                'price' => (float) $p->price,
                'min_stock_alert' => (int) $p->min_stock_alert,
                'current_stock' => (int) $p->current_stock,
            ])) }}
        })"
        @open-movement-modal.window="openMovementModal($event.detail)"
        @open-product-modal.window="openProductModal($event.detail)"
        @open-user-modal.window="userModalOpen = true"
    >
        {{-- Notificaciones Flash y Errores --}}
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center justify-between">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <p class="font-semibold mb-1">Por favor corrige los siguientes errores:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tarjetas KPI --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">SKUs en Catálogo</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($kpis['total_products']) }}</p>
                <p class="mt-1 text-xs text-gray-500">Aislados en {{ $user->tenant->name }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Stock Dinámico Total</p>
                <p class="mt-2 text-2xl font-bold text-indigo-600">{{ number_format($kpis['total_units_in_stock']) }} uds.</p>
                <p class="mt-1 text-xs text-gray-500">Calculado en vivo vía SQL CASE</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Alertas de Stock Bajo</p>
                <p class="mt-2 text-2xl font-bold {{ $kpis['low_stock_count'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    {{ $kpis['low_stock_count'] }} productos
                </p>
                <p class="mt-1 text-xs text-gray-500">Stock actual &le; mínimo configurado</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Valorización Estimada</p>
                <p class="mt-2 text-2xl font-bold text-gray-900">${{ number_format($kpis['inventory_valuation'], 2) }}</p>
                <p class="mt-1 text-xs text-gray-500">Suma de (Stock Actual &times; Precio)</p>
            </div>
        </div>

        {{-- Navegación por Pestañas con Alpine.js --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-6">
                <button
                    @click="activeTab = 'catalog'"
                    :class="activeTab === 'catalog' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition"
                >
                    Catálogo y Stock en Vivo ({{ $products->count() }})
                </button>
                <button
                    @click="activeTab = 'rotation'"
                    :class="activeTab === 'rotation' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition"
                >
                    Top 5 Mayor Rotación (30 días)
                </button>
                <button
                    @click="activeTab = 'movements'"
                    :class="activeTab === 'movements' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition"
                >
                    Historial Kardex Reciente
                </button>
                @can('manage-users')
                    <button
                        @click="activeTab = 'team'"
                        :class="activeTab === 'team' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition"
                    >
                        Usuarios de la Empresa ({{ $teamMembers->count() }})
                    </button>
                @endcan
            </nav>
        </div>

        {{-- TAB 1: Catálogo de Productos con Filtros Reactivos en Alpine --}}
        <div x-show="activeTab === 'catalog'" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gray-50/50">
                <div class="relative flex-1 max-w-md">
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Buscar por SKU, nombre o categoría..."
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                    <input type="checkbox" x-model="onlyLowStock" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Mostrar solo stock bajo alerta</span>
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <tr>
                            <th class="px-4 py-3 text-left">SKU</th>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-left">Categoría</th>
                            <th class="px-4 py-3 text-right">Precio</th>
                            <th class="px-4 py-3 text-center">Stock Actual</th>
                            <th class="px-4 py-3 text-right">Acciones Rápidas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-gray-600" x-text="product.sku"></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900" x-text="product.name"></div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs" x-text="product.description || ''"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs" x-text="product.category || 'General'"></span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900" x-text="'$' + Number(product.price).toFixed(2)"></td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold"
                                        :class="product.current_stock <= product.min_stock_alert ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'"
                                    >
                                        <span x-text="product.current_stock + ' uds.'"></span>
                                    </span>
                                    <div class="text-[11px] text-gray-400 mt-0.5" x-text="'Mín: ' + product.min_stock_alert"></div>
                                </td>
                                <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="openMovementModal({ productId: product.id, type: 'in' })"
                                        class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-semibold"
                                    >
                                        + Entrada
                                    </button>
                                    <button
                                        type="button"
                                        @click="openMovementModal({ productId: product.id, type: 'out' })"
                                        class="px-2.5 py-1 rounded bg-amber-50 text-amber-700 hover:bg-amber-100 text-xs font-semibold"
                                    >
                                        - Salida
                                    </button>

                                    @can('manage-catalog')
                                        <button
                                            type="button"
                                            @click="openProductModal({ mode: 'edit', product: product })"
                                            class="px-2.5 py-1 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-semibold"
                                        >
                                            Editar
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredProducts.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No se encontraron productos con el filtro actual.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 2: Reporte Eloquent Complejo - Top 5 Productos con Mayor Rotación en el Último Mes --}}
        <div x-show="activeTab === 'rotation'" style="display: none;" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-base font-bold text-gray-900">Top 5 Productos con Mayor Rotación (Últimos 30 días)</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Consulta Eloquent agregada en tiempo real ordenando por unidades despachadas (salidas) y volumen total de unidades movidas.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <tr>
                            <th class="px-4 py-3 text-left">Ranking</th>
                            <th class="px-4 py-3 text-left">SKU / Producto</th>
                            <th class="px-4 py-3 text-center">Unidades Salida (30d)</th>
                            <th class="px-4 py-3 text-center">Unidades Entrada (30d)</th>
                            <th class="px-4 py-3 text-center">Volumen Total Movido</th>
                            <th class="px-4 py-3 text-center">N° Operaciones</th>
                            <th class="px-4 py-3 text-center">Stock Actual</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($topRotatingProducts as $index => $item)
                            <tr>
                                <td class="px-4 py-3 font-bold text-indigo-600">#{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900">{{ $item->name }}</div>
                                    <div class="text-xs font-mono text-gray-500">{{ $item->sku }} · {{ $item->category }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-rose-600">-{{ number_format($item->units_out) }} uds.</td>
                                <td class="px-4 py-3 text-center font-semibold text-emerald-600">+{{ number_format($item->units_in) }} uds.</td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900">{{ number_format($item->total_rotated_units) }} uds.</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $item->movements_count }} movs.</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                        {{ $item->current_stock }} uds.
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">Sin movimientos registrados en los últimos 30 días.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 3: Historial Reciente de Movimientos (Kardex) --}}
        <div x-show="activeTab === 'movements'" style="display: none;" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-base font-bold text-gray-900">Últimos Movimientos de Inventario</h3>
                <p class="text-xs text-gray-500 mt-0.5">Trazabilidad completa por usuario, tipo de operación y documento de referencia.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <tr>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-center">Tipo</th>
                            <th class="px-4 py-3 text-right">Cantidad</th>
                            <th class="px-4 py-3 text-left">Motivo / Referencia</th>
                            <th class="px-4 py-3 text-left">Registrado por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recentMovements as $movement)
                            <tr>
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $movement->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900">{{ $movement->product?->name }}</span>
                                    <span class="text-xs font-mono text-gray-400 block">{{ $movement->product?->sku }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($movement->type === 'in')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">ENTRADA</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">SALIDA</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold {{ $movement->type === 'in' ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $movement->type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    <div>{{ $movement->reason }}</div>
                                    @if ($movement->reference)
                                        <div class="text-xs font-mono text-gray-400">Ref: {{ $movement->reference }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    {{ $movement->user?->name }} ({{ strtoupper($movement->user?->role ?? '') }})
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">Aún no hay movimientos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 4: Equipo de la Empresa (Solo Admin) --}}
        @can('manage-users')
            <div x-show="activeTab === 'team'" style="display: none;" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Usuarios de {{ $user->tenant->name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Solo los Administradores pueden crear cuentas para su empresa.</p>
                    </div>
                    <button
                        type="button"
                        @click="userModalOpen = true"
                        class="px-3 py-1.5 bg-gray-800 text-white text-xs font-semibold rounded-lg hover:bg-gray-900"
                    >
                        + Añadir Usuario
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                            <tr>
                                <th class="px-4 py-3 text-left">Nombre</th>
                                <th class="px-4 py-3 text-left">Correo Electrónico</th>
                                <th class="px-4 py-3 text-center">Rol</th>
                                <th class="px-4 py-3 text-right">Fecha Alta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($teamMembers as $member)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $member->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $member->email }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $member->isAdmin() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ strtoupper($member->role) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs text-gray-500">{{ $member->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endcan

        {{-- MODAL 1: Registrar Movimiento de Stock (Admin + Staff) con Previsualización Reactiva --}}
        <div
            x-show="movementModalOpen"
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4"
            @keydown.escape.window="movementModalOpen = false"
        >
            <div @click.outside="movementModalOpen = false" class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-lg font-bold text-gray-900">Registrar Movimiento de Stock</h3>
                    <button @click="movementModalOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form method="POST" action="{{ route('stock-movements.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Producto</label>
                        <select
                            name="product_id"
                            x-model.number="movementForm.product_id"
                            required
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="p.sku + ' — ' + p.name + ' (Stock: ' + p.current_stock + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Tipo de Operación</label>
                            <select
                                name="type"
                                x-model="movementForm.type"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="in">Entrada (+)</option>
                                <option value="out">Salida (-)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Cantidad</label>
                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                x-model.number="movementForm.quantity"
                                required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                    </div>

                    {{-- Previsualización en vivo del stock resultante --}}
                    <div class="rounded-lg p-3 text-xs border" :class="projectedStock < 0 ? 'bg-rose-50 border-rose-200 text-rose-800' : 'bg-gray-50 border-gray-200 text-gray-700'">
                        <div class="flex justify-between">
                            <span>Stock actual:</span>
                            <strong x-text="selectedMovementProduct ? selectedMovementProduct.current_stock + ' uds.' : '0 uds.'"></strong>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span>Stock resultante proyectado:</span>
                            <strong x-text="projectedStock + ' uds.'"></strong>
                        </div>
                        <p x-show="projectedStock < 0" class="mt-1 font-semibold text-rose-600">
                            ⚠️ La cantidad de salida supera el stock disponible.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Motivo / Concepto</label>
                        <input
                            type="text"
                            name="reason"
                            x-model="movementForm.reason"
                            placeholder="Ej. Compra a proveedor, Venta mostrador, Merma..."
                            required
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Documento / Referencia (Opcional)</label>
                        <input
                            type="text"
                            name="reference"
                            x-model="movementForm.reference"
                            placeholder="Ej. FAC-4021, OC-109, BOL-882"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="movementModalOpen = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="projectedStock < 0 || movementForm.quantity < 1"
                            :class="projectedStock < 0 ? 'opacity-50 cursor-not-allowed bg-gray-400' : 'bg-indigo-600 hover:bg-indigo-700'"
                            class="px-4 py-2 text-sm font-semibold text-white rounded-lg shadow-sm transition"
                        >
                            Guardar Movimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 2: Crear / Editar Producto (Solo Admin) --}}
        @can('manage-catalog')
            <div
                x-show="productModalOpen"
                style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4"
                @keydown.escape.window="productModalOpen = false"
            >
                <div @click.outside="productModalOpen = false" class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-4">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-lg font-bold text-gray-900" x-text="productForm.mode === 'edit' ? 'Editar Producto' : 'Nuevo Producto en Catálogo'"></h3>
                        <button @click="productModalOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <form :action="productForm.mode === 'edit' ? '/products/' + productForm.id : '{{ route('products.store') }}'" method="POST" class="space-y-4">
                        @csrf
                        <template x-if="productForm.mode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">SKU</label>
                                <input type="text" name="sku" x-model="productForm.sku" required class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Categoría</label>
                                <input type="text" name="category" x-model="productForm.category" placeholder="Ej. Herramientas, Analgésicos..." class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Nombre del Producto</label>
                            <input type="text" name="name" x-model="productForm.name" required class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Descripción</label>
                            <textarea name="description" x-model="productForm.description" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Precio Unitario ($)</label>
                                <input type="number" step="0.01" min="0" name="price" x-model="productForm.price" required class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Alerta Stock Mín.</label>
                                <input type="number" min="0" name="min_stock_alert" x-model="productForm.min_stock_alert" required class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div x-show="productForm.mode === 'create'">
                                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Stock Inicial</label>
                                <input type="number" min="0" name="initial_stock" x-model="productForm.initial_stock" class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="productModalOpen = false" class="px-4 py-2 text-sm text-gray-600">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg">
                                Guardar Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        {{-- MODAL 3: Crear Usuario en el Tenant Actual (Solo Admin) --}}
        @can('manage-users')
            <div
                x-show="userModalOpen"
                style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-xs p-4"
                @keydown.escape.window="userModalOpen = false"
            >
                <div @click.outside="userModalOpen = false" class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
                    <div class="flex items-center justify-between border-b pb-3">
                        <h3 class="text-lg font-bold text-gray-900">Crear Usuario en {{ $user->tenant->name }}</h3>
                        <button @click="userModalOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('tenant-users.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Nombre Completo</label>
                            <input type="text" name="name" required class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Correo Electrónico</label>
                            <input type="email" name="email" required class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Rol en la Empresa</label>
                            <select name="role" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="staff">Staff (Ver catálogo y registrar entradas/salidas)</option>
                                <option value="admin">Admin (Control total de catálogo y usuarios)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Contraseña Temporal</label>
                            <input type="password" name="password" required minlength="8" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="userModalOpen = false" class="px-4 py-2 text-sm text-gray-600">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-gray-800 hover:bg-gray-900 rounded-lg">
                                Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    </div>

    <script>
        function inventoryApp({ products }) {
            return {
                activeTab: 'catalog',
                search: '',
                onlyLowStock: false,
                products: products,

                movementModalOpen: false,
                movementForm: {
                    product_id: products.length ? products[0].id : null,
                    type: 'in',
                    quantity: 1,
                    reason: '',
                    reference: '',
                },

                productModalOpen: false,
                productForm: {
                    mode: 'create',
                    id: null,
                    sku: '',
                    name: '',
                    category: '',
                    description: '',
                    price: 0,
                    min_stock_alert: 10,
                    initial_stock: 0,
                },

                userModalOpen: false,

                get filteredProducts() {
                    const q = this.search.trim().toLowerCase();
                    return this.products.filter(p => {
                        const matchesQuery = !q ||
                            p.name.toLowerCase().includes(q) ||
                            p.sku.toLowerCase().includes(q) ||
                            (p.category && p.category.toLowerCase().includes(q));
                        const matchesLowStock = !this.onlyLowStock || (p.current_stock <= p.min_stock_alert);
                        return matchesQuery && matchesLowStock;
                    });
                },

                get selectedMovementProduct() {
                    return this.products.find(p => p.id === Number(this.movementForm.product_id)) || null;
                },

                get projectedStock() {
                    if (!this.selectedMovementProduct) return 0;
                    const qty = Number(this.movementForm.quantity) || 0;
                    return this.movementForm.type === 'in'
                        ? this.selectedMovementProduct.current_stock + qty
                        : this.selectedMovementProduct.current_stock - qty;
                },

                openMovementModal({ productId = null, type = 'in' } = {}) {
                    this.movementForm.product_id = productId || (this.products[0] ? this.products[0].id : null);
                    this.movementForm.type = type;
                    this.movementForm.quantity = 1;
                    this.movementForm.reason = '';
                    this.movementForm.reference = '';
                    this.movementModalOpen = true;
                },

                openProductModal({ mode = 'create', product = null } = {}) {
                    this.productForm.mode = mode;
                    if (mode === 'edit' && product) {
                        this.productForm.id = product.id;
                        this.productForm.sku = product.sku;
                        this.productForm.name = product.name;
                        this.productForm.category = product.category || '';
                        this.productForm.description = product.description || '';
                        this.productForm.price = product.price;
                        this.productForm.min_stock_alert = product.min_stock_alert;
                        this.productForm.initial_stock = 0;
                    } else {
                        this.productForm.id = null;
                        this.productForm.sku = '';
                        this.productForm.name = '';
                        this.productForm.category = '';
                        this.productForm.description = '';
                        this.productForm.price = '';
                        this.productForm.min_stock_alert = 10;
                        this.productForm.initial_stock = 0;
                    }
                    this.productModalOpen = true;
                }
            };
        }
    </script>
</x-app-layout>
