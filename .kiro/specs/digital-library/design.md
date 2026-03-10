# Design Document: E-Lib Digital Library

## Overview

E-Lib est une application web de bibliothèque numérique développée en PHP 8 avec MySQL et JavaScript moderne. L'architecture suit le pattern MVC avec une séparation claire des responsabilités entre l'authentification, la gestion des données, et l'interface utilisateur. Le système supporte trois rôles utilisateur distincts avec des dashboards personnalisés et intègre des lecteurs JavaScript pour PDF et EPUB.

## Architecture

### Structure des Dossiers

```
e-lib/
├── config/
│   └── database.php          # Configuration PDO
├── includes/
│   ├── auth.php             # Gestion authentification
│   ├── functions.php        # Fonctions utilitaires
│   └── security.php         # Fonctions de sécurité
├── assets/
│   ├── css/
│   ├── js/
│   │   ├── pdf.min.js       # PDF.js
│   │   └── epub.min.js      # ePub.js
│   └── images/
├── uploads/
│   ├── books/               # Fichiers PDF/EPUB
│   └── covers/              # Images de couverture
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   └── logs.php
├── librarian/
│   ├── dashboard.php
│   ├── books.php
│   ├── upload.php
│   └── categories.php
├── user/
│   ├── dashboard.php
│   ├── catalog.php
│   └── search.php
├── reader.php               # Lecteur universel
├── login.php
├── logout.php
└── index.php
```

### Architecture des Données

Le système utilise MySQL avec les tables suivantes :

- **users** : Gestion des utilisateurs et rôles
- **books** : Métadonnées des livres
- **categories** : Classification des ouvrages
- **reading_progress** : Suivi de lecture par utilisateur
- **logs** : Journalisation des actions système

## Components and Interfaces

### 1. Système d'Authentification

**Composant** : `includes/auth.php`

**Responsabilités** :
- Vérification des credentials avec `password_verify()`
- Gestion des sessions sécurisées
- Contrôle d'accès basé sur les rôles
- Protection CSRF

**Interface** :
```php
class AuthManager {
    public function login(string $username, string $password): bool
    public function logout(): void
    public function isLoggedIn(): bool
    public function hasRole(string $role): bool
    public function getCurrentUser(): ?array
}
```

### 2. Gestionnaire de Base de Données

**Composant** : `config/database.php`

**Responsabilités** :
- Connexion PDO sécurisée
- Requêtes préparées
- Gestion des erreurs de base de données

**Interface** :
```php
class DatabaseManager {
    public function getConnection(): PDO
    public function executeQuery(string $sql, array $params = []): PDOStatement
    public function fetchAll(string $sql, array $params = []): array
    public function fetchOne(string $sql, array $params = []): ?array
}
```

### 3. Gestionnaire de Fichiers

**Composant** : `includes/file_manager.php`

**Responsabilités** :
- Upload sécurisé de fichiers
- Validation des types MIME
- Renommage et stockage des fichiers
- Gestion des images de couverture

**Interface** :
```php
class FileManager {
    public function uploadBook(array $file): string
    public function uploadCover(array $file): string
    public function validateFileType(array $file, array $allowedTypes): bool
    public function generateSecureFilename(string $originalName): string
}
```

### 4. Gestionnaire de Livres

**Composant** : `includes/book_manager.php`

**Responsabilités** :
- CRUD des livres
- Gestion des métadonnées
- Recherche et filtrage
- Association avec les catégories

**Interface** :
```php
class BookManager {
    public function createBook(array $bookData): int
    public function updateBook(int $id, array $bookData): bool
    public function deleteBook(int $id): bool
    public function searchBooks(string $query, ?int $categoryId = null): array
    public function getBooksByCategory(int $categoryId): array
}
```

### 5. Lecteur Universel

**Composant** : `reader.php`

**Responsabilités** :
- Détection automatique du format de fichier
- Chargement du lecteur approprié (PDF.js ou ePub.js)
- Interface plein écran
- Sauvegarde de la progression de lecture

## Data Models

### Table Users
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'librarian', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);
```

### Table Categories
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Table Books
```sql
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('pdf', 'epub') NOT NULL,
    cover_path VARCHAR(500),
    category_id INT,
    uploaded_by INT NOT NULL,
    file_size BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### Table Reading Progress
```sql
CREATE TABLE reading_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    progress_data JSON,
    last_position VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, book_id)
);
```

### Table Logs
```sql
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## Correctness Properties

*Une propriété est une caractéristique ou un comportement qui doit être vrai dans toutes les exécutions valides d'un système - essentiellement, une déclaration formelle sur ce que le système devrait faire. Les propriétés servent de pont entre les spécifications lisibles par l'homme et les garanties de correction vérifiables par machine.*

### Property 1: Authentication Verification
*Pour tout* utilisateur avec des identifiants valides, l'authentification doit réussir et créer une session sécurisée avec les informations de rôle correctes
**Validates: Requirements 1.1, 1.2**

### Property 2: Access Control Enforcement
*Pour tout* utilisateur et toute page protégée, l'accès doit être accordé si et seulement si l'utilisateur a les permissions de rôle appropriées
**Validates: Requirements 1.3**

### Property 3: Authentication Failure Handling
*Pour tout* ensemble d'identifiants invalides, l'authentification doit échouer, l'accès doit être refusé, et la tentative doit être enregistrée
**Validates: Requirements 1.4**

### Property 4: Session Cleanup
*Pour tout* utilisateur connecté, la déconnexion doit détruire complètement toutes les données de session
**Validates: Requirements 1.5**

### Property 5: User Management Completeness
*Pour tout* administrateur accédant à l'interface de gestion, tous les utilisateurs du système doivent être affichés avec leurs rôles corrects
**Validates: Requirements 2.1**

### Property 6: User Creation and Role Management
*Pour toute* donnée utilisateur valide, la création doit réussir et les modifications de rôle doivent être immédiatement reflétées dans le système
**Validates: Requirements 2.2, 2.3**

### Property 7: Administrative Logging
*Pour toute* action administrative, un enregistrement de log approprié doit être créé et les statistiques système doivent refléter l'état actuel
**Validates: Requirements 2.4, 2.5**

### Property 8: File Upload Security
*Pour tout* fichier téléchargé, seuls les types PDF et EPUB doivent être acceptés, et les fichiers valides doivent être renommés de manière sécurisée et stockés correctement
**Validates: Requirements 3.1, 3.2, 6.3**

### Property 9: Book Metadata Validation
*Pour toute* tentative de création de livre, tous les champs requis (titre, auteur, catégorie, description) doivent être présents pour que la création réussisse
**Validates: Requirements 3.3**

### Property 10: Image Processing
*Pour toute* image de couverture valide téléchargée, elle doit être validée et redimensionnée appropriément
**Validates: Requirements 3.4**

### Property 11: Complete Book Deletion
*Pour tout* livre supprimé, tous les enregistrements de base de données et fichiers associés doivent être supprimés
**Validates: Requirements 3.5**

### Property 12: Metadata Editing
*Pour tout* livre existant, toutes les métadonnées doivent pouvoir être modifiées avec succès par un bibliothécaire
**Validates: Requirements 3.6**

### Property 13: Book Display Completeness
*Pour tout* livre affiché, l'image de couverture, le titre, l'auteur et la catégorie doivent tous être présents dans l'affichage
**Validates: Requirements 4.2**

### Property 14: Search Functionality
*Pour toute* requête de recherche, les résultats doivent correspondre aux critères de recherche dans au moins un des champs : titre, auteur, ou catégorie
**Validates: Requirements 4.3**

### Property 15: File Format Detection and Reader Selection
*Pour tout* fichier livre, le format doit être détecté automatiquement et le lecteur approprié (PDF.js pour PDF, ePub.js pour EPUB) doit être utilisé
**Validates: Requirements 5.1, 5.2, 5.3**

### Property 16: Reader Navigation Controls
*Pour tout* fichier ouvert dans le lecteur, les contrôles de navigation appropriés au format doivent être fournis
**Validates: Requirements 5.4**

### Property 17: Reading Progress Persistence
*Pour toute* session de lecture, le progrès doit être maintenu et les signets doivent être sauvegardés et restaurés
**Validates: Requirements 5.5**

### Property 18: XSS Prevention
*Pour toute* entrée utilisateur affichée, le contenu doit être échappé pour prévenir les attaques XSS
**Validates: Requirements 6.2**

### Property 19: Category Requirement
*Pour toute* création de livre, une catégorie existante valide doit être sélectionnée
**Validates: Requirements 8.1**

### Property 20: Category Uniqueness
*Pour toute* nouvelle catégorie créée, le nom doit être unique dans le système
**Validates: Requirements 8.2**

### Property 21: Category Filtering
*Pour tout* filtre de catégorie appliqué, seuls les livres appartenant à cette catégorie doivent être retournés
**Validates: Requirements 8.3**

### Property 22: Category Deletion Handling
*Pour toute* catégorie supprimée, les livres associés doivent être gérés appropriément (soit réassignés, soit marqués sans catégorie)
**Validates: Requirements 8.4**

### Property 23: Category Statistics Accuracy
*Pour toute* statistique de catégorie affichée, elle doit refléter avec précision le nombre réel de livres dans chaque catégorie
**Validates: Requirements 8.5**

## Error Handling

### Authentication Errors
- **Invalid Credentials** : Retourner un message d'erreur générique pour éviter l'énumération d'utilisateurs
- **Session Expiry** : Rediriger vers la page de connexion avec un message approprié
- **Insufficient Permissions** : Afficher une page d'erreur 403 avec un message explicatif

### File Upload Errors
- **Invalid File Type** : Rejeter le fichier avec un message d'erreur spécifique
- **File Size Exceeded** : Limiter la taille des fichiers et informer l'utilisateur
- **Storage Errors** : Gérer les erreurs de système de fichiers avec des messages appropriés

### Database Errors
- **Connection Failures** : Afficher une page d'erreur de maintenance
- **Constraint Violations** : Convertir en messages d'erreur utilisateur compréhensibles
- **Transaction Failures** : Rollback automatique avec notification d'erreur

### Reader Errors
- **Corrupted Files** : Détecter et signaler les fichiers corrompus
- **Unsupported Formats** : Vérifier les formats avant l'affichage
- **Loading Failures** : Fournir des alternatives ou des messages d'erreur clairs

## Testing Strategy

### Approche de Test Dual

Le système utilisera une approche de test combinée :

**Tests Unitaires** :
- Vérification d'exemples spécifiques et de cas limites
- Tests d'intégration entre composants
- Validation des conditions d'erreur
- Focus sur les scénarios concrets et les cas d'usage spécifiques

**Tests Basés sur les Propriétés** :
- Vérification des propriétés universelles sur tous les inputs
- Couverture complète des inputs par randomisation
- Validation des invariants système
- Tests de robustesse avec des données générées

### Configuration des Tests Basés sur les Propriétés

**Framework** : PHPUnit avec la bibliothèque Eris pour les tests basés sur les propriétés
**Configuration** : Minimum 100 itérations par test de propriété
**Annotation** : Chaque test doit référencer sa propriété de conception avec le format :
`**Feature: digital-library, Property {number}: {property_text}**`

### Stratégie de Test par Composant

**Authentification** :
- Tests unitaires pour les cas de connexion/déconnexion spécifiques
- Tests de propriétés pour la vérification des credentials sur tous les inputs
- Tests de sécurité pour les tentatives d'intrusion

**Gestion des Fichiers** :
- Tests unitaires pour les formats de fichiers spécifiques
- Tests de propriétés pour la validation sur tous les types de fichiers
- Tests de sécurité pour les tentatives d'upload malveillantes

**Base de Données** :
- Tests unitaires pour les requêtes CRUD spécifiques
- Tests de propriétés pour l'intégrité des données sur toutes les opérations
- Tests de performance pour les requêtes complexes

**Interface Utilisateur** :
- Tests unitaires pour les interactions spécifiques
- Tests de propriétés pour la cohérence de l'affichage
- Tests de compatibilité navigateur