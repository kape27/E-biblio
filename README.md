# E-Lib - Bibliothèque Numérique

E-Lib est une application web de bibliothèque numérique permettant de gérer et lire des livres au format PDF et EPUB. L'application propose une interface moderne avec un thème sombre (Dark Mode) et un design glassmorphism.

## 📋 Prérequis

### Option 1: Docker (Recommandé)
- **Docker** 20.10+
- **Docker Compose** 2.0+
- 2 GB RAM minimum
- 5 GB espace disque

### Option 2: XAMPP (Installation traditionnelle)
- **XAMPP** (ou équivalent avec Apache, PHP 7.4+, MySQL/MariaDB)
- **Extensions PHP requises** :
  - `pdo_mysql` - Connexion à la base de données
  - `zip` - Lecture des fichiers EPUB (optionnel mais recommandé)
  - `gd` - Redimensionnement des images de couverture (optionnel)

## 🚀 Installation

### ☁️ Déploiement sur Render (Production)

**Le moyen le plus simple de mettre E-Lib en ligne:**

```bash
# 1. Pousser le code sur Git
git add .
git commit -m "Deploy to Render"
git push origin main

# 2. Sur Render Dashboard
# - New + → Blueprint
# - Connecter votre repo
# - Render détecte render.yaml
# - Cliquer Apply

# 3. Accéder à votre app
# URL: https://elib-web.onrender.com
```

📖 **Documentation complète:** Voir [RENDER.md](RENDER.md)

---

### 🐳 Installation avec Docker (Développement local)

**Démarrage rapide:**
```bash
# 1. Copier le fichier d'environnement
cp .env.example .env

# 2. Démarrer l'application
make setup
# ou
docker-compose up -d

# 3. Accéder à l'application
# Web: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

**Identifiants par défaut:**
- Username: `admin`
- Password: `admin123`

📖 **Documentation complète:** Voir [DOCKER.md](DOCKER.md)

---

### 💻 Installation avec XAMPP

#### 1. Cloner/Copier les fichiers

Placez les fichiers du projet dans le dossier `htdocs` de XAMPP :
```
C:\xampp\htdocs\Biblio\
```

### 2. Diagnostic automatique et configuration

**🔧 Diagnostic complet (RECOMMANDÉ) :**
Accédez à : http://localhost/Biblio/admin/diagnostic.php

Cette page vous permet de :
- Vérifier toutes les extensions PHP requises
- Activer automatiquement les extensions manquantes
- Diagnostiquer les problèmes de connexion à la base de données
- Détecter les installations PHP multiples (CLI vs Apache)

**🚀 Activation automatique de PDO MySQL :**
Si PDO MySQL n'est pas activé, exécutez en ligne de commande :
```bash
cd C:\xampp\htdocs\Biblio
php admin/fix_pdo_auto.php
```

**🧪 Test de connexion MySQL :**
Pour tester uniquement la connexion à la base de données :
```bash
php admin/test_mysql.php
```

### 3. Configuration de la base de données

La base de données `elib_database` sera créée automatiquement si elle n'existe pas.

**Configuration manuelle (si nécessaire) :**
1. Démarrez Apache et MySQL depuis le panneau de contrôle XAMPP
2. Accédez à phpMyAdmin : http://localhost/phpmyadmin
3. Créez une nouvelle base de données nommée `elib_database`

### 4. Installation des tables

Exécutez le script de setup :
```bash
cd C:\xampp\htdocs\Biblio
php admin/setup.php
```

Ou via l'interface web : http://localhost/Biblio/admin/setup.php

### 5. Vérification des extensions PHP

**Activation manuelle dans XAMPP (si l'activation automatique échoue) :**
1. Ouvrez `C:\xampp\php\php.ini`
2. Recherchez et décommentez (retirez le `;`) :
   ```
   extension=pdo_mysql
   extension=zip
   extension=gd
   ```
3. Redémarrez Apache dans XAMPP
3. Redémarrez Apache dans XAMPP

### 4. Appliquer les mises à jour de la base de données

Exécutez le script de setup pour créer les tables et appliquer les mises à jour :

**Option 1 - Interface Web (Recommandée)** :
1. Connectez-vous en tant qu'administrateur
2. Accédez à : http://localhost/Biblio/admin/setup.php
3. Cliquez sur "🚀 Appliquer les mises à jour"

**Option 2 - Ligne de commande** :
```bash
cd C:\xampp\htdocs\Biblio\admin
php setup.php
```

### 5. Activer les extensions PHP (optionnel)

Pour activer les extensions `zip` et `gd` dans XAMPP :
1. Ouvrez `C:\xampp\php\php.ini`
2. Décommentez les lignes suivantes (retirez le `;`) :
   ```
   extension=zip
   extension=gd
   ```
3. Redémarrez Apache

### 6. Accéder à l'application

Ouvrez votre navigateur : http://localhost/Biblio/

## 👥 Rôles Utilisateurs

L'application dispose de 3 rôles avec des permissions différentes :

### 🔵 Utilisateur (user)
- Parcourir le catalogue de livres
- Rechercher des livres par titre, auteur ou catégorie
- Lire les livres (PDF et EPUB)
- Modifier son profil (email, mot de passe)
- Suivre sa progression de lecture

### 🟢 Bibliothécaire (librarian)
- Toutes les fonctionnalités utilisateur
- Ajouter de nouveaux livres
- Modifier les informations des livres
- Gérer les catégories
- Supprimer des livres

### 🔴 Administrateur (admin)
- Toutes les fonctionnalités bibliothécaire
- Gérer les utilisateurs (créer, modifier, supprimer)
- Changer les rôles des utilisateurs
- Activer/désactiver des comptes
- Consulter les journaux système

## 📖 Guide d'utilisation

### Connexion

1. Accédez à la page de connexion
2. Entrez votre nom d'utilisateur et mot de passe
3. Vous serez redirigé vers votre tableau de bord selon votre rôle

### Pour les Utilisateurs

#### Parcourir le catalogue
- Cliquez sur **Catalogue** dans le menu latéral
- Filtrez par catégorie en cliquant sur les badges
- Cliquez sur un livre pour commencer la lecture

#### Rechercher un livre
- Utilisez la barre de recherche en haut de page
- Ou accédez à **Recherche** pour une recherche avancée
- Filtrez par catégorie si nécessaire

#### Lire un livre
- Cliquez sur un livre pour ouvrir le lecteur
- **PDF** : Navigation par pages, zoom, mode plein écran
- **EPUB** : Navigation par chapitres (nécessite l'extension zip)

#### Modifier son profil
- Cliquez sur **Mon Profil** dans le menu
- Modifiez votre adresse email
- Changez votre mot de passe (mot de passe actuel requis)

### Pour les Bibliothécaires

#### Ajouter un livre
1. Accédez à **Téléverser** dans le menu
2. Remplissez les informations :
   - Titre (obligatoire)
   - Auteur (obligatoire)
   - Description
   - Catégorie
3. Sélectionnez le fichier PDF ou EPUB
4. Ajoutez une couverture (fichier ou URL)
5. Cliquez sur **Téléverser**

#### Modifier un livre
1. Accédez à **Livres** dans le menu
2. Cliquez sur l'icône de modification (crayon)
3. Modifiez les informations souhaitées
4. Enregistrez les modifications

#### Gérer les catégories
1. Accédez à **Catégories** dans le menu
2. Ajoutez une nouvelle catégorie avec le bouton **+**
3. Modifiez ou supprimez les catégories existantes

### Pour les Administrateurs

#### Gérer les utilisateurs
1. Accédez à **Utilisateurs** dans le menu admin
2. Créez un nouvel utilisateur avec le bouton **Nouveau**
3. Modifiez le rôle d'un utilisateur via le menu déroulant
4. Activez/désactivez un compte avec l'icône correspondante
5. Supprimez un utilisateur si nécessaire

#### Consulter les journaux
1. Accédez à **Journaux** dans le menu admin
2. Filtrez par a4144

### Lecteur PDF
- Navigation par pages (précédent/suivant)
- Slider de progression
- Zoom (ajuster à la largeur, à la page, personnalisé)
- Mode plein écran
- Aller à une page spécifique
- Support tactile (pinch-to-zoom, swipe)
- Interface auto-masquante sur mobile

### Lecteur EPUB
- Navigation par chapitres
- Table des matières
- Sauvegarde automatique de la progression
- ⚠️ Nécessite l'extension PHP `zip`

## 🔧 Structure des fichiers

```
Biblio/
├── admin/              # Pages administration
│   ├── dashboard.php
│   ├── users.php
│   ├── books.php
│   ├── categories.php
│   └── logs.php
├── api/                # API endpoints
│   ├── epub_content.php
│   ├── save_progress.php
│   └── serve_book.php
├── assets/
│   ├── css/style.css
│   ├── js/
│   └── images/
├── config/
│   ├── database.php
│   └── schema.sql
├── includes/           # Classes PHP
│   ├── auth.php
│   ├── book_manager.php
│   ├── category_manager.php
│   ├── file_manager.php
│   ├── functions.php
│   ├── reading_progress_manager.php
│   ├── security.php
│   └── user_manager.php
├── librarian/          # Pages bibliothécaire
│   ├── dashboard.php
│   ├── books.php
│   ├── upload.php
│   ├── edit_book.php
│   └── categories.php
├── uploads/
│   ├── books/          # Fichiers PDF/EPUB
│   └── covers/         # Images de couverture
├── user/               # Pages utilisateur
│   ├── dashboard.php
│   ├── catalog.php
│   ├── search.php
│   ├── favorites.php   # Système de favoris
│   └── profile.php
├── index.php           # Page d'accueil
├── login.php           # Page de connexion
├── register.php        # Page d'inscription
├── logout.php          # Déconnexion
├── reader.php          # Lecteur de livres
├── SETUP.md            # Guide des mises à jour
└── README.md           # Ce fichier
```

## 🔄 Mises à jour et maintenance

### Script de mise à jour automatique

L'application inclut un système de mise à jour automatique via `admin/setup.php` :

**Interface Web** :
1. Connectez-vous en tant qu'administrateur
2. Accédez à : `http://localhost/Biblio/admin/setup.php`
3. Utilisez les boutons pour vérifier l'état ou appliquer les mises à jour

**Ligne de commande** :
```bash
# Appliquer les mises à jour
cd C:\xampp\htdocs\Biblio\admin
php setup.php

# Vérifier l'état de la base de données
php setup.php check
```

### Nouvelles fonctionnalités disponibles

- ✅ **Système de favoris** (v1.1.0) - Marquer des livres comme favoris
- 🔄 **Récupération de mot de passe** (v1.2.0) - Reset par email
- 🔄 **Système de notation** (v1.3.0) - Noter les livres (1-5 étoiles)
- 🔄 **Commentaires** (v1.4.0) - Avis et commentaires sur les livres
- 🔄 **Notifications** (v1.5.0) - Alertes pour les utilisateurs

Consultez `SETUP.md` pour plus de détails sur les mises à jour.

## 🎨 Personnalisation

### Couleurs du thème
Les couleurs sont définies dans chaque fichier PHP via Tailwind CSS :
- **Fond principal** : `#0f172a` (dark-900)
- **Cartes** : `#1e293b` (dark-800)
- **Accent utilisateur** : `#6366f1` (indigo)
- **Accent bibliothécaire** : `#22c55e` (vert)
- **Accent admin** : `#ef4444` (rouge)

### Police
L'application utilise la police **Inter** de Google Fonts.

## ⚠️ Dépannage

### 🔧 Outils de diagnostic automatique

**Diagnostic complet (RECOMMANDÉ) :**
Accédez à : http://localhost/Biblio/admin/diagnostic.php
- Vérification complète de l'environnement PHP
- Activation automatique des extensions manquantes
- Détection des installations PHP multiples (CLI vs Apache)
- Test de connexion à la base de données

**Test de connexion MySQL uniquement :**
```bash
cd C:\xampp\htdocs\Biblio
php admin/test_mysql.php
```

**Activation automatique de PDO MySQL :**
```bash
cd C:\xampp\htdocs\Biblio
php admin/fix_pdo_auto.php
```

### 🚨 Problèmes courants

#### Extension PDO MySQL non activée
**Erreur :** `L'extension PDO MySQL n'est pas activée`

**Solutions automatiques :**
1. Via l'interface web : http://localhost/Biblio/admin/diagnostic.php
2. Via la ligne de commande : `php admin/fix_pdo_auto.php`

**Solution manuelle :**
1. Ouvrez le fichier php.ini utilisé (affiché dans diagnostic.php)
2. Cherchez `;extension=pdo_mysql` ou `;extension=php_pdo_mysql.dll`
3. Supprimez le `;` au début de la ligne
4. Redémarrez Apache dans XAMPP

#### Service MySQL non démarré
**Erreur :** `No connection could be made because the target machine actively refused it`

**Solution :**
1. Ouvrez le panneau de contrôle XAMPP
2. Cliquez sur "Start" à côté de MySQL
3. Vérifiez que le statut devient vert

#### Base de données manquante
**Erreur :** `Database 'elib_database' doesn't exist`

**Solution automatique :**
Le script `test_mysql.php` créera automatiquement la base de données si elle n'existe pas.

**Solution manuelle :**
1. Accédez à phpMyAdmin : http://localhost/phpmyadmin
2. Créez une nouvelle base de données nommée `elib_database`
3. Utilisez l'encodage `utf8mb4_unicode_ci`

#### Installations PHP multiples (CLI vs Apache)
**Problème :** Extensions activées dans un PHP mais pas l'autre

**Diagnostic :**
Le fichier `diagnostic.php` détecte automatiquement les installations multiples et permet d'activer les extensions dans chaque php.ini séparément.

**Fichiers php.ini typiques :**
- **PHP Apache (XAMPP) :** `C:\xampp\php\php.ini`
- **PHP CLI :** `C:\Users\[USER]\AppData\Local\Programs\PHP\current\php.ini`

#### Erreur VCRUNTIME140.dll (Windows)
Cette erreur est courante avec XAMPP sur Windows et généralement sans impact :

**Solution recommandée :**
1. Téléchargez Microsoft Visual C++ Redistributable 2015-2022 (x64)
2. Installez la version 64-bit
3. Redémarrez votre ordinateur
4. Redémarrez Apache dans XAMPP

**Note :** Cette erreur n'empêche généralement pas le fonctionnement de l'application.

#### Permissions de fichiers
**Problème :** Impossible de modifier php.ini ou d'écrire dans uploads/

**Solutions :**
1. Exécutez XAMPP en tant qu'administrateur
2. Vérifiez les permissions des dossiers `uploads/books/` et `uploads/covers/`
3. Assurez-vous que le serveur web peut écrire dans ces dossiers

#### Le lecteur EPUB ne fonctionne pas
**Cause :** Extension `zip` manquante

**Solution :**
1. Activez via diagnostic.php ou manuellement dans php.ini : `extension=zip`
2. Redémarrez Apache

#### Les images ne sont pas redimensionnées
**Cause :** Extension `gd` manquante

**Solution :**
1. Activez via diagnostic.php ou manuellement dans php.ini : `extension=gd`
2. Redémarrez Apache
3. L'application fonctionnera sans GD mais sans redimensionnement d'images

### 🔄 Après chaque modification de php.ini

**IMPORTANT :** Vous devez toujours redémarrer Apache après avoir modifié php.ini :
1. Ouvrez le panneau de contrôle XAMPP
2. Cliquez sur "Stop" à côté d'Apache
3. Attendez quelques secondes
4. Cliquez sur "Start" pour redémarrer Apache
5. Vérifiez les changements via diagnostic.php

## 📄 Licence

Ce projet est fourni à des fins éducatives.

## 📚 Documentation

- [QUICKSTART.md](QUICKSTART.md) - Déploiement rapide sur Render (5 minutes)
- [RENDER.md](RENDER.md) - Guide complet de déploiement Render
- [DOCKER.md](DOCKER.md) - Guide Docker pour développement local
- [DEPLOYMENT.md](DEPLOYMENT.md) - Comparaison des plateformes de déploiement
- [SETUP.md](SETUP.md) - Guide des mises à jour de la base de données

## 🤝 Support

Pour toute question ou problème, consultez les journaux système (admin) ou vérifiez les logs PHP dans XAMPP.
