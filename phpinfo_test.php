<?php
echo "<h1>Test PHP Extensions</h1>";

// Test des extensions critiques
$extensions = [
    'pdo' => 'PDO (Base de données)',
    'pdo_mysql' => 'PDO MySQL (MySQL)',
    'zip' => 'ZIP (Fichiers EPUB)',
    'gd' => 'GD (Images)',
    'mbstring' => 'Multibyte String (UTF-8)'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Extension</th><th>Status</th><th>Description</th></tr>";

foreach ($extensions as $ext => $desc) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? "<span style='color: green;'>✅ ACTIVÉE</span>" : "<span style='color: red;'>❌ DÉSACTIVÉE</span>";
    $row_color = $loaded ? "#d4edda" : "#f8d7da";
    
    echo "<tr style='background: $row_color;'>";
    echo "<td><strong>$ext</strong></td>";
    echo "<td>$status</td>";
    echo "<td>$desc</td>";
    echo "</tr>";
}

echo "</table>";

// Vérification spéciale pour PDO MySQL
if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
    echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2 style='color: #155724;'>🎉 SUCCÈS !</h2>";
    echo "<p>Les extensions PDO sont activées. Vous pouvez maintenant utiliser E-Lib.</p>";
    echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Se connecter à E-Lib</a></p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h2 style='color: #721c24;'>❌ PROBLÈME</h2>";
    echo "<p>Les extensions PDO ne sont pas activées. Suivez les instructions ci-dessus.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Informations PHP</h3>";
echo "<p><strong>Version PHP :</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Fichier php.ini :</strong> " . php_ini_loaded_file() . "</p>";
echo "<p><strong>SAPI :</strong> " . php_sapi_name() . "</p>";

// Bouton pour voir phpinfo complet
echo "<p><a href='?phpinfo=1' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px;'>Voir phpinfo() complet</a></p>";

if (isset($_GET['phpinfo'])) {
    echo "<hr><h2>phpinfo() complet</h2>";
    phpinfo();
}
?>