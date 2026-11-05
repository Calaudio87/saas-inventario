<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryAccountingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryDashboardController extends Controller
{
    public function index(Request $request, InventoryAccountingService $accountingService): View
    {
        $user = $request->user()->load('tenant');

        $products = Product::query()
            ->withCalculatedStock()
            ->orderBy('name')
            ->get();

        $topRotatingProducts = Product::query()
            ->topRotatingLastMonth(5)
            ->get();

        $recentMovements = StockMovement::query()
            ->with(['product:id,name,sku', 'user:id,name,role'])
            ->latest('created_at')
            ->limit(15)
            ->get();

        // Lotes FIFO activos ordenados del más antiguo al más nuevo (PEPS)
        $fifoBatches = StockMovement::query()
            ->with('product:id,name,sku,category')
            ->where('type', StockMovement::TYPE_IN)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $turnoverMetrics = $accountingService->calculateBusinessTurnoverMetrics(30);
        $obsolescenceReport = $accountingService->calculateDepreciationAndObsolescence();

        $teamMembers = $user->isAdmin()
            ? User::query()->forCurrentTenant()->orderBy('role')->orderBy('name')->get()
            : collect();

        $kpis = [
            'total_products' => $products->count(),
            'total_units_in_stock' => $products->sum(fn (Product $p) => (int) $p->current_stock),
            'low_stock_count' => $products->filter(fn (Product $p) => $p->current_stock <= $p->min_stock_alert)->count(),
            'inventory_valuation' => $turnoverMetrics['current_wac_valuation'],
        ];

        return view('dashboard', compact(
            'user',
            'products',
            'topRotatingProducts',
            'recentMovements',
            'fifoBatches',
            'turnoverMetrics',
            'obsolescenceReport',
            'teamMembers',
            'kpis'
        ));
    }
}
