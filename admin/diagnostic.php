<?php
/**
 * E-Lib - Diagnostic de l'environnement avancé
 * Vérifie la configuration PHP et les extensions (CLI + Apache)
 */

// Masquer les avertissements VCRUNTIME140.dll pour ce diagnostic
error_reporting(E_ALL & ~E_WARNING);

// Fonction pour détecter les installations PHP multiples
function detectPhpInstallations() {
    $installations = [];
    
    // PHP Web (Apache/XAMPP)
    $webPhpIni = php_ini_loaded_file();
    if ($webPhpIni) {
        $installations['web'] = [
            'type' => 'Web (Apache)',
            'php_ini' => $webPhpIni,
            'version' => PHP_VERSION,
            'sapi' => php_sapi_name(),
            'extensions' => get_loaded_extensions()
        ];
    }
    
    // PHP CLI (ligne de commande)
    $cliPhpIni = null;
    $cliVersion = null;
    $cliExtensions = [];
    
    // Essayer de détecter le PHP CLI
    $output = [];
    $return_var = 0;
    
    // Commande pour obtenir les infos CLI
    exec('php --ini 2>nul', $output, $return_var);
    if ($return_var === 0) {
        foreach ($output as $line) {
            if (strpos($line, 'Loaded Configuration File:') !== false) {
                $cliPhpIni = trim(str_replace('Loaded Configuration File:', '', $line));
                break;
            }
        }
    }
    
    // Version CLI
    exec('php -v 2>nul', $output, $return_var);
    if ($return_var === 0 && !empty($output[0])) {
        preg_match('/PHP (\d+\.\d+\.\d+)/', $output[0], $matches);
        $cliVersion = $matches[1] ?? 'Inconnue';
    }
    
    // Extensions CLI
    exec('php -m 2>nul', $output, $return_var);
    if ($return_var === 0) {
        $cliExtensions = array_filter($output, fn($line) => !empty(trim($line)) && !preg_match('/^\[.*\]$/', $line));
    }
    
    if ($cliPhpIni) {
        $installations['cli'] = [
            'type' => 'CLI (Ligne de commande)',
            'php_ini' => $cliPhpIni,
            'version' => $cliVersion,
            'sapi' => 'cli',
            'extensions' => $cliExtensions
        ];
    }
    
    return $installations;
}

// Fonction pour activer une extension dans un php.ini spécifique
function enableExtensionInFile($extensionName, $phpIniPath) {
    if (!$phpIniPath || !file_exists($phpIniPath) || !is_writable($phpIniPath)) {
        return ['success' => false, 'message' => "Impossible d'écrire dans php.ini: $phpIniPath"];
    }
    
    $content = file_get_contents($phpIniPath);
    if ($content === false) {
        return ['success' => false, 'message' => "Impossible de lire php.ini"];
    }
    
    // Vérifier si l'extension est déjà activée
    if (preg_match('/^extension=' . preg_quote($extensionName) . '$/m', $content)) {
        return ['success' => true, 'message' => "Extension $extensionName déjà activée dans $phpIniPath"];
    }
    
    // Chercher la ligne commentée de l'extension
    $patterns = [
        ";extension=$extensionName",
        ";extension=php_$extensionName.dll",
        "; extension=$extensionName",
        "; extension=php_$extensionName.dll"
    ];
    
    $found = false;
    foreach ($patterns as $pattern) {
        if (strpos($content, $pattern) !== false) {
            $replacement = str_replace([';', '; '], '', $pattern);
            $content = str_replace($pattern, $replacement, $content);
            $found = true;
            break;
        }
    }
    
    // Si pas trouvé, ajouter l'extension à la fin
    if (!$found) {
        $content .= "\n; Extension ajoutée automatiquement par E-Lib\nextension=$extensionName\n";
    }
    
    // Sauvegarder le fichier
    $backup = $phpIniPath . '.backup.' . date('Y-m-d_H-i-s');
    copy($phpIniPath, $backup);
    
    if (file_put_contents($phpIniPath, $content) !== false) {
        return ['success' => true, 'message' => "Extension $extensionName activée dans $phpIniPath. Sauvegarde: $backup"];
    } else {
        return ['success' => false, 'message' => "Erreur lors de l'écriture du fichier php.ini"];
    }
}

// Fonction pour activer une extension dans toutes les installations
function enableExtension($extensionName) {
    $installations = detectPhpInstallations();
    $results = [];
    $allSuccess = true;
    
    foreach ($installations as $type => $install) {
        $result = enableExtensionInFile($extensionName, $install['php_ini']);
        $results[] = "[$type] " . $result['message'];
        if (!$result['success']) {
            $allSuccess = false;
        }
    }
    
    return [
        'success' => $allSuccess,
        'message' => implode('<br>', $results)
    ];
}

// Traitement des actions
$message = '';
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'enable_extension') {
        $extension = $_POST['extension'] ?? '';
        if ($extension) {
            $result = enableExtension($extension);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
    } elseif ($_POST['action'] === 'enable_all') {
        $extensions = $_POST['extensions'] ?? [];
        $results = [];
        $allSuccess = true;
        
        foreach ($extensions as $extension) {
            $result = enableExtension($extension);
            $results[] = $result['message'];
            if (!$result['success']) {
                $allSuccess = false;
            }
        }
        
        $message = implode('<br>', $results);
        $messageType = $allSuccess ? 'success' : 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Lib - Diagnostic de l'environnement</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .version { font-family: monospace; background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; text-decoration: none; border-radius: 5px; font-weight: bold; background: #007bff; color: white; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .extension-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; }
        .extension-info { flex: 1; }
        .extension-actions { margin-left: 10px; }
        .message { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .message.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .message.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 E-Lib - Diagnostic de l'environnement</h1>
        
        <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <strong><?= $messageType === 'success' ? '✅' : '❌' ?></strong> <?= htmlspecialchars($message) ?>
            <?php if ($messageType === 'success'): ?>
            <br><strong>⚠️ Important:</strong> Vous devez redémarrer Apache pour que les changements prennent effet.
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php
        // Fonction pour afficher le statut
        function showStatus($condition, $success_msg, $error_msg, $warning_msg = null) {
            if ($condition === true) {
                echo "<div class='status success'>✅ $success_msg</div>";
            } elseif ($condition === false) {
                echo "<div class='status error'>❌ $error_msg</div>";
            } else {
                echo "<div class='status warning'>⚠️ " . ($warning_msg ?: $error_msg) . "</div>";
            }
        }
        
        // Vérification de la version PHP
        echo "<h2>📋 Informations PHP</h2>";
        echo "<table>";
        echo "<tr><th>Version PHP</th><td><span class='version'>" . PHP_VERSION . "</span></td></tr>";
        echo "<tr><th>SAPI</th><td>" . php_sapi_name() . "</td></tr>";
        echo "<tr><th>OS</th><td>" . PHP_OS . "</td></tr>";
        echo "<tr><th>Architecture</th><td>" . (PHP_INT_SIZE * 8) . " bits</td></tr>";
        echo "</table>";
        
        // Vérification des extensions requises
        echo "<h2>🔧 Extensions PHP</h2>";
        
        $extensions = [
            'pdo' => ['PDO', 'Connexion à la base de données', true],
            'pdo_mysql' => ['PDO MySQL', 'Driver MySQL pour PDO', true],
            'zip' => ['ZIP', 'Lecture des fichiers EPUB', false],
            'gd' => ['GD', 'Traitement des images', false],
            'mbstring' => ['Multibyte String', 'Support des caractères UTF-8', false],
            'json' => ['JSON', 'Manipulation des données JSON', true],
            'session' => ['Session', 'Gestion des sessions utilisateur', true],
            'curl' => ['cURL', 'Requêtes HTTP (optionnel)', false]
        ];
        
        echo "<table>";
        echo "<tr><th>Extension</th><th>Description</th><th>Statut</th><th>Requis</th><th>Actions</th></tr>";
        
        foreach ($extensions as $ext => $info) {
            $loaded = extension_loaded($ext);
            $status = $loaded ? "✅ Activée" : "❌ Désactivée";
            $required = $info[2] ? "Oui" : "Non";
            $row_class = $loaded ? "" : ($info[2] ? "style='background:#f8d7da;'" : "style='background:#fff3cd;'");
            
            echo "<tr $row_class>";
            echo "<td><strong>{$info[0]}</strong></td>";
            echo "<td>{$info[1]}</td>";
            echo "<td>$status</td>";
            echo "<td>$required</td>";
            echo "<td>";
            
            if (!$loaded) {
                echo "<form method='post' style='display: inline;'>";
                echo "<input type='hidden' name='action' value='enable_extension'>";
                echo "<input type='hidden' name='extension' value='$ext'>";
                echo "<button type='submit' class='btn btn-warning' style='padding: 5px 10px; font-size: 12px;' onclick='return confirm(\"Activer l\\'extension $ext ? Un redémarrage d\\'Apache sera nécessaire.\")'>Activer</button>";
                echo "</form>";
            } else {
                echo "<span style='color: #28a745; font-size: 12px;'>✓ Active</span>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Vérifications spécifiques
        echo "<h2>⚙️ Configuration</h2>";
        
        showStatus(
            version_compare(PHP_VERSION, '7.4.0', '>='),
            "Version PHP compatible (" . PHP_VERSION . ")",
            "Version PHP trop ancienne. Minimum requis: 7.4.0"
        );
        
        showStatus(
            extension_loaded('pdo') && extension_loaded('pdo_mysql'),
            "Extensions de base de données disponibles",
            "Extensions PDO manquantes - Installation impossible"
        );
        
        showStatus(
            extension_loaded('zip') ? true : null,
            "Extension ZIP disponible - Support EPUB complet",
            "Extension ZIP manquante - Support EPUB limité",
            "Extension ZIP manquante - Les fichiers EPUB ne pourront pas être lus"
        );
        
        showStatus(
            extension_loaded('gd') ? true : null,
            "Extension GD disponible - Redimensionnement des images",
            "Extension GD manquante - Pas de redimensionnement",
            "Extension GD manquante - Les images ne seront pas redimensionnées"
        );
        
        // Vérification des permissions de fichiers
        echo "<h2>📁 Permissions des dossiers</h2>";
        
        $directories = [
            '../uploads/books' => 'Stockage des livres',
            '../uploads/covers' => 'Stockage des couvertures'
        ];
        
        foreach ($directories as $dir => $desc) {
            $exists = is_dir($dir);
            $writable = $exists && is_writable($dir);
            
            if ($exists && $writable) {
                echo "<div class='status success'>✅ $dir - $desc (Lecture/Écriture OK)</div>";
            } elseif ($exists) {
                echo "<div class='status warning'>⚠️ $dir - $desc (Pas d'écriture)</div>";
            } else {
                echo "<div class='status error'>❌ $dir - $desc (Dossier manquant)</div>";
            }
        }
        
        // Test de connexion à la base de données
        echo "<h2>🗄️ Base de données</h2>";
        
        try {
            require_once '../config/database.php';
            $db = DatabaseManager::getInstance();
            
            // Test simple
            $result = $db->fetchOne("SELECT 1 as test");
            
            if ($result && $result['test'] == 1) {
                echo "<div class='status success'>✅ Connexion à la base de données réussie</div>";
                
                // Vérifier les tables principales
                $tables = ['users', 'books', 'categories', 'reading_progress', 'logs'];
                $existing_tables = [];
                
                foreach ($tables as $table) {
                    try {
                        $db->fetchOne("SELECT 1 FROM $table LIMIT 1");
                        $existing_tables[] = $table;
                    } catch (Exception $e) {
                        // Table n'existe pas
                    }
                }
                
                echo "<div class='status info'>📊 Tables trouvées: " . implode(', ', $existing_tables) . "</div>";
                
                if (count($existing_tables) < count($tables)) {
                    echo "<div class='status warning'>⚠️ Certaines tables sont manquantes. Exécutez le script de setup.</div>";
                }
                
            } else {
                echo "<div class='status error'>❌ Connexion établie mais test échoué</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='status error'>❌ Erreur de connexion: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // Informations sur VCRUNTIME140.dll
        echo "<h2>🔧 Problème VCRUNTIME140.dll</h2>";
        echo "<div class='status info'>";
        echo "<strong>Si vous voyez l'erreur VCRUNTIME140.dll :</strong><br>";
        echo "• Téléchargez Microsoft Visual C++ Redistributable 2015-2022<br>";
        echo "• Installez la version x64 (64-bit)<br>";
        echo "• Redémarrez votre ordinateur<br>";
        echo "• Redémarrez Apache dans XAMPP<br>";
        echo "• L'erreur est généralement sans impact sur le fonctionnement";
        echo "</div>";
        
        // Recommandations
        echo "<h2>💡 Recommandations</h2>";
        
        $missingExtensions = [];
        if (!extension_loaded('zip')) {
            $missingExtensions[] = 'zip';
            echo "<div class='status warning'>⚠️ Activez l'extension ZIP dans php.ini pour le support EPUB complet</div>";
        }
        
        if (!extension_loaded('gd')) {
            $missingExtensions[] = 'gd';
            echo "<div class='status warning'>⚠️ Activez l'extension GD dans php.ini pour le redimensionnement des images</div>";
        }
        
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            echo "<div class='status info'>💡 Considérez une mise à jour vers PHP 8.0+ pour de meilleures performances</div>";
        }
        
        // Bouton pour activer toutes les extensions manquantes
        if (!empty($missingExtensions)) {
            echo "<div style='margin: 20px 0; padding: 15px; background: #fff3cd; border-radius: 5px;'>";
            echo "<h3>🚀 Activation rapide</h3>";
            echo "<p>Activez toutes les extensions recommandées manquantes en un clic :</p>";
            echo "<form method='post' style='display: inline;'>";
            foreach ($missingExtensions as $ext) {
                echo "<input type='hidden' name='extensions[]' value='$ext'>";
            }
            echo "<button type='submit' name='action' value='enable_all' class='btn btn-success' onclick='return confirm(\"Activer toutes les extensions manquantes ? Un redémarrage d\\'Apache sera nécessaire.\")'>Activer toutes les extensions (" . implode(', ', $missingExtensions) . ")</button>";
            echo "</form>";
            echo "</div>";
        }
        
        // Informations sur les installations PHP multiples
        echo "<h2>🔧 Installations PHP détectées</h2>";
        $installations = detectPhpInstallations();
        
        if (empty($installations)) {
            echo "<div class='status error'>❌ Aucune installation PHP détectée</div>";
        } else {
            echo "<table>";
            echo "<tr><th>Type</th><th>Version</th><th>Fichier php.ini</th><th>Extensions critiques</th><th>Permissions</th></tr>";
            
            foreach ($installations as $type => $install) {
                $writable = is_writable($install['php_ini']) ? "✅ Écriture OK" : "❌ Lecture seule";
                
                // Vérifier les extensions critiques
                $criticalExtensions = ['pdo', 'pdo_mysql'];
                $extensionStatus = [];
                
                foreach ($criticalExtensions as $ext) {
                    if (in_array($ext, $install['extensions'])) {
                        $extensionStatus[] = "<span style='color: #28a745;'>$ext ✓</span>";
                    } else {
                        $extensionStatus[] = "<span style='color: #dc3545;'>$ext ✗</span>";
                    }
                }
                
                echo "<tr>";
                echo "<td><strong>{$install['type']}</strong><br><small>SAPI: {$install['sapi']}</small></td>";
                echo "<td><span class='version'>{$install['version']}</span></td>";
                echo "<td><small>{$install['php_ini']}</small></td>";
                echo "<td>" . implode('<br>', $extensionStatus) . "</td>";
                echo "<td>$writable</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Avertissement si les installations diffèrent
            if (count($installations) > 1) {
                echo "<div class='status warning'>";
                echo "⚠️ <strong>Installations PHP multiples détectées !</strong><br>";
                echo "• Le PHP Web (Apache) est utilisé pour l'interface web<br>";
                echo "• Le PHP CLI est utilisé pour les scripts en ligne de commande<br>";
                echo "• Les extensions peuvent différer entre les deux installations<br>";
                echo "• Activez les extensions dans les deux php.ini si nécessaire";
                echo "</div>";
            }
        }
        
        // Informations sur le fichier php.ini actuel (Web)
        echo "<h2>📄 Configuration PHP Web (Actuelle)</h2>";
        echo "<div class='info'>";
        echo "<strong>Fichier php.ini utilisé :</strong> " . php_ini_loaded_file() . "<br>";
        echo "<strong>Permissions d'écriture :</strong> " . (is_writable(php_ini_loaded_file()) ? "✅ Oui" : "❌ Non") . "<br>";
        if (!is_writable(php_ini_loaded_file())) {
            echo "<div style='color: #dc3545; margin-top: 10px;'>";
            echo "⚠️ <strong>Attention :</strong> Le fichier php.ini n'est pas modifiable par le serveur web.<br>";
            echo "Vous devrez modifier manuellement le fichier ou ajuster les permissions.";
            echo "</div>";
        }
        echo "</div>";
        
        ?>
        
        <hr style="margin: 30px 0;">
        
        <!-- Instructions de redémarrage Apache -->
        <div style="background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #0066cc;">🔄 Redémarrage d'Apache requis</h3>
            <p>Après avoir activé des extensions, vous devez redémarrer Apache pour que les changements prennent effet :</p>
            <ol>
                <li>Ouvrez le <strong>panneau de contrôle XAMPP</strong></li>
                <li>Cliquez sur <strong>"Stop"</strong> à côté d'Apache</li>
                <li>Attendez quelques secondes</li>
                <li>Cliquez sur <strong>"Start"</strong> pour redémarrer Apache</li>
                <li>Actualisez cette page pour vérifier les changements</li>
            </ol>
        </div>
        
        <p>
            <a href="setup.php" class="btn">🚀 Aller au Setup</a>
            <a href="../index.php" class="btn">🏠 Retour à l'accueil</a>
            <a href="javascript:location.reload()" class="btn btn-success">🔄 Actualiser la page</a>
        </p>
        
        <p><small>Diagnostic généré le <?= date('d/m/Y à H:i:s') ?></small></p>
    </div>
</body>
</html>