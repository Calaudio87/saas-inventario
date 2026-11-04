<?php


namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'category',
        'description',
        'price',
        'min_stock_alert',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'min_stock_alert' => 'integer',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Calcula el stock actual en SQL mediante una subconsulta correlacionada (sin N+1).
     */
    public function scopeWithCalculatedStock(Builder $query): Builder
    {
        return $query->addSelect([
            'calculated_stock' => StockMovement::query()
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0)")
                ->whereColumn('stock_movements.product_id', 'products.id')
                ->whereColumn('stock_movements.tenant_id', 'products.tenant_id'),
        ]);
    }

    /**
     * Accessor $product->current_stock
     */
    public function getCurrentStockAttribute(): int
    {
        if (array_key_exists('calculated_stock', $this->attributes)) {
            return (int)$this->attributes['calculated_stock'];
        }

        return (int)$this->stockMovements()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END), 0) as net_stock")
            ->value('net_stock');
    }

    /**
     * Reporte Eloquent complejo: Top 5 productos con mayor rotación en el último mes.
     * Ordena por unidades despachadas (salidas) y volumen total de unidades movidas.
     */
    public function scopeTopRotatingLastMonth(Builder $query, int $limit = 5, ?Carbon $since = null): Builder
    {
        $sinceDate = $since ?? now()->subDays(30);

        return $query
            ->select('products.*')
            ->withCalculatedStock()
            ->join('stock_movements as sm', function ($join) use ($sinceDate) {
                $join->on('sm.product_id', '=', 'products.id')
                    ->on('sm.tenant_id', '=', 'products.tenant_id')
                    ->where('sm.created_at', '>=', $sinceDate);
            })
            ->selectRaw("SUM(CASE WHEN sm.type = 'out' THEN sm.quantity ELSE 0 END) as units_out")
            ->selectRaw("SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE 0 END) as units_in")
            ->selectRaw("SUM(sm.quantity) as total_rotated_units")
            ->selectRaw("COUNT(sm.id) as movements_count")
            ->groupBy('products.id')
            ->orderByDesc('units_out')
            ->orderByDesc('total_rotated_units')
            ->limit($limit);
    }
}

