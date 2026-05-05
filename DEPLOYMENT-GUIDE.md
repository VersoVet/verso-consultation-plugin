# Guide de Déploiement - verso-consultation-plugin

## 📋 Vue d'ensemble

Ce guide explique comment déployer automatiquement le plugin verso-rest-api sur verso-vet.com.

### Architecture

```
verso-consultation-plugin/
├── verso-consultation-plugin.php     (Plugin formulaire - déjà déployé)
├── includes/
│   ├── class-form-handler.php
│   ├── class-webhook-sender.php
│   └── class-vault-client.php
├── verso-rest-api/
│   ├── verso-rest-api.php           (Plugin email - déploiement automatisé)
│   └── README.md
├── build-verso-rest-api.sh          (Crée le plugin)
├── deploy-verso-rest-api.sh         (Déploie et active le plugin)
└── DEPLOYMENT-GUIDE.md              (Ce fichier)
```

---

## 🔑 Credentials Requises

### 1. OVH SSH

Pour accéder au serveur verso-vet.com via SSH:

```
Host: ssh.cluster129.hosting.ovh.net
User: versovx-onyx
Password: 8up2dBW4cH4i6Zg
Port: 22
```

**Stockage dans le Vault:**
```json
{
  "key": "verso_vet_ovh_ssh",
  "value": {
    "host": "ssh.cluster129.hosting.ovh.net",
    "user": "versovx-onyx",
    "password": "8up2dBW4cH4i6Zg",
    "port": 22
  }
}
```

### 2. WordPress REST API

Pour activer/vérifier le plugin:

```
Site: https://verso-vet.com
User: onyx
App Password: l20JcUKAFqmYYNPdlEKpvVBA
```

**Stockage dans le Vault:**
```json
{
  "key": "verso_vet_wordpress",
  "value": {
    "site_url": "https://verso-vet.com",
    "username": "onyx",
    "app_password": "l20JcUKAFqmYYNPdlEKpvVBA"
  }
}
```

### 3. OVH FTP (Optionnel)

Alternative à SSH pour les uploads:

```
Host: ftp.cluster129.hosting.ovh.net
User: versovx-onyx
Password: Dolibarr2026!
Port: 21
```

---

## 🚀 Déploiement Automatisé

### Étape 1: Construire le plugin

```bash
cd /home/onyx/projects/skills/verso-consultation-plugin

# Crée le fichier verso-rest-api.php
./build-verso-rest-api.sh
```

### Étape 2: Déployer et activer

```bash
# Définir les credentials
export OVH_SSH_PASS='8up2dBW4cH4i6Zg'
export WP_PASS='l20JcUKAFqmYYNPdlEKpvVBA'

# Déployer
./deploy-verso-rest-api.sh
```

### Résultat attendu

```
[1/5] Création du répertoire du plugin...
✓ Répertoire créé/existant
[2/5] Upload du fichier du plugin...
✓ Plugin uploadé (3434 bytes)
[3/5] Activation du plugin via SSH...
✓ Plugin activé avec succès
[4/5] Test de l'endpoint...
✓ Endpoint fonctionne!
✓ Demande envoyée avec succès! Vous recevrez une confirmation par email.
[5/5] Vérification finale...
✓ Plugin vérifié comme ACTIF
```

---

## 📧 Configuration Email

### Endpoint

```
POST /wp-json/verso/v1/submit
```

### Données acceptées

```json
{
  "owner_nom": "string (requis)",
  "owner_prenom": "string (requis)",
  "owner_email": "string (requis)",
  "owner_telephone": "string (requis)",
  "vet_nom": "string (optionnel)",
  "vet_clinique": "string (optionnel)",
  "animal_nom": "string (requis)",
  "animal_espece": "string (requis)",
  "animal_race": "string (optionnel)",
  "motif": "string (requis)"
}
```

### Email généré

**Destinataire:** consultations@verso-vet.com

**Sujet:** `[Verso Vet] Nouvelle demande - {animal_nom} ({animal_espece})`

**Corps:** Email structuré avec tous les détails du formulaire

---

## 🔄 Déploiement pour futures versions

### Mise à jour du plugin

1. Modifier `verso-rest-api.php` dans le répertoire `verso-rest-api/`
2. Reconstruire: `./build-verso-rest-api.sh`
3. Redéployer: `./deploy-verso-rest-api.sh`

### Automatisation complète

Pour automatiser dans un CI/CD:

```bash
#!/bin/bash
set -e

# Récupérer les credentials du Vault
OVH_SSH_PASS=$(curl -s -H "X-Vault-Token: $ONYX_VAULT_TOKEN" \
  http://10.0.0.44:8050/vault/verso_vet_ovh_ssh | jq -r '.value | fromjson | .password')

WP_PASS=$(curl -s -H "X-Vault-Token: $ONYX_VAULT_TOKEN" \
  http://10.0.0.44:8050/vault/verso_vet_wordpress | jq -r '.value | fromjson | .app_password')

# Exporter
export OVH_SSH_PASS WP_PASS

# Déployer
cd /home/onyx/projects/skills/verso-consultation-plugin
./build-verso-rest-api.sh
./deploy-verso-rest-api.sh
```

---

## 📊 Statut Actuel (2026-05-05)

✅ **Déployé et testé**

- Plugin verso-rest-api: ACTIF
- Endpoint /wp-json/verso/v1/submit: FONCTIONNEL
- Email: consultations@verso-vet.com
- Test d'envoi: SUCCÈS

---

## 🔧 Troubleshooting

### Le plugin ne s'active pas

```bash
# Vérifier les permissions
ssh versovx-onyx@ssh.cluster129.hosting.ovh.net
ls -la ~/www/wp-content/plugins/verso-rest-api/

# Vérifier WordPress
cd ~/www
wp plugin list | grep verso-rest-api
```

### L'endpoint retourne 404

```bash
# Vérifier que le plugin est actif
curl -u onyx:l20JcUKAFqmYYNPdlEKpvVBA \
  https://verso-vet.com/wp-json/wp/v2/plugins | jq '.[] | select(.name | contains("Verso REST"))'
```

### L'email n'est pas envoyé

Vérifier que WordPress mail est configuré correctement sur OVH. Les emails sont envoyés par le système mail de WordPress.

---

## 📝 Notes

- Les scripts utilisent `sshpass` pour l'authentification SSH
- Les credentials sont à passer par variables d'environnement
- Pour la production, utiliser le Vault OnyxVault
- L'endpoint ne nécessite pas d'authentification (public)
- Les emails sont envoyés de manière synchrone (bloquant)

---

## 📞 Support

Pour mettre à jour le plugin ou les scripts:

1. Modifier les fichiers locaux dans `/home/onyx/projects/skills/verso-consultation-plugin/`
2. Exécuter `./build-verso-rest-api.sh`
3. Exécuter `./deploy-verso-rest-api.sh`
4. Vérifier l'endpoint
5. Committer les changements
