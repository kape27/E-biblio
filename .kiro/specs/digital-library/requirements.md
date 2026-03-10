# Requirements Document

## Introduction

E-Lib est une bibliothèque numérique complète permettant la gestion, le stockage et la lecture de fichiers PDF et EPUB. Le système supporte trois rôles d'utilisateurs distincts avec des permissions spécifiques : administrateur, bibliothécaire et utilisateur final.

## Glossary

- **System**: Le système E-Lib dans son ensemble
- **Admin**: Utilisateur avec privilèges d'administration système
- **Librarian**: Utilisateur avec privilèges de gestion des livres
- **User**: Utilisateur final avec accès en lecture seule
- **Book**: Fichier PDF ou EPUB avec métadonnées associées
- **Catalog**: Collection de livres disponibles dans le système
- **Reader**: Interface de lecture intégrée pour PDF et EPUB
- **Upload_System**: Système de téléchargement sécurisé de fichiers

## Requirements

### Requirement 1: Authentification et Gestion des Sessions

**User Story:** En tant qu'utilisateur du système, je veux me connecter de manière sécurisée, afin d'accéder aux fonctionnalités selon mon rôle.

#### Acceptance Criteria

1. WHEN a user provides valid credentials, THE System SHALL authenticate them using password_hash verification
2. WHEN authentication succeeds, THE System SHALL create a secure session with role information
3. WHEN a user accesses protected pages, THE System SHALL verify their session and role permissions
4. IF invalid credentials are provided, THEN THE System SHALL reject access and log the attempt
5. WHEN a user logs out, THE System SHALL destroy their session completely

### Requirement 2: Administration des Utilisateurs

**User Story:** En tant qu'administrateur, je veux gérer les utilisateurs du système, afin de contrôler les accès et maintenir la sécurité.

#### Acceptance Criteria

1. WHEN an admin accesses the user management interface, THE System SHALL display all users with their roles
2. WHEN an admin creates a new user, THE System SHALL validate the data and store it securely
3. WHEN an admin modifies user permissions, THE System SHALL update the role immediately
4. WHEN an admin views system statistics, THE System SHALL display user activity and connection logs
5. THE System SHALL log all administrative actions for audit purposes

### Requirement 3: Gestion des Livres par les Bibliothécaires

**User Story:** En tant que bibliothécaire, je veux gérer le catalogue de livres, afin de maintenir une collection organisée et accessible.

#### Acceptance Criteria

1. WHEN a librarian uploads a book file, THE Upload_System SHALL validate the file type (PDF or EPUB only)
2. WHEN a valid file is uploaded, THE Upload_System SHALL rename it securely and store it in the appropriate directory
3. WHEN adding book metadata, THE System SHALL require title, author, category, and description fields
4. WHEN a librarian uploads a cover image, THE System SHALL validate and resize it appropriately
5. WHEN a librarian deletes a book, THE System SHALL remove both the file and database records
6. THE System SHALL allow librarians to edit all book metadata after creation

### Requirement 4: Consultation du Catalogue par les Utilisateurs

**User Story:** En tant qu'utilisateur, je veux consulter le catalogue de livres, afin de découvrir et sélectionner des ouvrages à lire.

#### Acceptance Criteria

1. WHEN a user accesses the catalog, THE System SHALL display books in a responsive grid layout
2. WHEN displaying books, THE System SHALL show cover image, title, author, and category for each book
3. WHEN a user searches for books, THE System SHALL filter results by title, author, or category
4. WHEN search results are displayed, THE System SHALL maintain the same grid layout format
5. WHEN no search results are found, THE System SHALL display an appropriate message

### Requirement 5: Système de Lecture Intégré

**User Story:** En tant qu'utilisateur, je veux lire les livres directement dans le navigateur, afin d'avoir une expérience de lecture fluide sans téléchargement.

#### Acceptance Criteria

1. WHEN a user clicks on a book, THE System SHALL detect the file format automatically
2. WHEN opening a PDF file, THE Reader SHALL use PDF.js for display in full screen
3. WHEN opening an EPUB file, THE Reader SHALL use ePub.js for display in full screen
4. WHEN the reader loads, THE System SHALL provide navigation controls appropriate to the format
5. THE Reader SHALL maintain reading progress and allow bookmarking

### Requirement 6: Sécurité et Protection des Données

**User Story:** En tant qu'administrateur système, je veux que l'application soit sécurisée, afin de protéger les données et prévenir les attaques.

#### Acceptance Criteria

1. THE System SHALL use prepared statements for all database queries to prevent SQL injection
2. WHEN displaying user input, THE System SHALL escape all output to prevent XSS attacks
3. WHEN handling file uploads, THE Upload_System SHALL validate file extensions and MIME types
4. THE System SHALL implement CSRF protection for all form submissions
5. WHEN storing passwords, THE System SHALL use PHP's password_hash function with appropriate cost

### Requirement 7: Interface Utilisateur Responsive

**User Story:** En tant qu'utilisateur sur différents appareils, je veux une interface adaptative, afin d'utiliser l'application sur desktop, tablette et mobile.

#### Acceptance Criteria

1. THE System SHALL use Tailwind CSS via CDN for consistent styling
2. WHEN accessed on mobile devices, THE System SHALL adapt the layout appropriately
3. WHEN displaying the book grid, THE System SHALL adjust the number of columns based on screen size
4. THE Reader SHALL provide touch-friendly controls on mobile devices
5. WHEN navigating the interface, THE System SHALL maintain usability across all screen sizes

### Requirement 8: Gestion des Catégories

**User Story:** En tant que bibliothécaire, je veux organiser les livres par catégories, afin de faciliter la navigation et la recherche.

#### Acceptance Criteria

1. WHEN creating a book entry, THE System SHALL require selection of an existing category
2. WHEN a librarian creates a new category, THE System SHALL validate the name for uniqueness
3. WHEN displaying the catalog, THE System SHALL allow filtering by category
4. WHEN a category is deleted, THE System SHALL handle books assigned to that category appropriately
5. THE System SHALL display category statistics in the admin dashboard