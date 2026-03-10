<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Lib - Extensions PHP manquantes</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 600px; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); text-align: center; }
        h1 { color: #dc3545; margin-bottom: 20px; font-size: 2.5em; }
        .icon { font-size: 4em; margin-bottom: 20px; }
        .message { font-size: 1.2em; color: #666; margin-bottom: 30px; line-height: 1.6; }
        .btn { display: inline-block; padding: 15px 30px; margin: 10px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 1.1em; transition: all 0.3s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; transform: translateY(-2px); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #1e7e34; transform: translateY(-2px); }
        .steps { text-align: left; background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .steps h3 { color: #495057; margin-top: 0; }
        .steps ol { color: #6c757d; }
        .code { background: #e9ecef; padding: 5px 10px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>Extensions PHP manquantes</h1>
        
        <div class="message">
            L'application E-Lib nécessite certaines extensions PHP pour fonctionner correctement. 
            Ces extensions ne sont pas activées sur votre serveur.
        </div>
        
        <div class="steps">
            <h3>📝 Comment résoudre ce problème :</h3>
            <ol>
                <li>Ouvrez le fichier <span class="code">C:\xampp\php\php.ini</span></li>
                <li>Recherchez ces lignes et supprimez le <span class="code">;</span> au début :
                    <br><span class="code">;extension=pdo</span>
                    <br><span class="code">;extension=pdo_mysql</span>
                </li>
                <li>Sauvegardez le fichier</li>
                <li>Redémarrez Apache dans XAMPP</li>
                <li>Actualisez cette page</li>
            </ol>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="check_extensions.php" class="btn btn-primary">🔍 Vérifier les extensions</a>
            <a href="diagnostic.php" class="btn btn-success">📊 Diagnostic complet</a>
        </div>
        
        <div style="margin-top: 20px; font-size: 0.9em; color: #6c757d;">
            Si vous continuez à avoir des problèmes, consultez la documentation XAMPP ou réinstallez XAMPP.
        </div>
    </div>
</body>
</html>