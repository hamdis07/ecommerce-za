<?php

namespace App\Exports;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\Commandes;
use App\Models\LivraisonDetails;
use App\Models\Paiements;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class CommandesExport implements FromArray, WithHeadings, WithMapping
{
    use Exportable;

    protected $commandes;

    public function __construct($commandes)
    {
        $this->commandes = $commandes;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->commandes as $commande) {
            $livraison = $commande->livraisonDetails; // Use relationship instead of querying again
            $paiement = $commande->paiement; // Use relationship instead of querying again
            $produits = $commande->produits;

            foreach ($produits as $produit) {
                $data[] = [
                    'Commande ID' => $commande->id,
                    'Adresse Livraison' => $livraison ? $livraison->adresse : 'N/A',
                    'Ville Livraison' => $livraison ? $livraison->ville : 'N/A',
                    'Code Postal Livraison' => $livraison ? $livraison->code_postal : 'N/A',
                    'Téléphone Livraison' => $livraison ? $livraison->telephone : 'N/A',
                    'Description Livraison' => $livraison ? $livraison->description : 'N/A',
                    'Méthode de Paiement' => $paiement ? $paiement->methode_paiement : 'N/A',
                    'Montant Total' => $commande->montant_total,
                    'Produit ID' => $produit->id,
                    'Produit Nom' => $produit->nom,
                    'Quantité' => $produit->pivot->quantite,
                    'Prix Unitaire' => $produit->prix,
                    'Prix Total' => $produit->pivot->prix_total,
                    'Adresse Facturation' => $paiement ? $paiement->adresse_facturation : 'N/A',
                    'Date Paiement' => $paiement ? $paiement->created_at->format('Y-m-d H:i:s') : 'N/A',
                ];
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Commande ID',
            'Adresse Livraison',
            'Ville Livraison',
            'Code Postal Livraison',
            'Téléphone Livraison',
            'Description Livraison',
            'Méthode de Paiement',
            'Montant Total',
            'Produit ID',
            'Produit Nom',
            'Quantité',
            'Prix Unitaire',
            'Prix Total',
            'Adresse Facturation',
            'Date Paiement',
        ];
    }

}
