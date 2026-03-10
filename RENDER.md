# 🚀 Déploiement E-Lib sur Render

Ce guide explique comment déployer E-Lib sur Render.com avec Docker.

## 📋 Prérequis

- Compte Render.com (gratuit)
- Repository Git (GitHub, GitLab, ou Bitbucket)
- Code source E-Lib poussé sur le repository

## 🎯 Avantages de Render

✅ Déploiement automatique depuis Git
✅ HTTPS gratuit avec certificat SSL
✅ Base de données MySQL managée
✅ Stockage persistant pour les uploads
✅ Logs en temps réel
✅ Plan gratuit disponible

## 🚀 Déploiement automatique (Recommandé)

### Méthode 1: Blueprint (render.yaml)

1. **Pousser le code sur Git**
```bash
git add .
git commit -m "Add Render configuration"
git push origin main
```

2. **Créer un nouveau Blueprint sur Render**
   - Aller sur https://dashboard.render.com
   - Cliquer sur "New +" → "Blueprint"
   - Connecter votre repository Git
   - Render détectera automatiquement `render.yaml`
   - Cliquer sur "Apply"

3. **Attendre le déploiement**
   - Render va créer automatiquement:
     - Service Web (elib-web)
     - Base de données MySQL (elib-db)
     - Disque persistant pour uploads (1 GB)

4. **Accéder à l'application**
   - URL fournie par Render: `https://elib-web.onrender.com`
   - Identifiants par défaut: `admin` / `admin123`

### Méthode 2: Déploiement manuel

#### Étape 1: Créer la base de données

1. Sur le dashboard Render, cliquer "New +" → "MySQL"
2. Configurer:
   - **Name**: `elib-db`
   - **Database**: `elib_database`
   - **User**: `elib_user`
   - **Region**: Frankfurt (ou le plus proche)
   - **Plan**: Free
3. Cliquer "Create Database"
4. Noter les informations de connexion

#### Étape 2: Créer le service web

1. Cliquer "New +" → "Web Service"
2. Connecter votre repository Git
3. Configurer:
   - **Name**: `elib-web`
   - **Region**: Frankfurt
   - **Branch**: `main`
   - **Runtime**: Docker
   - **Dockerfile Path**: `./Dockerfile` (ou `./Dockerfile.render`)
   - **Plan**: Free

#### Étape 3: Configurer les variables d'environnement

Dans les paramètres du service web, ajouter:

```
DB_HOST=<hostname de votre base MySQL>
DB_NAME=elib_database
DB_USER=elib_user
DB_PASS=<password de votre base MySQL>
APP_ENV=production
APP_DEBUG=false
```

#### Étape 4: Ajouter un disque persistant

1. Dans les paramètres du service web
2. Section "Disks" → "Add Disk"
3. Configurer:
   - **Name**: `elib-uploads`
   - **Mount Path**: `/var/www/html/uploads`
   - **Size**: 1 GB (gratuit)

#### Étape 5: Déployer

1. Cliquer "Create Web Service"
2. Render va construire et déployer automatiquement
3. Suivre les logs en temps réel

## 🔧 Configuration avancée

### Utiliser un Dockerfile spécifique pour Render

Si vous voulez utiliser `Dockerfile.render` au lieu de `Dockerfile`:

```yaml
# Dans render.yaml
services:
  - type: web
    dockerfilePath: ./Dockerfile.render
```

### Configurer le health check

Render vérifie automatiquement `/healthcheck.php`:

```yaml
healthCheckPath: /healthcheck.php
```

### Augmenter les ressources (plans payants)

```yaml
services:
  - type: web
    plan: starter  # $7/mois
    # ou
    plan: standard # $25/mois
```

### Ajouter des variables d'environnement personnalisées

```yaml
envVars:
  - key: CUSTOM_VAR
    value: custom_value
  - key: SECRET_KEY
    generateValue: true  # Génère une valeur aléatoire
```

## 📊 Monitoring et maintenance

### Voir les logs

```bash
# Via le dashboard Render
Dashboard → Service → Logs

# Via Render CLI
render logs -s elib-web -f
```

### Accéder au shell du conteneur

```bash
# Via Render CLI
render shell -s elib-web
```

### Backup de la base de données

```bash
# Via Render CLI
render db backup elib-db

# Ou via le dashboard
Dashboard → Database → Backups → Create Backup
```

### Restaurer une sauvegarde

```bash
# Via Render CLI
render db restore elib-db --backup-id <backup-id>
```

## 🔒 Sécurité en production

### 1. Changer le mot de passe admin

Après le premier déploiement:
1. Se connecter avec `admin` / `admin123`
2. Aller dans "Mon Profil"
3. Changer le mot de passe immédiatement

### 2. Configurer les secrets

Utiliser les variables d'environnement pour les données sensibles:

```yaml
envVars:
  - key: DB_PASS
    sync: false  # Ne pas afficher dans les logs
```

### 3. Activer les headers de sécurité

Déjà configurés dans `includes/enhanced_security_headers.php`

### 4. Limiter l'accès à phpMyAdmin

Ne pas déployer phpMyAdmin en production. Utiliser:
- Render Database Dashboard
- Render Shell pour accès MySQL

## 💰 Coûts

### Plan gratuit (Free)
- ✅ Web Service: 750h/mois
- ✅ MySQL: 1 GB stockage
- ✅ Disk: 1 GB persistant
- ⚠️ Le service s'endort après 15 min d'inactivité
- ⚠️ Redémarre en ~30 secondes à la première requête

### Plan Starter ($7/mois)
- ✅ Pas de mise en veille
- ✅ Plus de ressources CPU/RAM
- ✅ Support prioritaire

### Optimisation des coûts

Pour rester sur le plan gratuit:
1. Accepter la mise en veille après inactivité
2. Utiliser un service de "keep-alive" (ping toutes les 10 min)
3. Limiter la taille des uploads

## 🐛 Dépannage

### Le service ne démarre pas

**Vérifier les logs:**
```bash
render logs -s elib-web
```

**Problèmes courants:**
- Variables d'environnement manquantes
- Base de données non accessible
- Permissions sur le disque persistant

### Erreur de connexion à la base de données

1. Vérifier que la base de données est créée
2. Vérifier les variables d'environnement
3. Vérifier que les services sont dans la même région

### Les uploads ne sont pas sauvegardés

1. Vérifier que le disque persistant est monté
2. Vérifier le mount path: `/var/www/html/uploads`
3. Vérifier les permissions dans les logs

### Le service est lent

**Plan gratuit:**
- Normal après mise en veille (30s de démarrage)
- Considérer un upgrade vers Starter

**Optimisations:**
- Activer le cache PHP
- Optimiser les requêtes SQL
- Compresser les images

## 🔄 Mises à jour

### Déploiement automatique

Render redéploie automatiquement à chaque push sur la branche configurée:

```bash
git add .
git commit -m "Update feature"
git push origin main
# Render détecte et redéploie automatiquement
```

### Déploiement manuel

Via le dashboard:
1. Dashboard → Service → Manual Deploy
2. Sélectionner la branche
3. Cliquer "Deploy"

### Rollback

En cas de problème:
1. Dashboard → Service → Events
2. Trouver le déploiement précédent
3. Cliquer "Rollback"

## 📱 Domaine personnalisé

### Ajouter un domaine

1. Dashboard → Service → Settings → Custom Domains
2. Ajouter votre domaine: `elib.votredomaine.com`
3. Configurer les DNS chez votre registrar:
   ```
   Type: CNAME
   Name: elib
   Value: elib-web.onrender.com
   ```
4. Render génère automatiquement un certificat SSL

## 🌍 Régions disponibles

- 🇺🇸 Oregon (US West)
- 🇺🇸 Ohio (US East)
- 🇩🇪 Frankfurt (Europe)
- 🇸🇬 Singapore (Asia)

**Recommandation:** Choisir la région la plus proche de vos utilisateurs.

## 📞 Support

### Documentation Render
- https://render.com/docs
- https://render.com/docs/docker

### Support E-Lib
- Vérifier les logs: `render logs -s elib-web`
- Consulter `/healthcheck.php` pour le statut
- Vérifier les issues GitHub du projet

## ✅ Checklist de déploiement

- [ ] Code poussé sur Git
- [ ] `render.yaml` configuré
- [ ] Base de données créée
- [ ] Variables d'environnement configurées
- [ ] Disque persistant ajouté
- [ ] Service déployé avec succès
- [ ] Health check passe (200 OK)
- [ ] Connexion à l'application réussie
- [ ] Mot de passe admin changé
- [ ] Test d'upload de livre
- [ ] Test de lecture PDF/EPUB
- [ ] Vérification des logs

---

**Note:** Le plan gratuit de Render est parfait pour tester et développer. Pour une utilisation en production avec du trafic, considérez le plan Starter ($7/mois) pour éviter la mise en veille.
