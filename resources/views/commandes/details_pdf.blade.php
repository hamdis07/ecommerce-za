<!-- resources/views/commandes/details_pdf.blade.php -->

<h1>Détails de la Commande #{{ $commande_id }}</h1>

<div class="commande-details">
    <p><strong>Montant Total:</strong> {{ $montant_total }} €</p>
    <p><strong>Statut:</strong> {{ $statut }}</p>
    <p><strong>Frais de Livraison:</strong> {{ $frais_livraison }} €</p>

    <h2>Détails de Livraison</h2>
    <p>
        <strong>Adresse:</strong> {{ $details_livraison->adresse }},
        <strong>Ville:</strong> {{ $details_livraison->ville }},
        <strong>Code Postal:</strong> {{ $details_livraison->code_postal }},
        <strong>Téléphone:</strong> {{ $details_livraison->telephone }},
        <strong>Description:</strong> {{ $details_livraison->description }}
    </p>

    <h2>Produits Commandés</h2>
    <table>
        <thead>
            <tr>
                <th>Nom du Produit</th>
                <th>Quantité</th>
                <th>Taille</th>
                <th>Couleur</th>
                <th>Prix Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($produits_commandes as $produit)
                <tr>
                    <td>{{ $produit['nom'] }}</td>
                    <td>{{ $produit['quantite'] }}</td>
                    <td>{{ $produit['taille'] }}</td>
                    <td>{{ $produit['couleur'] }}</td>
                    <td>{{ $produit['prix_total'] }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Détails de Paiement</h2>
    @if($details_paiement)
        <p><strong>Méthode de Paiement:</strong> {{ $details_paiement['methode_paiement'] }}</p>
        <p><strong>Adresse de Facturation:</strong> {{ $details_paiement['adresse_facturation'] }}</p>
        <p><strong>Prix Total:</strong> {{ $details_paiement['prix_total'] }} €</p>
    @else
        <p>Aucun détail de paiement disponible.</p>
    @endif
</div>
