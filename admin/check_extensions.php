<?php
/**
 * E-Lib - Vérification des extensions PHP
 * Script pour diagnostiquer et guider l'activation des extensions
 */

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Lib - Vérification des extensions PHP</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
        .status { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        ol { line-height: 1.6; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Vérification des extensions PHP</h1>
        
        <?php
        // Vérifier les extensions critiques
        $pdo_loaded = extension_loaded('pdo');
        $pdo_mysql_loaded = extension_loaded('pdo_mysql');
        $mysql_attr_defined = defined('PDO::MYSQL_ATTR_INIT_COMMAND');
        
        echo "<h2>📋 État des extensions</h2>";
        
        if ($pdo_loaded) {
            echo "<div class='status success'>✅ Extension PDO : Activée</div>";
        } else {
            echo "<div class='status error'>❌ Extension PDO : Désactivée (CRITIQUE)</div>";
        }
        
        if ($pdo_mysql_loaded) {
            echo "<div class='status success'>✅ Extension PDO MySQL : Activée</div>";
        } else {
            echo "<div class='status error'>❌ Extension PDO MySQL : Désactivée (CRITIQUE)</div>";
        }
        
        if ($mysql_attr_defined) {
            echo "<div class='status success'>✅ Constantes MySQL PDO : Disponibles</div>";
        } else {
            echo "<div class='status warning'>⚠️ Constantes MySQL PDO : Non disponibles</div>";
        }
        
        // Afficher les instructions si des extensions manquent
        if (!$pdo_loaded || !$pdo_mysql_loaded) {
            echo "<h2>🔧 Comment activer les extensions manquantes</h2>";
            
            echo "<div class='status error'>";
            echo "<strong>❌ Extensions critiques manquantes !</strong><br>";
            echo "L'application ne peut pas fonctionner sans PDO et PDO MySQL.";
            echo "</div>";
            
            echo "<h3>📝 Instructions pour XAMPP :</h3>";
            echo "<ol>";
            echo "<li><strong>Ouvrez le fichier php.ini</strong><br>";
            echo "<div class='code'>C:\\xampp\\php\\php.ini</div></li>";
            
            echo "<li><strong>Recherchez ces lignes et supprimez le point-virgule (;) au début :</strong><br>";
            echo "<div class='code'>";
            if (!$pdo_loaded) echo ";extension=pdo<br>";
            if (!$pdo_mysql_loaded) echo ";extension=pdo_mysql<br>";
            echo "</div>";
            echo "Elles doivent devenir :<br>";
            echo "<div class='code'>";
            if (!$pdo_loaded) echo "extension=pdo<br>";
            if (!$pdo_mysql_loaded) echo "extension=pdo_mysql<br>";
            echo "</div></li>";
            
            echo "<li><strong>Sauvegardez le fichier php.ini</strong></li>";
            echo "<li><strong>Redémarrez Apache</strong> depuis le panneau de contrôle XAMPP</li>";
            echo "<li><strong>Actualisez cette page</strong> pour vérifier</li>";
            echo "</ol>";
            
        } else {
            echo "<div class='status success'>";
            echo "<strong>✅ Extensions de base activées !</strong><br>";
            echo "Vous pouvez maintenant utiliser l'application E-Lib.";
            echo "</div>";
            
            echo "<p><a href='setup.php' class='btn btn-primary'>🚀 Continuer vers le Setup</a></p>";
        }
        
        // Vérifications supplémentaires
        echo "<h2>🔍 Extensions optionnelles</h2>";
        
        $optional_extensions = [
            'zip' => 'Support des fichiers EPUB',
            'gd' => 'Redimensionnement des images',
            'mbstring' => 'Support UTF-8 avancé',
            'curl' => 'Requêtes HTTP'
        ];
        
        foreach ($optional_extensions as $ext => $desc) {
            if (extension_loaded($ext)) {
                echo "<div class='status success'>✅ $ext : Activée - $desc</div>";
            } else {
                echo "<div class='status warning'>⚠️ $ext : Désactivée - $desc</div>";
            }
        }
        
        // Informations sur le système
        echo "<h2>💻 Informations système</h2>";
        echo "<div class='info'>";
        echo "<strong>Version PHP :</strong> " . PHP_VERSION . "<br>";
        echo "<strong>SAPI :</strong> " . php_sapi_name() . "<br>";
        echo "<strong>OS :</strong> " . PHP_OS . "<br>";
        echo "<strong>Architecture :</strong> " . (PHP_INT_SIZE * 8) . " bits<br>";
        echo "<strong>Fichier php.ini :</strong> " . php_ini_loaded_file();
        echo "</div>";
        
        // Guide de dépannage
        echo "<h2>🆘 Dépannage</h2>";
        echo "<div class='info'>";
        echo "<strong>Si les extensions ne s'activent pas :</strong><br>";
        echo "1. Vérifiez que vous modifiez le bon fichier php.ini<br>";
        echo "2. Assurez-vous qu'il n'y a pas d'espaces avant 'extension='<br>";
        echo "3. Redémarrez complètement XAMPP (Stop puis Start)<br>";
        echo "4. Vérifiez les logs d'erreur Apache dans XAMPP<br>";
        echo "5. Essayez de réinstaller XAMPP si le problème persiste";
        echo "</div>";
        
        ?>
        
        <hr style="margin: 30px 0;">
        <p>
            <a href="diagnostic.php" class="btn btn-primary">📊 Diagnostic complet</a>
            <a href="../index.php" class="btn btn-success">🏠 Retour à l'accueil</a>
        </p>
        
        <p><small>Vérification effectuée le <?= date('d/m/Y à H:i:s') ?></small></p>
    </div>
</body>
</html>