# 🐳 E-Lib Docker Deployment Guide

Ce guide explique comment déployer E-Lib avec Docker et Docker Compose.

## 📋 Prérequis

- Docker Engine 20.10+
- Docker Compose 2.0+
- 2 GB RAM minimum
- 5 GB espace disque disponible

## 🚀 Démarrage rapide

### 1. Configuration initiale

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Éditer les variables si nécessaire
nano .env
```

### 2. Lancer l'application

```bash
# Construire et démarrer tous les services
docker-compose up -d

# Voir les logs
docker-compose logs -f web
```

### 3. Accéder à l'application

- **Application E-Lib**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Identifiants par défaut**:
  - Username: `admin`
  - Password: `admin123`

## 📦 Services inclus

### Web (Apache + PHP 8.2)
- Port: 8080
- Extensions PHP: PDO, MySQL, ZIP, GD
- Apache avec mod_rewrite activé

### Database (MySQL 8.0)
- Port: 3306
- Base de données: `elib_database`
- User: `elib_user`
- Schémas initialisés automatiquement

### phpMyAdmin
- Port: 8081
- Interface de gestion de base de données
- Connexion automatique à MySQL

## 🔧 Commandes utiles

### Gestion des conteneurs

```bash
# Démarrer les services
docker-compose up -d

# Arrêter les services
docker-compose down

# Redémarrer un service
docker-compose restart web

# Voir les logs
docker-compose logs -f web
docker-compose logs -f db

# Voir l'état des services
docker-compose ps
```

### Accès aux conteneurs

```bash
# Shell dans le conteneur web
docker-compose exec web bash

# Shell dans le conteneur database
docker-compose exec db mysql -u root -p

# Exécuter une commande PHP
docker-compose exec web php admin/setup.php
```

### Gestion de la base de données

```bash
# Backup de la base de données
docker-compose exec db mysqldump -u elib_user -pelib_password elib_database > backup.sql

# Restaurer une sauvegarde
docker-compose exec -T db mysql -u elib_user -pelib_password elib_database < backup.sql

# Accéder au client MySQL
docker-compose exec db mysql -u elib_user -pelib_password elib_database
```

### Maintenance

```bash
# Reconstruire les images
docker-compose build --no-cache

# Nettoyer les volumes (⚠️ supprime les données)
docker-compose down -v

# Voir l'utilisation des ressources
docker stats
```

## 📁 Volumes persistants

Les données suivantes sont persistées dans des volumes Docker:

- **db-data**: Données MySQL
- **./uploads**: Fichiers uploadés (livres, couvertures)
- **./logs**: Logs de l'application
- **./backups**: Sauvegardes

## 🔒 Sécurité

### Recommandations pour la production

1. **Changer les mots de passe par défaut**:
```bash
# Éditer .env
DB_PASS=votre_mot_de_passe_fort
MYSQL_ROOT_PASSWORD=votre_root_password_fort
```

2. **Désactiver phpMyAdmin en production**:
```yaml
# Dans docker-compose.yml, commenter la section phpmyadmin
```

3. **Utiliser HTTPS avec un reverse proxy**:
```bash
# Exemple avec Nginx
docker run -d -p 443:443 \
  -v /path/to/certs:/etc/nginx/certs \
  nginx-proxy
```

4. **Limiter l'accès réseau**:
```yaml
# Exposer uniquement le port web
ports:
  - "127.0.0.1:8080:80"
```

## 🐛 Dépannage

### Le conteneur web ne démarre pas

```bash
# Vérifier les logs
docker-compose logs web

# Vérifier les permissions
docker-compose exec web ls -la /var/www/html/uploads
```

### Erreur de connexion à la base de données

```bash
# Vérifier que MySQL est prêt
docker-compose exec db mysqladmin ping -h localhost -u root -proot_password

# Recréer le conteneur database
docker-compose down
docker-compose up -d db
```

### Les fichiers uploadés ne sont pas sauvegardés

```bash
# Vérifier les permissions
docker-compose exec web chown -R www-data:www-data /var/www/html/uploads

# Vérifier le volume
docker volume inspect elib_db-data
```

### Port déjà utilisé

```bash
# Changer le port dans docker-compose.yml
ports:
  - "8090:80"  # Au lieu de 8080
```

## 🔄 Migration depuis XAMPP

### 1. Exporter les données XAMPP

```bash
# Depuis XAMPP
mysqldump -u root elib_database > xampp_backup.sql
```

### 2. Copier les fichiers uploadés

```bash
# Copier les livres et couvertures
cp -r C:/xampp/htdocs/Biblio/uploads/* ./uploads/
```

### 3. Importer dans Docker

```bash
# Démarrer Docker
docker-compose up -d

# Importer la base de données
docker-compose exec -T db mysql -u elib_user -pelib_password elib_database < xampp_backup.sql
```

## 📊 Monitoring

### Logs en temps réel

```bash
# Tous les services
docker-compose logs -f

# Service spécifique
docker-compose logs -f web
```

### Métriques de performance

```bash
# Utilisation CPU/RAM
docker stats

# Espace disque des volumes
docker system df -v
```

## 🚀 Déploiement en production

### Avec Docker Swarm

```bash
# Initialiser Swarm
docker swarm init

# Déployer la stack
docker stack deploy -c docker-compose.yml elib
```

### Avec Kubernetes

```bash
# Convertir docker-compose en manifests K8s
kompose convert

# Déployer
kubectl apply -f .
```

## 📝 Variables d'environnement

| Variable | Description | Défaut |
|----------|-------------|--------|
| DB_HOST | Hôte MySQL | db |
| DB_NAME | Nom de la base | elib_database |
| DB_USER | Utilisateur MySQL | elib_user |
| DB_PASS | Mot de passe MySQL | elib_password |
| MYSQL_ROOT_PASSWORD | Root password | root_password |
| WEB_PORT | Port web | 8080 |
| DB_PORT | Port MySQL | 3306 |
| PHPMYADMIN_PORT | Port phpMyAdmin | 8081 |

## 🆘 Support

Pour toute question ou problème:

1. Vérifier les logs: `docker-compose logs`
2. Consulter la documentation Docker
3. Vérifier les issues GitHub du projet

---

**Note**: Cette configuration Docker est optimisée pour le développement. Pour la production, ajoutez des mesures de sécurité supplémentaires et utilisez des secrets Docker.
