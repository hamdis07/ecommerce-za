<?php
use App\Models\Commandes;
use App\Models\Produits;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalRevenue = Commandes::where('status', 'completed')->sum('total');
        $dailyVisits = User::whereDate('created_at', DB::raw('CURDATE()'))->count(); // Example of daily visits if they register
        $conversionRate = Commandes::count() / User::count(); // Simplified conversion rate
        $ordersPerDay = Commandes::selectRaw('DATE(created_at) as date, count(*) as orders')
            ->groupBy('date')
            ->get();
        $mostSoldProducts = Produits::withCount('commandes')
            ->orderBy('commandes_count', 'desc')
            ->take(5)
            ->get();
        $topCustomers = User::withCount('commandes')
            ->orderBy('commandes_count', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'dailyVisits' => $dailyVisits,
            'conversionRate' => $conversionRate,
            'ordersPerDay' => $ordersPerDay,
            'mostSoldProducts' => $mostSoldProducts,
            'topCustomers' => $topCustomers,
        ]);
    }
}
