# 🚂 Déploiement E-Lib sur Railway

Guide complet pour déployer E-Lib sur Railway.app

## 🎯 Pourquoi Railway ?

✅ **Plus simple** que Render
✅ **Déploiement en un clic** depuis GitHub
✅ **Base de données MySQL incluse**
✅ **Stockage persistant** (volumes)
✅ **Plan gratuit généreux** ($5 de crédit/mois)
✅ **Logs en temps réel**
✅ **HTTPS automatique**

## 🚀 Déploiement rapide

### Étape 1: Créer un compte Railway

1. Aller sur **https://railway.app**
2. Cliquer **"Start a New Project"**
3. Se connecter avec **GitHub** (recommandé)
4. Autoriser Railway à accéder à vos repositories

### Étape 2: Déployer depuis GitHub

1. **Dashboard Railway** → **"New Project"**
2. **"Deploy from GitHub repo"**
3. Sélectionner votre repository **"E-biblio"**
4. Railway détecte automatiquement le Dockerfile
5. Cliquer **"Deploy"**

### Étape 3: Ajouter MySQL

1. Dans votre projet → **"New"** → **"Database"** → **"Add MySQL"**
2. Railway crée automatiquement la base de données
3. Les variables d'environnement sont configurées automatiquement :
   - `MYSQL_URL`
   - `MYSQL_HOST`
   - `MYSQL_PORT`
   - `MYSQL_USER`
   - `MYSQL_PASSWORD`
   - `MYSQL_DATABASE`

### Étape 4: Configurer les variables d'environnement

Railway configure automatiquement la plupart des variables, mais ajoutez :

```bash
APP_ENV=production
APP_DEBUG=false
```

### Étape 5: Accéder à votre application

- Railway génère automatiquement une URL : `https://your-app.up.railway.app`
- Identifiants par défaut : `admin` / `admin123`

## 🔧 Configuration avancée

### Variables d'environnement Railway

Railway utilise des variables spéciales :

```bash
# Automatiquement configurées par Railway
MYSQL_URL=${{MySQL.DATABASE_URL}}
MYSQL_HOST=${{MySQL.MYSQL_HOST}}
MYSQL_PORT=${{MySQL.MYSQL_PORT}}
MYSQL_USER=${{MySQL.MYSQL_USER}}
MYSQL_PASSWORD=${{MySQL.MYSQL_PASSWORD}}
MYSQL_DATABASE=${{MySQL.MYSQL_DATABASE}}

# À ajouter manuellement
APP_ENV=production
APP_DEBUG=false
```

### Domaine personnalisé

1. **Projet** → **Settings** → **Domains**
2. **"Custom Domain"**
3. Ajouter votre domaine : `elib.votredomaine.com`
4. Configurer les DNS :
   ```
   Type: CNAME
   Name: elib
   Value: your-app.up.railway.app
   ```

## 📊 Monitoring

### Logs en temps réel

```bash
# Via Railway CLI (si installé)
railway logs

# Ou via le dashboard
# Projet → Deployments → View Logs
```

### Métriques

Railway fournit automatiquement :
- CPU usage
- Memory usage
- Network traffic
- Response times

## 💾 Stockage persistant

### Volumes Railway

Railway supporte les volumes persistants :

```toml
# Dans railway.toml
[deploy]
volumes = [
  { source = "/var/www/html/uploads", target = "/uploads" }
]
```

Mais comme vous utilisez Google Drive, ce n'est pas nécessaire !

## 💰 Coûts Railway

### Plan gratuit
- **$5 de crédit/mois** (renouvelé automatiquement)
- **Pas de limite de temps** (contrairement à Render)
- **Stockage persistant inclus**
- **Pas de mise en veille**

### Usage typique E-Lib
- **Web service** : ~$3-4/mois
- **MySQL** : ~$1-2/mois
- **Total** : Dans les $5 gratuits !

## 🔄 Déploiement automatique

Railway redéploie automatiquement à chaque push sur `main` :

```bash
git add .
git commit -m "Update feature"
git push origin main
# Railway redéploie automatiquement
```

## 🐛 Dépannage

### Service ne démarre pas

```bash
# Vérifier les logs
railway logs

# Problèmes courants :
# - Port non configuré (Railway utilise PORT env var)
# - Variables d'environnement manquantes
# - Erreur dans le Dockerfile
```

### Base de données non accessible

1. Vérifier que MySQL est ajouté au projet
2. Vérifier les variables d'environnement
3. Redémarrer le service web

### Uploads ne fonctionnent pas

Avec Google Drive, ce n'est pas un problème, mais si nécessaire :
1. Ajouter un volume persistant
2. Vérifier les permissions dans le conteneur

## 🚀 Migration depuis Render

Si vous migrez depuis Render :

1. **Exporter la base de données** Render
2. **Créer le projet** Railway
3. **Importer les données** dans MySQL Railway
4. **Tester** l'application

## 📱 Fonctionnalités Railway

### CLI Railway

```bash
# Installer Railway CLI
npm install -g @railway/cli

# Se connecter
railway login

# Déployer
railway up

# Voir les logs
railway logs

# Ouvrir l'app
railway open
```

### Environnements multiples

Railway supporte plusieurs environnements :
- **Production** : Branche `main`
- **Staging** : Branche `develop`
- **Preview** : Pull requests

## 🔒 Sécurité

### Variables secrètes

Railway chiffre automatiquement :
- Mots de passe de base de données
- Clés API
- Tokens d'authentification

### HTTPS

- **Certificat SSL automatique**
- **Redirection HTTP → HTTPS**
- **Headers de sécurité** (configurés dans E-Lib)

## 📈 Scaling

### Scaling automatique

Railway peut scaler automatiquement :
- **CPU** : Jusqu'à 8 vCPU
- **RAM** : Jusqu'à 32 GB
- **Réplicas** : Scaling horizontal

### Optimisation

Pour optimiser les coûts :
1. **Surveiller l'usage** dans le dashboard
2. **Optimiser les requêtes** SQL
3. **Utiliser le cache** PHP
4. **Compresser les assets**

## 🆘 Support

### Documentation
- https://docs.railway.app
- https://railway.app/help

### Communauté
- Discord Railway
- GitHub Discussions
- Twitter @Railway

---

**Railway est généralement plus fiable et simple que Render pour les applications PHP/MySQL !**