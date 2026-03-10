<?php
/**
 * Script automatique pour activer PDO MySQL
 */

echo "🔧 E-Lib - Activation automatique PDO MySQL\n";
echo "==========================================\n\n";

// Fonction pour activer une extension dans php.ini
function activateExtension($phpIniPath, $extensionName) {
    if (!file_exists($phpIniPath)) {
        return ['success' => false, 'message' => "Fichier php.ini non trouvé: $phpIniPath"];
    }
    
    if (!is_writable($phpIniPath)) {
        return ['success' => false, 'message' => "Impossible d'écrire dans php.ini: $phpIniPath"];
    }
    
    $content = file_get_contents($phpIniPath);
    if ($content === false) {
        return ['success' => false, 'message' => "Impossible de lire php.ini"];
    }
    
    // Vérifier si l'extension est déjà activée
    if (preg_match('/^extension=' . preg_quote($extensionName) . '$/m', $content)) {
        return ['success' => true, 'message' => "Extension $extensionName déjà activée"];
    }
    
    // Chercher les lignes commentées possibles
    $patterns = [
        ";extension=$extensionName",
        ";extension=php_$extensionName.dll",
        "; extension=$extensionName",
        "; extension=php_$extensionName.dll"
    ];
    
    $found = false;
    $originalContent = $content;
    
    foreach ($patterns as $pattern) {
        if (strpos($content, $pattern) !== false) {
            $replacement = str_replace([';', '; '], '', $pattern);
            $content = str_replace($pattern, $replacement, $content);
            $found = true;
            echo "   ✅ Trouvé et activé: $pattern -> $replacement\n";
            break;
        }
    }
    
    // Si pas trouvé, ajouter l'extension
    if (!$found) {
        $content .= "\n; Extension ajoutée automatiquement par E-Lib\nextension=$extensionName\n";
        echo "   ✅ Extension ajoutée: extension=$extensionName\n";
    }
    
    // Créer une sauvegarde
    $backup = $phpIniPath . '.backup.' . date('Y-m-d_H-i-s');
    if (copy($phpIniPath, $backup)) {
        echo "   💾 Sauvegarde créée: $backup\n";
    }
    
    // Sauvegarder le fichier modifié
    if (file_put_contents($phpIniPath, $content) !== false) {
        return ['success' => true, 'message' => "Extension $extensionName activée avec succès"];
    } else {
        // Restaurer le contenu original en cas d'erreur
        file_put_contents($phpIniPath, $originalContent);
        return ['success' => false, 'message' => "Erreur lors de l'écriture du fichier"];
    }
}

// Détecter les installations PHP
echo "1. Détection des installations PHP:\n";

$installations = [];

// PHP Web (actuel)
$webPhpIni = php_ini_loaded_file();
if ($webPhpIni) {
    $installations['web'] = [
        'type' => 'Web (Apache)',
        'php_ini' => $webPhpIni,
        'version' => PHP_VERSION
    ];
    echo "   ✅ PHP Web: " . PHP_VERSION . " ($webPhpIni)\n";
}

// PHP CLI
$cliPhpIni = null;
$output = [];
exec('php --ini 2>nul', $output, $return_var);
if ($return_var === 0) {
    foreach ($output as $line) {
        if (strpos($line, 'Loaded Configuration File:') !== false) {
            $cliPhpIni = trim(str_replace('Loaded Configuration File:', '', $line));
            if ($cliPhpIni && $cliPhpIni !== '(none)') {
                $installations['cli'] = [
                    'type' => 'CLI (Ligne de commande)',
                    'php_ini' => $cliPhpIni,
                    'version' => 'CLI'
                ];
                echo "   ✅ PHP CLI: ($cliPhpIni)\n";
            }
            break;
        }
    }
}

if (empty($installations)) {
    echo "   ❌ Aucune installation PHP détectée\n";
    exit(1);
}

echo "\n2. Vérification de l'état actuel de PDO MySQL:\n";
echo "   - PDO: " . (extension_loaded('pdo') ? '✅ OK' : '❌ NON') . "\n";
echo "   - PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ OK' : '❌ NON') . "\n";

if (extension_loaded('pdo_mysql')) {
    echo "\n✅ PDO MySQL est déjà activé ! Aucune action nécessaire.\n";
    exit(0);
}

echo "\n3. Activation de PDO MySQL dans les fichiers php.ini:\n";

$allSuccess = true;
$needsRestart = false;

foreach ($installations as $type => $install) {
    echo "\n   📝 Traitement de {$install['type']}:\n";
    echo "      Fichier: {$install['php_ini']}\n";
    
    $result = activateExtension($install['php_ini'], 'pdo_mysql');
    
    if ($result['success']) {
        echo "      ✅ " . $result['message'] . "\n";
        $needsRestart = true;
    } else {
        echo "      ❌ " . $result['message'] . "\n";
        $allSuccess = false;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";

if ($allSuccess && $needsRestart) {
    echo "✅ PDO MySQL activé avec succès !\n\n";
    echo "🔄 IMPORTANT - Redémarrage requis:\n";
    echo "   1. Ouvrez le panneau de contrôle XAMPP\n";
    echo "   2. Arrêtez Apache (bouton 'Stop')\n";
    echo "   3. Redémarrez Apache (bouton 'Start')\n";
    echo "   4. Testez à nouveau avec: php admin/test_mysql.php\n\n";
} elseif (!$allSuccess) {
    echo "❌ Certaines activations ont échoué.\n";
    echo "   Vous devrez peut-être modifier manuellement les fichiers php.ini\n";
    echo "   ou ajuster les permissions de fichiers.\n\n";
} else {
    echo "ℹ️  Aucune modification nécessaire.\n\n";
}

echo "📋 Prochaines étapes:\n";
echo "   1. Redémarrez Apache dans XAMPP\n";
echo "   2. Exécutez: php admin/test_mysql.php\n";
echo "   3. Si OK, exécutez: php admin/setup.php\n";
echo "   4. Accédez à l'interface web via: http://localhost/Biblio/\n";

echo "\nScript terminé - " . date('Y-m-d H:i:s') . "\n";
?>