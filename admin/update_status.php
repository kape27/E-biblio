<?php
/**
 * E-Lib - API pour vérifier le statut des mises à jour
 * Utilisé par le dashboard admin pour afficher les notifications
 */

session_start();

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est admin
$auth = new AuthManager();
if (!$auth->isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

try {
    $db = DatabaseManager::getInstance();
    
    // Versions disponibles (à synchroniser avec setup.php)
    $availableVersions = [
        '1.1.0' => 'Système de favoris',
        '1.2.0' => 'Récupération de mot de passe', 
        '1.3.0' => 'Système de notation',
        '1.4.0' => 'Commentaires et avis',
        '1.5.0' => 'Notifications utilisateur',
        '1.6.0' => 'Statistiques de lecture avancées'
    ];
    
    // Récupérer la version actuelle
    $currentVersion = '1.0.0';
    try {
        $versionResult = $db->fetchOne("SELECT version FROM database_versions ORDER BY applied_at DESC LIMIT 1");
        if ($versionResult) {
            $currentVersion = $versionResult['version'];
        }
    } catch (Exception $e) {
        // Table n'existe pas encore
    }
    
    // Trouver les mises à jour disponibles
    $updatesAvailable = [];
    foreach ($availableVersions as $version => $description) {
        if (version_compare($version, $currentVersion, '>')) {
            $updatesAvailable[] = [
                'version' => $version,
                'description' => $description
            ];
        }
    }
    
    // Récupérer l'historique des versions appliquées
    $appliedVersions = [];
    try {
        $appliedVersions = $db->fetchAll(
            "SELECT version, description, applied_at FROM database_versions ORDER BY applied_at DESC"
        );
    } catch (Exception $e) {
        // Table n'existe pas encore
    }
    
    echo json_encode([
        'success' => true,
        'current_version' => $currentVersion,
        'updates_available' => !empty($updatesAvailable),
        'update_count' => count($updatesAvailable),
        'available_updates' => $updatesAvailable,
        'applied_versions' => $appliedVersions
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la vérification des mises à jour: ' . $e->getMessage()
    ]);
}
?>