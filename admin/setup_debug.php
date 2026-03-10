<?php
/**
 * E-Lib Digital Library - Setup & Database Updates (Version Debug)
 * Script pour diagnostiquer les problèmes de session
 */

// Vérifier que le script est exécuté depuis la ligne de commande ou par un admin
session_start();

// Configuration de sécurité
$isCommandLine = php_sapi_name() === 'cli';
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// DEBUG: Afficher les informations de session
echo "<h2>🔍 Informations de Debug</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; font-family: monospace;'>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Session Status:</strong> " . session_status() . "<br>";
echo "<strong>PHP SAPI:</strong> " . php_sapi_name() . "<br>";
echo "<strong>Is Command Line:</strong> " . ($isCommandLine ? 'OUI' : 'NON') . "<br>";
echo "<strong>Session Role:</strong> " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NON DÉFINI') . "<br>";
echo "<strong>Is Admin:</strong> " . ($isAdmin ? 'OUI' : 'NON') . "<br>";
echo "<strong>Session Data:</strong><br>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

if (!$isCommandLine && !$isAdmin) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>❌ Accès refusé</strong><br>";
    echo "Raison: ";
    if (!isset($_SESSION['role'])) {
        echo "Aucun rôle défini dans la session";
    } elseif ($_SESSION['role'] !== 'admin') {
        echo "Rôle actuel: " . $_SESSION['role'] . " (admin requis)";
    } else {
        echo "Raison inconnue";
    }
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>💡 Solutions possibles:</strong><br>";
    echo "1. <a href='../login.php'>Se reconnecter</a><br>";
    echo "2. <a href='../admin/dashboard.php'>Vérifier le dashboard admin</a><br>";
    echo "3. Vider le cache du navigateur<br>";
    echo "4. Utiliser la ligne de commande: <code>php setup.php</code>";
    echo "</div>";
    
    exit;
} else {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>✅ Accès autorisé</strong><br>";
    echo "Vous pouvez maintenant accéder au setup normal.";
    echo "</div>";
    
    echo "<p><a href='setup.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Aller au Setup</a></p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Lib - Debug Setup</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 E-Lib - Debug Setup</h1>
        
        <?php // Le contenu PHP s'affiche ici ?>
        
        <hr>
        <p><small><a href="../admin/dashboard.php">← Retour au dashboard admin</a></small></p>
    </div>
</body>
</html>