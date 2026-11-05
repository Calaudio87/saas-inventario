<?php


namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class StockMovement extends Model
{
    use HasFactory, BelongsToTenant;

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'remaining_quantity',
        'reason',
        'reference',
        'batch_code',
        'expires_at',
        'running_qty_balance',
        'running_avg_cost',
        'running_valuation',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'remaining_quantity' => 'integer',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
            'running_qty_balance' => 'decimal:2',
            'running_avg_cost' => 'decimal:4',
            'running_valuation' => 'decimal:2',
            'expires_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement) {
            // Garantiza que el producto exista dentro del mismo Tenant
            $productExistsInTenant = Product::withoutGlobalScopes()
                ->where('id', $movement->product_id)
                ->where('tenant_id', $movement->tenant_id)
                ->exists();

            if (!$productExistsInTenant) {
                throw ValidationException::withMessages([
                    'product_id' => 'El producto seleccionado no pertenece a tu empresa.',
                ]);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

