<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryAccountingService
{
    /**
     * Registra un movimiento actualizando simultáneamente capas FIFO y Costo Promedio Ponderado (CPP).
     */
    public function recordValuedMovement(
        Product $product,
        int $userId,
        string $type,
        int $quantity,
        ?float $unitCost,
        string $reason,
        ?string $reference = null,
        ?string $batchCode = null,
        ?string $expiresAt = null
    ): StockMovement {
        return DB::transaction(function () use (
            $product, $userId, $type, $quantity, $unitCost, $reason, $reference, $batchCode, $expiresAt
        ) {
            $lockedProduct = Product::query()
                ->withCalculatedStock()
                ->lockForUpdate()
                ->findOrFail($product->id);

            $currentStock = (int) $lockedProduct->current_stock;
            $currentWac = (float) ($lockedProduct->weighted_average_cost ?: $lockedProduct->default_unit_cost);

            if ($type === StockMovement::TYPE_IN) {
                $effectiveUnitCost = $unitCost ?? (float) $lockedProduct->default_unit_cost;
                $totalMovementCost = round($quantity * $effectiveUnitCost, 2);

                // Recálculo de Costo Promedio Ponderado (CPP / WAC)
                $newStock = $currentStock + $quantity;
                $previousValuation = $currentStock * $currentWac;
                $newWac = $newStock > 0
                    ? round(($previousValuation + $totalMovementCost) / $newStock, 4)
                    : $effectiveUnitCost;

                $lockedProduct->update(['weighted_average_cost' => $newWac]);

                return StockMovement::create([
                    'tenant_id' => $lockedProduct->tenant_id,
                    'product_id' => $lockedProduct->id,
                    'user_id' => $userId,
                    'type' => StockMovement::TYPE_IN,
                    'quantity' => $quantity,
                    'unit_cost' => $effectiveUnitCost,
                    'total_cost' => $totalMovementCost,
                    'remaining_quantity' => $quantity,
                    'reason' => $reason,
                    'reference' => $reference,
                    'batch_code' => $batchCode ?? ('LOTE-' . now()->format('Ymd-His')),
                    'expires_at' => $expiresAt,
                    'running_qty_balance' => $newStock,
                    'running_avg_cost' => $newWac,
                    'running_valuation' => round($newStock * $newWac, 2),
                ]);
            }

            // SALIDA (OUT): Validación de stock y consumo de capas FIFO (PEPS)
            if ($currentStock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuficiente para «{$lockedProduct->name}». Disponible: {$currentStock} uds.",
                ]);
            }

            // Obtener lotes de entrada con saldo disponible ordenados del más antiguo al más nuevo (FIFO estricto)
            $openBatches = StockMovement::query()
                ->where('product_id', $lockedProduct->id)
                ->where('type', StockMovement::TYPE_IN)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $pendingToAllocate = $quantity;
            $fifoTotalCost = 0.0;
            $allocations = [];

            foreach ($openBatches as $batch) {
                if ($pendingToAllocate <= 0) {
                    break;
                }

                $take = min($pendingToAllocate, (int) $batch->remaining_quantity);
                $batchUnitCost = (float) $batch->unit_cost;

                $batch->decrement('remaining_quantity', $take);

                $fifoTotalCost += ($take * $batchUnitCost);
                $pendingToAllocate -= $take;

                $allocations[] = [
                    'tenant_id' => $lockedProduct->tenant_id,
                    'in_batch_movement_id' => $batch->id,
                    'allocated_quantity' => $take,
                    'unit_cost' => $batchUnitCost,
                ];
            }

            // En una salida, el Costo Promedio Ponderado unitario no cambia, solo disminuye el saldo total
            $newStock = $currentStock - $quantity;
            $effectiveFifoUnitCost = $quantity > 0 ? round($fifoTotalCost / $quantity, 4) : $currentWac;

            $outMovement = StockMovement::create([
                'tenant_id' => $lockedProduct->tenant_id,
                'product_id' => $lockedProduct->id,
                'user_id' => $userId,
                'type' => StockMovement::TYPE_OUT,
                'quantity' => $quantity,
                'unit_cost' => $effectiveFifoUnitCost,
                'total_cost' => round($fifoTotalCost, 2),
                'remaining_quantity' => null,
                'reason' => $reason,
                'reference' => $reference,
                'running_qty_balance' => $newStock,
                'running_avg_cost' => $currentWac,
                'running_valuation' => round($newStock * $currentWac, 2),
            ]);

            foreach ($allocations as $alloc) {
                DB::table('stock_movement_allocations')->insert(array_merge($alloc, [
                    'out_movement_id' => $outMovement->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            return $outMovement;
        });
    }

    /**
     * Calcula el Deterioro / Depreciación por Obsolescencia y Vencimiento (NIC 2) sobre lotes FIFO activos.
     */
    public function calculateDepreciationAndObsolescence(): array
    {
        $activeBatches = StockMovement::query()
            ->with('product:id,sku,name,category,shelf_life_days')
            ->where('type', StockMovement::TYPE_IN)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $totalFifoValuation = 0.0;
        $totalProvisionAmount = 0.0;
        $agingBuckets = [
            '0_60_sano' => ['label' => '0–60 días (Sano - 0%)', 'units' => 0, 'gross_value' => 0.0, 'provision' => 0.0],
            '61_120_lento' => ['label' => '61–120 días (Lento - 15%)', 'units' => 0, 'gross_value' => 0.0, 'provision' => 0.0],
            '121_180_riesgo' => ['label' => '121–180 días (Obsolescencia - 40%)', 'units' => 0, 'gross_value' => 0.0, 'provision' => 0.0],
            'mas_180_o_vencido' => ['label' => '>180 días o Vencido (Castigo - 100%)', 'units' => 0, 'gross_value' => 0.0, 'provision' => 0.0],
        ];

        foreach ($activeBatches as $batch) {
            $ageDays = (int) $batch->created_at->diffInDays(now());
            $isExpired = $batch->expires_at && Carbon::parse($batch->expires_at)->isPast();
            $grossBatchValue = (int) $batch->remaining_quantity * (float) $batch->unit_cost;

            if ($isExpired || $ageDays > 180) {
                $rate = 1.00; // 100% castigo por vencimiento u obsolescencia severa
                $bucketKey = 'mas_180_o_vencido';
            } elseif ($ageDays > 120) {
                $rate = 0.40; // 40% provisión
                $bucketKey = '121_180_riesgo';
            } elseif ($ageDays > 60) {
                $rate = 0.15; // 15% provisión
                $bucketKey = '61_120_lento';
            } else {
                $rate = 0.00; // 0% provisión
                $bucketKey = '0_60_sano';
            }

            $provision = round($grossBatchValue * $rate, 2);

            $totalFifoValuation += $grossBatchValue;
            $totalProvisionAmount += $provision;

            $agingBuckets[$bucketKey]['units'] += (int) $batch->remaining_quantity;
            $agingBuckets[$bucketKey]['gross_value'] += $grossBatchValue;
            $agingBuckets[$bucketKey]['provision'] += $provision;
        }

        return [
            'gross_fifo_valuation' => round($totalFifoValuation, 2),
            'obsolescence_provision' => round($totalProvisionAmount, 2),
            'net_realizable_value' => round($totalFifoValuation - $totalProvisionAmount, 2),
            'buckets' => $agingBuckets,
        ];
    }

    /**
     * Estadística de Negocio: Cada cuántos días rota el inventario completo (DIO) e Índice de Rotación.
     */
    public function calculateBusinessTurnoverMetrics(int $daysWindow = 30): array
    {
        $since = now()->subDays($daysWindow);

        // 1. Valor actual del inventario por Costo Promedio Ponderado (CPP)
        $products = Product::query()->withCalculatedStock()->get();
        $currentWacValuation = $products->sum(function (Product $p) {
            $unitCost = (float) ($p->weighted_average_cost ?: $p->default_unit_cost ?: ($p->price * 0.65));
            return max(0, (int) $p->current_stock) * $unitCost;
        });

        // 2. Costo de Ventas (COGS) en la ventana de tiempo (salidas valorizadas)
        $cogsInPeriod = (float) StockMovement::query()
            ->where('type', StockMovement::TYPE_OUT)
            ->where('created_at', '>=', $since)
            ->selectRaw('COALESCE(SUM(CASE WHEN total_cost > 0 THEN total_cost ELSE quantity * unit_cost END), 0) as cogs')
            ->value('cogs');

        $dailyCogs = $daysWindow > 0 ? ($cogsInPeriod / $daysWindow) : 0;

        // 3. Índice de Rotación en el período y Anualizado
        $periodTurnoverRatio = $currentWacValuation > 0
            ? round($cogsInPeriod / $currentWacValuation, 2)
            : 0.0;

        $annualizedTurnoverRatio = round($periodTurnoverRatio * (365 / $daysWindow), 2);

        // 4. DIO (Days Inventory Outstanding): ¿Cada cuántos días rota el inventario completo?
        $daysToRotateEntireInventory = $dailyCogs > 0
            ? round($currentWacValuation / $dailyCogs, 1)
            : null;

        return [
            'window_days' => $daysWindow,
            'current_wac_valuation' => round($currentWacValuation, 2),
            'period_cogs' => round($cogsInPeriod, 2),
            'daily_cogs' => round($dailyCogs, 2),
            'period_turnover_ratio' => $periodTurnoverRatio,
            'annualized_turnover_ratio' => $annualizedTurnoverRatio,
            'days_to_rotate_inventory' => $daysToRotateEntireInventory, // Ej: 21.4 días
        ];
    }
}

