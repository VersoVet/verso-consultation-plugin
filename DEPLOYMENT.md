# verso-consultation-plugin - Guide de Déploiement

> **Trois méthodes automatisées** pour déployer le plugin sans toucher directement au serveur

---

## 🎯 Méthode 1: ZIP via WordPress Admin (Manuel, 2 min)

**Avantages:** Aucune dépendance, interface graphique, visible
**Inconvénients:** Manuel, pas d'historique

### Étapes:
1. **Créer le ZIP:**
   ```bash
   cd verso-consultation-plugin
   mkdir -p dist/verso-consultation-plugin
   cp verso-consultation-plugin.php js/ css/ dist/verso-consultation-plugin/
   cd dist && zip -r verso-consultation-plugin.zip verso-consultation-plugin/
   ```

2. **Uploader via WordPress Admin:**
   - Va à: `https://verso-vet.com/wp-admin/plugins.php?tab=upload`
   - Clique "Envoyer une extension"
   - Sélectionne le ZIP
   - Clique "Installer"
   - Clique "Activer"

3. **Vérifie:**
   - La page `demande-de-consultation` affiche le formulaire
   - Les logs WordPress ne montrent pas d'erreurs

---

## 🤖 Méthode 2: WP-CLI Local (Automatisé, 30s)

**Avantages:** Rapide, scriptable, historique Git
**Inconvénients:** Nécessite WP-CLI sur la machine locale

### Installation de WP-CLI:
```bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp
```

### Déploiement:
```bash
# Methode 1: Script automatisé (recommandé)
./deploy-auto.sh

# Methode 2: Commandes manuelles
zip -r verso-consultation-plugin.zip verso-consultation-plugin.php js/ css/
wp plugin install verso-consultation-plugin.zip --activate --url=https://verso-vet.com
```

### Variables d'environnement (optionnel):
```bash
export WP_CLI_PATH=/usr/local/bin/wp
export WP_SITE_URL=https://verso-vet.com
export WP_ADMIN_USER=onyx

./deploy-auto.sh
```

---

## 🔄 Méthode 3: GitHub Actions (CI/CD Automatisé)

**Avantages:** Complètement automatisé, historique, version taggée
**Inconvénients:** Nécessite secrets GitHub, un peu plus complexe

### Configuration:

#### Étape 1: Ajouter les secrets GitHub
Va à: `https://github.com/VersoVet/verso-consultation-plugin/settings/secrets/actions`

Ajoute ces secrets:
```
WORDPRESS_HOST         = verso-vet.com
WORDPRESS_USER         = onyx
WORDPRESS_PASSWORD     = (app_password du Vault)
WORDPRESS_PATH         = /var/www/verso-vet.com
SSH_HOST               = ssh.cluster129.hosting.ovh.net
SSH_USER               = versovx-onyx
SSH_PRIVATE_KEY        = (clé SSH privée)
```

#### Étape 2: Workflow automatique
Le fichier `.github/workflows/deploy.yml` est déjà configura. À chaque push sur `main`:
1. ✅ Tests PHP (syntax check)
2. 📦 Crée le ZIP du plugin
3. 🚀 Déploie via WP-CLI
4. 📌 Crée une release avec le ZIP

### Déploiement par commit:
```bash
git add verso-consultation-plugin.php
git commit -m "fix: Enable file upload processing"
git push origin main

# → GitHub Actions déploie automatiquement!
```

### Utiliser une release:
```bash
# GitHub Actions crée automatiquement des releases
# Tu peux télécharger le ZIP depuis: 
# https://github.com/VersoVet/verso-consultation-plugin/releases/latest
```

---

## 📋 Tableau Comparatif

| Aspect | ZIP Admin | WP-CLI Local | GitHub Actions |
|--------|-----------|--------------|----------------|
| **Temps** | 2 min | 30 sec | Automatique |
| **Dépendances** | Aucune | WP-CLI | Secrets GitHub |
| **Historique** | Non | Git | Git + Releases |
| **Sécurité** | Manuelle | En local | Chiffré |
| **Visibilité** | WordPress | Logs | GitHub |
| **Rollback** | Manuel | `git revert` | Releases |
| **Idéal pour** | Test unique | Dev régulier | Prod continu |

---

## 🔐 Sécurité & Bonnes Pratiques

### ✅ À faire:
- Utiliser des app_passwords (pas de vrais mots de passe)
- Stocker les secrets dans GitHub (pas dans le repo)
- Tester d'abord en local via WP-CLI
- Committer avant de déployer
- Utiliser des releases pour suivre les versions

### ❌ À ne pas faire:
- Ne pas hardcoder les credentials dans les scripts
- Ne pas utiliser SSH/FTP directement (utiliser WP-CLI)
- Ne pas modifier le fichier plugin sur le serveur (toujours redéployer)
- Ne pas sauter les tests PHP

---

## 🧪 Tests Post-Déploiement

Après chaque déploiement, vérifie:

```bash
# 1. Plugin est actif
wp plugin list --url=https://verso-vet.com | grep verso-consultation-plugin

# 2. Pas d'erreurs dans les logs
curl https://verso-vet.com/wp-content/debug.log | grep -i error | tail -5

# 3. Formulaire est accessible
curl https://verso-vet.com/demande-de-consultation/ | grep verso-form

# 4. AJAX handler fonctionne
curl -X POST https://verso-vet.com/wp-admin/admin-ajax.php \
  -d "action=verso_submit_consultation" \
  -d "owner_nom=Test" \
  -d "owner_prenom=User" \
  -d "owner_email=test@test.com" \
  -d "animal_nom=Rex" \
  -d "animal_espece=Chien" \
  -d "motif=Test" | jq .
```

---

## 🐛 Troubleshooting

### Plugin ne s'active pas
```bash
# Vérifiez la syntaxe PHP
php -l verso-consultation-plugin.php

# Vérifiez les logs WordPress
tail -100 /var/www/verso-vet.com/wp-content/debug.log
```

### WP-CLI: "command not found"
```bash
# Installez WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

### GitHub Actions échoue
```bash
# Vérifiez les logs:
# https://github.com/VersoVet/verso-consultation-plugin/actions

# Vérifiez les secrets sont bien définis:
# https://github.com/VersoVet/verso-consultation-plugin/settings/secrets/actions
```

---

## 📞 Support

Problèmes? Consultez:
- `.github/workflows/deploy.yml` - Workflow CI/CD
- `deploy-auto.sh` - Script WP-CLI
- `README.md` - Documentation générale
