# Configuration du Plugin Verso Consultation

Guide de configuration initiale du plugin pour verso-vet.com.

---

## Installation

1. **Copier le dossier du plugin** dans `/wp-content/plugins/verso-consultation-plugin/`
2. **Activer le plugin** via WordPress admin
3. **Vérifier** que le shortcode `[verso_consultation_form]` est présent sur la page

```
https://verso-vet.com/consultation-refere/
```

---

## Configuration Initiale

### 1. Activer le Custom Post Type

Le plugin crée automatiquement un custom post type `verso_consultation` pour stocker les consultations.

**Vérifier:**
```bash
# En SSH sur verso-vet.com
wp post-type list | grep verso
```

Doit afficher: `verso_consultation`

---

### 2. Générer une Clé API pour le Dashboard

La clé API est nécessaire pour accéder aux endpoints de gestion (liste, récupération, statut).

**Générer une clé sécurisée:**
```bash
# Sur verso-vet.com (SSH)
wp option add verso_api_key "$(openssl rand -hex 32)"

# Récupérer la clé
wp option get verso_api_key
```

**Ou via PHP (wp-admin):**
```php
// Ajouter temporairement dans functions.php ou un snippet plugin
update_option('verso_api_key', 'votre-clé-ici');
```

---

### 3. Vérifier l'Email de Destination

Le plugin envoie les consultations à `consultations@verso-vet.com`.

**Vérifier que l'adresse existe:**
```bash
# En SSH sur verso-vet.com
mail -u consultations
```

**Configurer un alias ou forwarder si nécessaire:**
```bash
# Ajouter dans /etc/aliases (Postfix)
consultations: consultation-team@verso-vet.com

# Recharger Postfix
postfix reload
```

---

## Test de Fonctionnement

### 1. Remplir et Soumettre le Formulaire

1. Aller sur https://verso-vet.com/consultation-refere/
2. Remplir tous les champs obligatoires
3. Optionnel: Ajouter des documents (PDF, JPG, PNG)
4. Cliquer "Envoyer la Demande"

**Vérifier:**
- ✅ Message de succès s'affiche
- ✅ Email reçu à consultations@verso-vet.com
- ✅ Consultation visible dans WordPress (Posts > Consultations)

### 2. Tester l'API Dashboard

**Récupérer une consultation:**
```bash
API_KEY="votre-clé-api"
curl -H "X-Verso-API-Key: $API_KEY" \
  "https://verso-vet.com/wp-json/verso/v1/consultations?status=new"
```

**Mettre à jour le statut:**
```bash
curl -X POST -H "X-Verso-API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"status":"reviewed"}' \
  "https://verso-vet.com/wp-json/verso/v1/consultations/ID/status"
```

### 3. Tester le Upload de Fichiers

- Soumettre un formulaire avec des fichiers
- Vérifier dans `/wp-content/uploads/verso-consultations/` que les fichiers sont présents
- Cliquer les liens dans l'email pour télécharger

---

## Stockage des Fichiers

### Emplacement

```
/wp-content/uploads/verso-consultations/
├── verso-1620000000000-abc123def/
│   ├── radiographie.pdf
│   ├── analyse.jpg
│   └── ...
└── verso-1620000000001-def456ghi/
    └── document.pdf
```

### Gestion Manuelle

**Supprimer les fichiers d'une consultation:**
```bash
# Via API (plus sûr)
curl -X DELETE \
  "https://verso-vet.com/wp-json/verso/v1/delete/{uuid}/{filename}"

# Via SSH (manuel)
rm -rf /var/www/verso-vet.com/wp-content/uploads/verso-consultations/verso-*
```

### Maintenance du Répertoire

Les répertoires vides sont automatiquement supprimés après le dernier fichier.

**Nettoyer manuellement:**
```bash
# Trouver les répertoires vides
find /wp-content/uploads/verso-consultations -type d -empty

# Les supprimer
find /wp-content/uploads/verso-consultations -type d -empty -delete
```

---

## Dépannage

### Emails non reçus

**Vérifier:**
```bash
# Logs de Postfix
tail -f /var/log/mail.log

# Vérifier que WordPress peut envoyer des emails
wp eval 'wp_mail("test@example.com", "Test", "Body");'
```

**Solution:**
```bash
# Configurer SMTP si nécessaire
wp plugin install wp-mail-smtp --activate

# Ou via PHP dans functions.php
define('WORDPRESS_SSMTP_HOST', 'smtp.exemple.com');
define('WORDPRESS_SSMTP_PORT', 587);
```

### Fichiers non uploadés

**Vérifier les permissions:**
```bash
# Répertoire uploads doit avoir les bonnes permissions
ls -la /wp-content/uploads/verso-consultations/

# Changer si nécessaire
chmod 755 /wp-content/uploads/verso-consultations
```

**Vérifier les limites PHP:**
```bash
# Dans php.ini
upload_max_filesize = 50M
post_max_size = 50M
```

### API retourne 403 Unauthorized

**Vérifier la clé API:**
```bash
# Récupérer la clé stockée
wp option get verso_api_key

# Comparer avec celle envoyée dans X-Verso-API-Key
```

**Regenerate la clé:**
```bash
wp option delete verso_api_key
wp option add verso_api_key "$(openssl rand -hex 32)"
```

---

## Sauvegardes

### Sauvegarder les Consultations

```bash
# Export JSON de toutes les consultations
curl -s -H "X-Verso-API-Key: YOUR_KEY" \
  "https://verso-vet.com/wp-json/verso/v1/consultations?per_page=100" \
  | jq . > consultations-backup.json

# Export des fichiers
rsync -avz root@verso-vet.com:/var/www/verso-vet.com/wp-content/uploads/verso-consultations/ ./verso-files-backup/
```

---

## Support

- **Documentation API:** Voir `DASHBOARD-API.md`
- **Erreurs de formulaire:** Vérifier la console JavaScript du navigateur
- **Problèmes de serveur:** Vérifier `/var/log/apache2/error.log` ou `/var/log/nginx/error.log`
