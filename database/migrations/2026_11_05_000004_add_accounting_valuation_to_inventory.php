<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Costo base de reposición y snapshot de Costo Promedio Ponderado (CPP)
            $table->decimal('default_unit_cost', 12, 2)->default(0)->after('price');
            $table->decimal('weighted_average_cost', 12, 4)->default(0)->after('default_unit_cost');
            $table->unsignedInteger('shelf_life_days')->default(180)->after('min_stock_alert');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            // Costo unitario real de la entrada o costo unitario asignado a la salida
            $table->decimal('unit_cost', 12, 4)->default(0)->after('quantity');
            $table->decimal('total_cost', 14, 2)->default(0)->after('unit_cost');

            // Control de lotes FIFO (para movimientos type = 'in')
            $table->unsignedInteger('remaining_quantity')->nullable()->after('total_cost');
            $table->string('batch_code', 64)->nullable()->after('reference');
            $table->date('expires_at')->nullable()->after('batch_code');

            // Snapshot del Costo Promedio Ponderado tras este movimiento (Kardex Valorizado)
            $table->decimal('running_qty_balance', 12, 2)->default(0)->after('expires_at');
            $table->decimal('running_avg_cost', 12, 4)->default(0)->after('running_qty_balance');
            $table->decimal('running_valuation', 14, 2)->default(0)->after('running_avg_cost');

            $table->index(['tenant_id', 'product_id', 'type', 'remaining_quantity', 'created_at'], 'idx_fifo_batches');
        });

        // Trazabilidad exacta de qué lote FIFO fue consumido por cada salida
        Schema::create('stock_movement_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('out_movement_id')->constrained('stock_movements')->cascadeOnDelete();
            $table->foreignId('in_batch_movement_id')->constrained('stock_movements')->cascadeOnDelete();
            $table->unsignedInteger('allocated_quantity');
            $table->decimal('unit_cost', 12, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_allocations');

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_fifo_batches');
            $table->dropColumn([
                'unit_cost',
                'total_cost',
                'remaining_quantity',
                'batch_code',
                'expires_at',
                'running_qty_balance',
                'running_avg_cost',
                'running_valuation',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['default_unit_cost', 'weighted_average_cost', 'shelf_life_days']);
        });
    }
};

