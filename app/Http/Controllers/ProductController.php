<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'min_stock_alert' => ['required', 'integer', 'min:0'],
            'initial_stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $product = Product::create([
            'sku' => strtoupper(trim($validated['sku'])),
            'name' => $validated['name'],
            'category' => $validated['category'] ?? 'General',
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'min_stock_alert' => $validated['min_stock_alert'],
        ]);

        if (! empty($validated['initial_stock']) && $validated['initial_stock'] > 0) {
            $product->stockMovements()->create([
                'user_id' => $request->user()->id,
                'type' => 'in',
                'quantity' => (int) $validated['initial_stock'],
                'reason' => 'Inventario inicial de apertura',
                'reference' => 'INIT-' . $product->sku,
            ]);
        }

        return redirect()->route('dashboard')
            ->with('status', "Producto «{$product->name}» creado correctamente.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'min_stock_alert' => ['required', 'integer', 'min:0'],
        ]);

        $product->update([
            'sku' => strtoupper(trim($validated['sku'])),
            'name' => $validated['name'],
            'category' => $validated['category'] ?? 'General',
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'min_stock_alert' => $validated['min_stock_alert'],
        ]);

        return redirect()->route('dashboard')
            ->with('status', "Producto «{$product->name}» actualizado.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('manage-catalog');

        $name = $product->name;
        $product->delete();

        return redirect()->route('dashboard')
            ->with('status', "Producto «{$name}» eliminado del catálogo.");
    }
}