<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('record-movements');

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            // El Global Scope TenantScope garantiza que solo se resuelvan productos del Tenant actual
            $product = Product::query()
                ->withCalculatedStock()
                ->lockForUpdate()
                ->findOrFail($validated['product_id']);

            if ($validated['type'] === StockMovement::TYPE_OUT && $product->current_stock < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuficiente para «{$product->name}». Disponible actual: {$product->current_stock} uds.",
                ]);
            }

            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => (int) $validated['quantity'],
                'reason' => $validated['reason'],
                'reference' => $validated['reference'] ?? null,
            ]);
        });

        $tipoTexto = $validated['type'] === 'in' ? 'Entrada' : 'Salida';

        return redirect()->route('dashboard')
            ->with('status', "{$tipoTexto} de {$validated['quantity']} uds. registrada correctamente.");
    }
}