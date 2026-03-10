# 🔧 Guide de diagnostic E-Lib

Ce guide vous aide à résoudre les problèmes courants de configuration de E-Lib.

## 🚀 Outils de diagnostic disponibles

### 1. Diagnostic complet (Interface Web)
**URL :** http://localhost/Biblio/admin/diagnostic.php

**Fonctionnalités :**
- ✅ Vérification complète de l'environnement PHP
- 🔧 Activation automatique des extensions manquantes
- 📋 Détection des installations PHP multiples (CLI vs Apache)
- 🗄️ Test de connexion à la base de données
- 📁 Vérification des permissions de dossiers
- 💡 Recommandations personnalisées

**Utilisation :**
1. Ouvrez l'URL dans votre navigateur
2. Consultez les statuts (✅ OK, ⚠️ Attention, ❌ Erreur)
3. Cliquez sur "Activer" pour les extensions manquantes
4. Redémarrez Apache après les modifications

### 2. Test de connexion MySQL (Ligne de commande)
**Commande :** `php admin/test_mysql.php`

**Fonctionnalités :**
- 🔍 Vérification des extensions PDO
- 🗄️ Test de connexion à MySQL
- 📊 Création automatique de la base de données si manquante
- 📋 Liste des tables existantes

**Utilisation :**
```bash
cd C:\xampp\htdocs\Biblio
php admin/test_mysql.php
```

### 3. Activation automatique PDO MySQL (Ligne de commande)
**Commande :** `php admin/fix_pdo_auto.php`

**Fonctionnalités :**
- 🔧 Détection des installations PHP multiples
- ⚙️ Activation automatique de PDO MySQL dans tous les php.ini
- 💾 Sauvegarde automatique des fichiers php.ini
- 📋 Instructions de redémarrage

**Utilisation :**
```bash
cd C:\xampp\htdocs\Biblio
php admin/fix_pdo_auto.php
```

## 🚨 Résolution des problèmes courants

### Problème 1: "L'extension PDO MySQL n'est pas activée"

**Solutions (par ordre de préférence) :**

1. **Interface Web (Facile) :**
   - Allez sur http://localhost/Biblio/admin/diagnostic.php
   - Cliquez sur "Activer" à côté de PDO MySQL
   - Redémarrez Apache dans XAMPP

2. **Ligne de commande (Automatique) :**
   ```bash
   cd C:\xampp\htdocs\Biblio
   php admin/fix_pdo_auto.php
   ```

3. **Manuel (Si les autres échouent) :**
   - Ouvrez le fichier php.ini (chemin affiché dans diagnostic.php)
   - Cherchez `;extension=pdo_mysql`
   - Supprimez le `;` pour obtenir `extension=pdo_mysql`
   - Sauvegardez et redémarrez Apache

### Problème 2: "No connection could be made"

**Cause :** Service MySQL non démarré

**Solution :**
1. Ouvrez le panneau de contrôle XAMPP
2. Cliquez sur "Start" à côté de MySQL
3. Vérifiez que le statut devient vert
4. Testez avec `php admin/test_mysql.php`

### Problème 3: "Database 'elib_database' doesn't exist"

**Solution automatique :**
```bash
cd C:\xampp\htdocs\Biblio
php admin/test_mysql.php
```
Le script créera automatiquement la base de données.

**Solution manuelle :**
1. Accédez à phpMyAdmin : http://localhost/phpmyadmin
2. Créez une base de données nommée `elib_database`
3. Utilisez l'encodage `utf8mb4_unicode_ci`

### Problème 4: Installations PHP multiples

**Symptôme :** Extensions activées dans l'interface web mais pas en ligne de commande (ou vice versa)

**Diagnostic :**
```bash
# Vérifier PHP CLI
php -v
php -m | findstr pdo

# Vérifier php.ini CLI
php --ini
```

**Solution :**
Le diagnostic.php détecte automatiquement les installations multiples et permet d'activer les extensions dans chaque php.ini.

### Problème 5: Erreur VCRUNTIME140.dll

**Symptôme :** Message d'erreur au démarrage d'Apache

**Impact :** Généralement aucun - l'application fonctionne normalement

**Solution (optionnelle) :**
1. Téléchargez Microsoft Visual C++ Redistributable 2015-2022 (x64)
2. Installez la version 64-bit
3. Redémarrez l'ordinateur

## 📋 Checklist de vérification

Avant de contacter le support, vérifiez :

- [ ] XAMPP est démarré (Apache + MySQL verts)
- [ ] Extensions PDO et PDO MySQL activées (diagnostic.php)
- [ ] Base de données `elib_database` existe (test_mysql.php)
- [ ] Permissions d'écriture sur `uploads/` (diagnostic.php)
- [ ] Aucune erreur dans les logs Apache/PHP

## 🔄 Workflow de résolution

1. **Diagnostic initial :** http://localhost/Biblio/admin/diagnostic.php
2. **Si PDO MySQL manque :** Cliquez sur "Activer" ou utilisez `fix_pdo_auto.php`
3. **Si connexion échoue :** Vérifiez MySQL dans XAMPP, puis `test_mysql.php`
4. **Si base manque :** Exécutez `test_mysql.php` pour création automatique
5. **Installation des tables :** `php admin/setup.php`
6. **Test final :** http://localhost/Biblio/

## 📞 Support

Si les problèmes persistent après avoir suivi ce guide :

1. Exécutez le diagnostic complet et notez tous les messages d'erreur
2. Vérifiez les logs Apache dans `C:\xampp\apache\logs\error.log`
3. Consultez la documentation complète dans `README.md`

---

**Dernière mise à jour :** Janvier 2026
**Version E-Lib :** 1.2.0+