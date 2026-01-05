<?php
/**
 * Script de validation : Vérifie que toutes les colonnes utilisées dans le code existent dans les schémas
 */

// Définition des schémas (colonnes réelles dans les BDD)
$schemas = [
    'utilisateur' => ['utilisateur_id', 'pseudo', 'email', 'password', 'telephone', 'adresse', 'date_naissance', 'photo', 'credit', 'role', 'statut', 'created_at', 'updated_at'],
    'voiture' => ['voiture_id', 'utilisateur_id', 'modele', 'marque', 'immatriculation', 'couleur', 'energie', 'places', 'date_premiere_immatriculation', 'created_at'],
    'covoiturage' => ['covoiturage_id', 'conducteur_id', 'voiture_id', 'ville_depart', 'adresse_depart', 'ville_arrivee', 'adresse_arrivee', 'date_depart', 'date_arrivee', 'places_disponibles', 'prix_par_place', 'statut', 'created_at'],
    'participation' => ['participation_id', 'covoiturage_id', 'passager_id', 'nombre_places', 'credit_utilise', 'statut', 'date_reservation'],
    'avis' => ['avis_id', 'covoiturage_id', 'auteur_id', 'destinataire_id', 'commentaire', 'note', 'statut', 'valide_par', 'date_validation', 'created_at'],
    'transaction_credit' => ['transaction_id', 'utilisateur_id', 'montant', 'type', 'description', 'reference_id', 'reference_type', 'created_at']
];

// Valeurs ENUM valides
$enums = [
    'utilisateur.role' => ['utilisateur', 'employe', 'administrateur'],
    'utilisateur.statut' => ['actif', 'suspendu'],
    'voiture.energie' => ['essence', 'diesel', 'electrique', 'hybride', 'gpl'],
    'covoiturage.statut' => ['planifie', 'en_cours', 'termine', 'annule'],
    'participation.statut' => ['reserve', 'confirme', 'annule', 'termine'],
    'avis.statut' => ['en_attente', 'valide', 'refuse'],
    'transaction_credit.type' => ['credit', 'debit'],
    'transaction_credit.reference_type' => ['participation', 'remboursement', 'bonus', 'commission']
];

// Colonnes INCORRECTES souvent utilisées par erreur
$incorrect_columns = [
    'utilisateur.date_inscription' => 'created_at',
    'utilisateur.credits' => 'credit',
    'voiture.type_carburant' => 'energie',
    'voiture.type_vehicule' => 'energie',
    'voiture.places_disponibles' => 'places',
    'participation.statut_reservation' => 'statut',
    'avis.evaluateur_id' => 'auteur_id',
    'avis.evalue_id' => 'destinataire_id',
    'avis.date_creation' => 'created_at (MySQL) - différence acceptable'
];

echo "=== VALIDATION SCHÉMA BASE DE DONNÉES ===\n\n";

echo "✅ COLONNES CORRECTES DANS LES SCHÉMAS :\n";
foreach ($schemas as $table => $columns) {
    echo "\n$table:\n";
    echo "  " . implode(', ', $columns) . "\n";
}

echo "\n\n✅ VALEURS ENUM CORRECTES :\n";
foreach ($enums as $field => $values) {
    echo "$field: " . implode(', ', $values) . "\n";
}

echo "\n\n❌ COLONNES INCORRECTES À NE PAS UTILISER :\n";
foreach ($incorrect_columns as $wrong => $correct) {
    echo "$wrong → UTILISER: $correct\n";
}

echo "\n\n=== VÉRIFICATION DU CODE ===\n\n";

$files_to_check = array_merge(
    glob(__DIR__ . '/api/*.php'),
    glob(__DIR__ . '/user/*.php'),
    glob(__DIR__ . '/employee/*.php'),
    glob(__DIR__ . '/*.php')
);

$errors = [];

foreach ($files_to_check as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);

    // Vérifier les colonnes incorrectes
    foreach ($incorrect_columns as $wrong => $correct) {
        list($table, $col) = explode('.', $wrong);

        // Chercher utilisation dans requêtes SQL
        if (preg_match("/\b$col\b/i", $content) && !str_contains($content, "// Ignore: $col")) {
            $errors[] = "⚠️  $filename : utilise possiblement '$col' au lieu de '$correct'";
        }
    }

    // Vérifier valeurs ENUM incorrectes pour participation
    if (preg_match("/'confirmee'/", $content)) {
        $errors[] = "❌ $filename : utilise 'confirmee' au lieu de 'confirme'";
    }
    if (preg_match("/participation.*statut.*'en_attente'/", $content)) {
        $errors[] = "❌ $filename : utilise 'en_attente' pour participation (devrait être 'reserve')";
    }
}

if (empty($errors)) {
    echo "✅ AUCUNE ERREUR DÉTECTÉE !\n";
} else {
    echo "ERREURS TROUVÉES :\n\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
}

echo "\n=== FIN DE LA VALIDATION ===\n";
?>
