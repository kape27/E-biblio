# 🚀 Guide de Déploiement E-Lib

Ce document compare les différentes options de déploiement pour E-Lib.

## 📊 Comparaison des plateformes

| Plateforme | Coût | Difficulté | HTTPS | Base de données | Stockage persistant |
|------------|------|------------|-------|-----------------|---------------------|
| **Render** | Gratuit / $7/mois | ⭐ Facile | ✅ Auto | ✅ MySQL inclus | ✅ 1 GB gratuit |
| **Railway** | $5/mois | ⭐⭐ Moyen | ✅ Auto | ✅ MySQL inclus | ✅ Inclus |
| **Heroku** | $7/mois | ⭐⭐ Moyen | ✅ Auto | ❌ Add-on payant | ❌ Éphémère |
| **DigitalOcean** | $6/mois | ⭐⭐⭐ Difficile | ⚙️ Manuel | ⚙️ Manuel | ✅ Inclus |
| **AWS** | Variable | ⭐⭐⭐⭐ Complexe | ⚙️ Manuel | ⚙️ RDS payant | ✅ S3 |
| **VPS** | $5-20/mois | ⭐⭐⭐ Difficile | ⚙️ Manuel | ⚙️ Manuel | ✅ Inclus |

## 🎯 Recommandations

### Pour débuter / Prototypes
**→ Render (Plan gratuit)**
- ✅ Déploiement en 5 minutes
- ✅ Tout inclus (web + DB + stockage)
- ✅ HTTPS automatique
- ⚠️ Mise en veille après 15 min d'inactivité

### Pour production légère
**→ Render (Plan Starter - $7/mois)**
- ✅ Pas de mise en veille
- ✅ Performances stables
- ✅ Support prioritaire
- ✅ Backups automatiques

### Pour production avec trafic élevé
**→ DigitalOcean App Platform ou VPS**
- ✅ Plus de contrôle
- ✅ Meilleures performances
- ✅ Scaling horizontal
- ⚠️ Configuration plus complexe

## 🚀 Déploiement sur Render (Recommandé)

### Pourquoi Render ?

1. **Simplicité**: Déploiement en un clic depuis Git
2. **Tout inclus**: Web service + MySQL + Stockage persistant
3. **HTTPS gratuit**: Certificat SSL automatique
4. **Plan gratuit**: Parfait pour tester et développer
5. **Docker natif**: Utilise votre Dockerfile directement

### Guide rapide

```bash
# 1. Préparer le code
git add .
git commit -m "Deploy to Render"
git push origin main

# 2. Sur Render Dashboard
# https://dashboard.render.com
# - New + → Blueprint
# - Connecter votre repository
# - Render détecte render.yaml
# - Cliquer "Apply"

# 3. Attendre 5-10 minutes
# Render va:
# - Créer la base de données MySQL
# - Builder l'image Docker
# - Déployer l'application
# - Configurer HTTPS

# 4. Accéder à votre app
# URL fournie: https://elib-web.onrender.com
```

📖 **Documentation détaillée**: [RENDER.md](RENDER.md)

## 🐳 Déploiement sur Railway

### Configuration

```yaml
# railway.toml
[build]
builder = "DOCKERFILE"
dockerfilePath = "Dockerfile"

[deploy]
startCommand = "apache2-foreground"
healthcheckPath = "/healthcheck.php"
healthcheckTimeout = 100
restartPolicyType = "ON_FAILURE"
restartPolicyMaxRetries = 10
```

### Variables d'environnement

```bash
DB_HOST=${{MySQL.MYSQL_HOST}}
DB_NAME=${{MySQL.MYSQL_DATABASE}}
DB_USER=${{MySQL.MYSQL_USER}}
DB_PASS=${{MySQL.MYSQL_PASSWORD}}
```

## ☁️ Déploiement sur Heroku

### Limitations

⚠️ **Attention**: Heroku a des limitations importantes:
- Système de fichiers éphémère (uploads perdus au redémarrage)
- Base de données MySQL payante (add-on ClearDB)
- Nécessite un stockage externe (S3) pour les uploads

### Configuration

```yaml
# app.json
{
  "name": "E-Lib",
  "description": "Digital Library System",
  "image": "heroku/php",
  "addons": [
    "cleardb:ignite"
  ],
  "env": {
    "APP_ENV": "production"
  }
}
```

## 🖥️ Déploiement sur VPS (DigitalOcean, Linode, etc.)

### Prérequis

- VPS avec Ubuntu 22.04
- Accès SSH root
- Nom de domaine (optionnel)

### Installation

```bash
# 1. Connexion SSH
ssh root@your-server-ip

# 2. Installation Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# 3. Installation Docker Compose
apt install docker-compose-plugin

# 4. Cloner le projet
git clone https://github.com/your-username/elib.git
cd elib

# 5. Configuration
cp .env.example .env
nano .env  # Éditer les variables

# 6. Démarrage
docker-compose up -d

# 7. Configuration Nginx (optionnel)
apt install nginx certbot python3-certbot-nginx

# 8. Configurer le reverse proxy
nano /etc/nginx/sites-available/elib
```

### Configuration Nginx

```nginx
server {
    listen 80;
    server_name elib.votredomaine.com;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### HTTPS avec Let's Encrypt

```bash
certbot --nginx -d elib.votredomaine.com
```

## 🔒 Sécurité en production

### Checklist de sécurité

- [ ] Changer le mot de passe admin par défaut
- [ ] Utiliser des mots de passe forts pour la DB
- [ ] Activer HTTPS (certificat SSL)
- [ ] Configurer les CORS si nécessaire
- [ ] Limiter les tailles d'upload
- [ ] Activer les logs de sécurité
- [ ] Configurer les backups automatiques
- [ ] Mettre à jour régulièrement
- [ ] Surveiller les logs d'erreur
- [ ] Configurer un monitoring (UptimeRobot, etc.)

### Variables d'environnement sensibles

Ne jamais commiter:
```bash
DB_PASS=votre_mot_de_passe_fort
MYSQL_ROOT_PASSWORD=votre_root_password
SECRET_KEY=votre_clé_secrète
```

Utiliser des secrets ou variables d'environnement sur la plateforme.

## 📊 Monitoring

### Services de monitoring gratuits

1. **UptimeRobot** (https://uptimerobot.com)
   - Ping toutes les 5 minutes
   - Alertes email/SMS
   - Gratuit jusqu'à 50 monitors

2. **Render Health Checks**
   - Intégré automatiquement
   - Vérifie `/healthcheck.php`

3. **Google Analytics**
   - Suivi du trafic
   - Analyse des utilisateurs

### Script de monitoring personnalisé

```bash
# Ajouter à crontab
*/5 * * * * /path/to/scripts/render-health-check.sh https://your-app.onrender.com
```

## 💾 Stratégie de backup

### Backups automatiques

**Sur Render:**
```bash
# Backups quotidiens automatiques (plan payant)
# Ou manuel via dashboard
```

**Sur VPS:**
```bash
# Crontab pour backup quotidien
0 2 * * * /path/to/scripts/backup.sh
```

### Backup manuel

```bash
# Base de données
docker-compose exec db mysqldump -u elib_user -p elib_database > backup.sql

# Fichiers uploads
tar -czf uploads_backup.tar.gz uploads/
```

## 🔄 Mises à jour

### Déploiement continu (CI/CD)

Le projet inclut GitHub Actions pour déploiement automatique:

```yaml
# .github/workflows/deploy-render.yml
# Se déclenche automatiquement sur push vers main
```

### Mise à jour manuelle

```bash
# 1. Pousser les changements
git add .
git commit -m "Update feature"
git push origin main

# 2. Render redéploie automatiquement
# Ou déclencher manuellement via dashboard
```

## 📈 Scaling

### Scaling vertical (plus de ressources)

**Render:**
- Starter: 0.5 GB RAM
- Standard: 2 GB RAM
- Pro: 4 GB RAM

### Scaling horizontal (plusieurs instances)

Nécessite:
- Load balancer
- Session partagée (Redis)
- Stockage partagé (S3)

## 💰 Estimation des coûts

### Petit projet (< 1000 utilisateurs/mois)
- **Render Free**: $0/mois (avec limitations)
- **Render Starter**: $7/mois (recommandé)

### Projet moyen (1000-10000 utilisateurs/mois)
- **Render Standard**: $25/mois
- **DigitalOcean**: $12-24/mois

### Grand projet (> 10000 utilisateurs/mois)
- **Render Pro**: $85/mois
- **AWS/GCP**: $50-200/mois (variable)
- **VPS dédié**: $40-100/mois

## 🆘 Support et dépannage

### Logs

```bash
# Render
render logs -s elib-web -f

# Docker local
docker-compose logs -f web

# VPS
docker logs elib-web -f
```

### Problèmes courants

1. **Service ne démarre pas**: Vérifier les logs
2. **Erreur DB**: Vérifier les variables d'environnement
3. **Uploads perdus**: Vérifier le disque persistant
4. **Performance lente**: Vérifier le plan/ressources

## 📚 Ressources

- [Documentation Render](https://render.com/docs)
- [Documentation Docker](https://docs.docker.com)
- [Guide sécurité PHP](https://www.php.net/manual/en/security.php)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)

---

**Recommandation finale**: Commencez avec Render (gratuit ou Starter $7/mois) pour la simplicité, puis migrez vers un VPS si vous avez besoin de plus de contrôle ou de performances.
