<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\InventoryAccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StockMovementController extends Controller
{
    public function store(Request $request, InventoryAccountingService $accountingService): RedirectResponse
    {
        Gate::authorize('record-movements');

        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'batch_code' => ['nullable', 'string', 'max:64'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);

        $accountingService->recordValuedMovement(
            product: $product,
            userId: $request->user()->id,
            type: $validated['type'],
            quantity: (int) $validated['quantity'],
            unitCost: isset($validated['unit_cost']) ? (float) $validated['unit_cost'] : null,
            reason: $validated['reason'],
            reference: $validated['reference'] ?? null,
            batchCode: $validated['batch_code'] ?? null,
            expiresAt: $validated['expires_at'] ?? null,
        );

        $tipoTexto = $validated['type'] === 'in' ? 'Entrada' : 'Salida';

        return redirect()->route('dashboard')
            ->with('status', "{$tipoTexto} de {$validated['quantity']} uds. valorizada (FIFO + CPP) correctamente.");
    }
}
