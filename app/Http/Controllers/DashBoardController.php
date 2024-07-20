<?php

namespace App\Http\Controllers;

use App\Models\Commandes;
use App\Models\Produits;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        // Calculer le revenu total
        $totalRevenue = Commandes::join('commandesproduits', 'commandes.id', '=', 'commandesproduits.commandes_id')
            ->sum('commandesproduits.prix_total');

        // Obtenir les visites quotidiennes (en supposant que 'created_at' est la date d'inscription)
        $dailyVisits = User::whereDate('created_at', DB::raw('CURDATE()'))->count();

        // Calculer le taux de conversion
        $totalUsers = User::count();
        $totalOrders = Commandes::count();
        $conversionRate = $totalUsers > 0 ? $totalOrders / $totalUsers : 0;

        // Obtenir les commandes par jour
        $ordersPerDay = Commandes::selectRaw('DATE(created_at) as date, count(*) as orders')
            ->groupBy('date')
            ->get();

        // Obtenir les produits les plus vendus
        $mostSoldProducts = Produits::withCount('commandes')
            ->orderBy('commandes_count', 'desc')
            ->take(5)
            ->get();

        // Obtenir les meilleurs clients
        $topCustomers = User::withCount('commandes')
            ->orderBy('commandes_count', 'desc')
            ->take(5)
            ->get();

        // Retourner les statistiques collectées comme réponse JSON
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
