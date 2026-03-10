<?php
/**
 * E-Lib - Script temporaire pour créer un compte administrateur
 * À supprimer après utilisation pour des raisons de sécurité
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

try {
    $db = DatabaseManager::getInstance();
    
    // Vérifier si un admin existe déjà
    $existingAdmin = $db->fetchOne("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    
    if ($existingAdmin) {
        echo "<h2>✅ Un compte administrateur existe déjà</h2>";
        echo "<p>Utilisez les identifiants existants pour vous connecter.</p>";
        echo "<p><strong>Compte par défaut :</strong></p>";
        echo "<ul>";
        echo "<li>Nom d'utilisateur : <code>admin</code></li>";
        echo "<li>Mot de passe : <code>admin123</code></li>";
        echo "</ul>";
    } else {
        // Créer un compte admin
        $username = 'admin';
        $email = 'admin@elib.local';
        $password = 'admin123';
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')";
        $db->executeQuery($sql, [$username, $email, $passwordHash]);
        
        echo "<h2>✅ Compte administrateur créé avec succès !</h2>";
        echo "<p><strong>Identifiants de connexion :</strong></p>";
        echo "<ul>";
        echo "<li>Nom d'utilisateur : <code>$username</code></li>";
        echo "<li>Mot de passe : <code>$password</code></li>";
        echo "</ul>";
    }
    
    echo "<hr>";
    echo "<p><strong>Étapes suivantes :</strong></p>";
    echo "<ol>";
    echo "<li><a href='../login.php'>Se connecter avec ces identifiants</a></li>";
    echo "<li><a href='setup.php'>Accéder au setup</a></li>";
    echo "<li><strong>Supprimer ce fichier</strong> (create_admin.php) pour la sécurité</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur</h2>";
    echo "<p>Impossible de créer le compte admin : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Vérifiez que :</p>";
    echo "<ul>";
    echo "<li>La base de données est configurée correctement</li>";
    echo "<li>Les tables existent (exécutez le schéma SQL)</li>";
    echo "<li>Les extensions PHP sont activées</li>";
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création compte admin - E-Lib</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        ul, ol { line-height: 1.6; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; color: #856404; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 E-Lib - Création compte administrateur</h1>
        
        <div class="warning">
            <strong>⚠️ Sécurité :</strong> Supprimez ce fichier après utilisation !
        </div>
        
        <?php // Le contenu PHP s'affiche ici ?>
        
        <hr>
        <p><small>Script temporaire - À supprimer après utilisation</small></p>
    </div>
</body>
</html>