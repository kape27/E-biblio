# 🔧 Guide de Setup et Mises à jour - E-Lib

Ce document explique comment utiliser le script `admin/setup.php` pour appliquer les mises à jour de la base de données de l'application E-Lib.

## 📋 Vue d'ensemble

Le script `admin/setup.php` permet de :
- Appliquer automatiquement les mises à jour de la base de données
- Vérifier l'état actuel de la base de données
- Gérer les versions et l'historique des mises à jour
- Créer les nouvelles tables nécessaires (favoris, notifications, etc.)

## 🚀 Utilisation

### Interface Web (Recommandée)

1. **Connectez-vous en tant qu'administrateur** sur l'application E-Lib
2. **Accédez au script** : `http://votre-domaine/admin/setup.php`
3. **Choisissez une action** :
   - **📊 Vérifier l'état** : Affiche la version actuelle et les tables présentes
   - **🚀 Appliquer les mises à jour** : Applique uniquement les nouvelles mises à jour
   - **🔄 Forcer toutes les mises à jour** : Réapplique toutes les mises à jour (attention !)

### Ligne de commande

```bash
# Appliquer les mises à jour
cd /path/to/e-lib/admin
php setup.php

# Vérifier l'état de la base de données
php setup.php check

# Afficher l'aide
php setup.php --help
```

## 📊 Versions et Mises à jour

### Version 1.1.0 - Système de favoris
- ✅ Table `favorites` pour les livres favoris des utilisateurs
- ✅ Index optimisés pour les performances
- ✅ Contraintes de clés étrangères

### Version 1.2.0 - Récupération de mot de passe
- 🔄 Table `password_resets` pour les tokens de réinitialisation
- 🔄 Gestion des expirations automatiques

### Version 1.3.0 - Système de notation
- 🔄 Table `ratings` pour les notes des livres (1-5 étoiles)
- 🔄 Contraintes de validation des notes

### Version 1.4.0 - Commentaires et avis
- 🔄 Table `reviews` pour les commentaires des utilisateurs
- 🔄 Système de modération des commentaires

### Version 1.5.0 - Notifications
- 🔄 Table `notifications` pour les alertes utilisateurs
- 🔄 Types de notifications configurables

### Version 1.6.0 - Statistiques avancées
- 🔄 Colonnes supplémentaires dans `reading_progress`
- 🔄 Temps de lecture, pages lues, pourcentage de completion

## ⚠️ Précautions importantes

### Avant d'exécuter le script

1. **Sauvegarde obligatoire** : Toujours faire une sauvegarde complète de la base de données
   ```bash
   mysqldump -u username -p elib_database > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Environnement de test** : Tester d'abord sur un environnement de développement

3. **Permissions** : S'assurer que l'utilisateur de base de données a les droits CREATE, ALTER, DROP

### Pendant l'exécution

- ⏱️ Les mises à jour peuvent prendre du temps sur de grandes bases de données
- 🔒 Le script utilise des transactions pour garantir la cohérence
- 📝 Tous les changements sont loggés avec horodatage

### En cas de problème

1. **Erreur de permissions** :
   ```sql
   GRANT CREATE, ALTER, DROP, INSERT, UPDATE, DELETE, SELECT ON elib_database.* TO 'votre_utilisateur'@'localhost';
   ```

2. **Restauration depuis sauvegarde** :
   ```bash
   mysql -u username -p elib_database < backup_20240101_120000.sql
   ```

3. **Vérification manuelle** :
   ```sql
   SELECT * FROM database_versions ORDER BY applied_at DESC;
   SHOW TABLES;
   ```

## 🔍 Dépannage

### Problèmes courants

**"Table already exists"**
- Normal si vous réexécutez le script
- Le script utilise `CREATE TABLE IF NOT EXISTS`

**"Foreign key constraint fails"**
- Vérifier l'intégrité des données existantes
- S'assurer que les tables parentes existent

**"Access denied"**
- Vérifier les permissions de l'utilisateur MySQL
- Contrôler la configuration dans `config/database.php`

### Logs et debugging

Le script affiche des messages détaillés :
- ✅ **Vert** : Opération réussie
- ❌ **Rouge** : Erreur
- ℹ️ **Bleu** : Information
- ⚠️ **Jaune** : Avertissement

## 📁 Structure des fichiers

```
admin/
├── setup.php              # Script principal de mise à jour
├── diagnostic.php         # Diagnostic de l'environnement
├── check_extensions.php   # Vérification des extensions PHP
├── error_extensions.php   # Page d'erreur pour extensions manquantes
├── create_admin.php       # Création compte administrateur
└── SETUP.md               # Cette documentation
config/
├── database.php           # Configuration de la base de données
└── schema.sql             # Schéma initial complet
includes/
└── functions.php          # Fonctions utilitaires
```

## 🔄 Processus de développement

### Ajouter une nouvelle mise à jour

1. **Modifier `admin/setup.php`** :
   ```php
   '1.7.0' => [
       'description' => 'Nouvelle fonctionnalité',
       'sql' => [
           "CREATE TABLE nouvelle_table (...)",
           "ALTER TABLE existing_table ADD COLUMN ..."
       ]
   ]
   ```

2. **Tester localement** :
   ```bash
   php setup.php check
   php setup.php update
   ```

3. **Documenter** dans ce fichier

### Bonnes pratiques

- ✅ Utiliser `IF NOT EXISTS` pour les nouvelles tables
- ✅ Utiliser `ADD COLUMN IF NOT EXISTS` pour les nouvelles colonnes
- ✅ Toujours incrémenter le numéro de version
- ✅ Fournir une description claire
- ✅ Tester sur des données réelles

## 📞 Support

En cas de problème avec les mises à jour :

1. Consulter les logs du script
2. Vérifier la configuration de la base de données
3. S'assurer que les sauvegardes sont disponibles
4. Contacter l'équipe de développement avec :
   - Version actuelle de la base de données
   - Messages d'erreur complets
   - Configuration de l'environnement

---

**Note** : Ce script est conçu pour être sûr et réversible. En cas de doute, toujours faire une sauvegarde avant d'appliquer les mises à jour.