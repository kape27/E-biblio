# ⚡ E-Lib Quick Start Guide

Guide de démarrage rapide pour mettre E-Lib en ligne en 5 minutes.

## 🎯 Objectif

Déployer E-Lib sur Render.com avec Docker en quelques commandes.

## 📋 Prérequis

- Compte GitHub/GitLab/Bitbucket (gratuit)
- Compte Render.com (gratuit)
- Git installé localement

## 🚀 Déploiement en 5 étapes

### Étape 1: Préparer le code (1 min)

```bash
# Initialiser Git si nécessaire
git init

# Ajouter tous les fichiers
git add .

# Créer le commit initial
git commit -m "Initial commit for Render deployment"
```

### Étape 2: Pousser sur GitHub (2 min)

```bash
# Créer un nouveau repository sur GitHub
# https://github.com/new

# Ajouter le remote
git remote add origin https://github.com/votre-username/elib.git

# Pousser le code
git branch -M main
git push -u origin main
```

### Étape 3: Créer un compte Render (1 min)

1. Aller sur https://render.com
2. Cliquer "Get Started"
3. S'inscrire avec GitHub (recommandé)

### Étape 4: Déployer avec Blueprint (1 min)

1. Sur le dashboard Render: https://dashboard.render.com
2. Cliquer "New +" → "Blueprint"
3. Connecter votre repository GitHub
4. Sélectionner le repository "elib"
5. Render détecte automatiquement `render.yaml`
6. Cliquer "Apply"

### Étape 5: Attendre et accéder (5-10 min)

Render va automatiquement:
- ✅ Créer la base de données MySQL
- ✅ Builder l'image Docker
- ✅ Déployer l'application
- ✅ Configurer HTTPS

Une fois terminé:
- URL: `https://elib-web.onrender.com`
- Credentials: `admin` / `admin123`

## ✅ Vérification

### 1. Vérifier le déploiement

```bash
# Vérifier le statut
curl https://elib-web.onrender.com/healthcheck.php

# Devrait retourner:
# {"status":"healthy", ...}
```

### 2. Se connecter

1. Ouvrir `https://elib-web.onrender.com`
2. Cliquer "Connexion"
3. Username: `admin`
4. Password: `admin123`

### 3. Changer le mot de passe

⚠️ **IMPORTANT**: Changez immédiatement le mot de passe admin!

1. Aller dans "Mon Profil"
2. Section "Changer le mot de passe"
3. Entrer un nouveau mot de passe fort

## 🎨 Personnalisation

### Changer le nom de l'application

Éditer `render.yaml`:
```yaml
services:
  - type: web
    name: ma-bibliotheque  # Changer ici
```

### Changer la région

```yaml
services:
  - type: web
    region: oregon  # ou: frankfurt, singapore, ohio
```

### Ajouter un domaine personnalisé

1. Dashboard → Service → Settings → Custom Domains
2. Ajouter: `bibliotheque.votredomaine.com`
3. Configurer les DNS:
   ```
   Type: CNAME
   Name: bibliotheque
   Value: elib-web.onrender.com
   ```

## 📊 Monitoring

### Voir les logs

```bash
# Via Render CLI (si installé)
render logs -s elib-web -f

# Ou via le dashboard
# Dashboard → Service → Logs
```

### Vérifier la santé

```bash
# Health check
curl https://elib-web.onrender.com/healthcheck.php

# Ou utiliser le script
./scripts/render-health-check.sh https://elib-web.onrender.com
```

## 💾 Backup

### Backup automatique

Render fait des backups automatiques de la base de données (plan payant).

### Backup manuel

1. Dashboard → Database → Backups
2. Cliquer "Create Backup"
3. Télécharger le fichier SQL

## 🔄 Mises à jour

### Déploiement automatique

Chaque push sur `main` déclenche un redéploiement:

```bash
# Faire des modifications
git add .
git commit -m "Update feature"
git push origin main

# Render redéploie automatiquement
```

### Déploiement manuel

1. Dashboard → Service → Manual Deploy
2. Sélectionner la branche
3. Cliquer "Deploy"

## 🐛 Dépannage

### Le service ne démarre pas

```bash
# Vérifier les logs
render logs -s elib-web

# Problèmes courants:
# - Variables d'environnement manquantes
# - Base de données non prête
# - Erreur dans le Dockerfile
```

### Erreur de connexion à la base de données

1. Vérifier que la base de données est créée
2. Dashboard → Database → vérifier le statut
3. Vérifier les variables d'environnement du service web

### Les uploads ne sont pas sauvegardés

1. Vérifier que le disque persistant est monté
2. Dashboard → Service → Disks
3. Vérifier le mount path: `/var/www/html/uploads`

## 💰 Coûts

### Plan gratuit
- ✅ 750 heures/mois de web service
- ✅ 1 GB de base de données MySQL
- ✅ 1 GB de stockage persistant
- ⚠️ Mise en veille après 15 min d'inactivité

### Plan Starter ($7/mois)
- ✅ Pas de mise en veille
- ✅ Performances stables
- ✅ Support prioritaire

## 📚 Ressources

- [Documentation complète Render](RENDER.md)
- [Guide Docker](DOCKER.md)
- [Comparaison des plateformes](DEPLOYMENT.md)
- [Documentation Render.com](https://render.com/docs)

## 🆘 Besoin d'aide ?

1. Vérifier les logs: `render logs -s elib-web`
2. Consulter [RENDER.md](RENDER.md) pour plus de détails
3. Vérifier le health check: `/healthcheck.php`
4. Consulter la documentation Render

---

**Félicitations! 🎉** Votre bibliothèque numérique est maintenant en ligne et accessible depuis n'importe où dans le monde!
