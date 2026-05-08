# Verso Consultation Plugin

Système simple et décentralisé pour gérer les demandes de consultation:
- **Formulaire WordPress** simple qui envoie des emails
- **Dashboard SQLite** pour suivre les demandes
- **Suivi ERP** sans webhook - statut manuel

## ✨ Fonctionnalités

- ✅ Formulaire simple (propriétaire, vétérinaire, animal, consultation)
- ✅ Email automatique à consultations@verso-vet.com
- ✅ Dashboard SQLite pour suivi des demandes
- ✅ Synchronisation emails → SQLite (via cron)
- ✅ Gestion des statuts (new, reviewed, integrated, archived)
- ✅ Historique des changements de statut
- ✅ Export JSON pour ERP
- ✅ Pas de dépendance WordPress pour le suivi

## 📦 Installation

1. **Copier le plugin**
   ```bash
   cp -r verso-consultation-plugin /var/www/verso-vet.com/wp-content/plugins/
   ```

2. **Activer dans WordPress**
   ```bash
   wp plugin activate verso-consultation-plugin
   ```

3. **Générer la clé API**
   ```bash
   wp option add verso_api_key "$(openssl rand -hex 32)"
   ```

## 🚀 Utilisation

### Page de Consultation

La page est accessible à: `https://verso-vet.com/consultation-refere/`

Shortcode: `[verso_consultation_form]`

### Sections du Formulaire

1. **Propriétaire/Contact** (obligatoire)
   - Nom, Prénom
   - Email, Téléphone
   - Adresse

2. **Vétérinaire Référant** (optionnel)
   - Nom, Prénom, Clinique
   - Email, Téléphone

3. **Animal** (obligatoire)
   - Nom, Espèce, Race

4. **Consultation** (obligatoire)
   - Motif de la demande

5. **Documents** (optionnel)
   - PDF, JPG, PNG, GIF, TIFF
   - Max: 50 MB par fichier

## 📂 Architecture

### Structure des Fichiers
```
verso-consultation-plugin/
├── verso-consultation-plugin.php      # Plugin principal
├── includes/
│   └── class-file-handler.php         # Endpoints + storage
├── templates/
│   ├── template-hero-gradient.txt     # Template Divi gradient
│   └── template-hero-image.txt        # Template Divi image
├── create-page.sh                     # Script création pages
├── README.md                          # Ce fichier
├── SETUP.md                           # Configuration
├── DASHBOARD-API.md                   # Documentation API
└── TEMPLATE-GUIDE.md                  # Guide Divi
```

### Flux de Données

```
Utilisateur
    ↓
Formulaire Web [verso_consultation_form]
    ↓
Email à consultations@verso-vet.com
    ↓
Cron: sync_emails.py (toutes les heures)
    ↓
SQLite: consultations.db
    ↓
Dashboard CLI (python3 cli.py list/show/status)
    ↓
Export JSON pour ERP
```

## 🔌 Interfaces

### Formulaire WordPress
```
https://verso-vet.com/consultation-refere/
[verso_consultation_form]

→ Envoie un email simple à consultations@verso-vet.com
```

### Dashboard SQLite

```bash
cd dashboard

# Initialiser
python3 init_db.py

# Synchroniser emails
python3 sync_emails.py

# Lister les consultations
python3 cli.py list new              # Nouvelles
python3 cli.py list integrated       # Intégrées à l'ERP

# Afficher détails
python3 cli.py show verso-1620000000000-abc123def

# Mettre à jour statut
python3 cli.py status verso-... integrated "Envoyé à VetoPartner CRM"

# Exporter pour l'ERP
python3 cli.py export integrated consultations.json
```

## 🔐 Sécurité

### Authentification
- **Public:** Formulaire, upload, suppression (UUID aléatoire = sécurité)
- **Dashboard:** Clé API dans en-tête `X-Verso-API-Key`

### Validation Fichiers
- ✅ Whitelist: pdf, jpg, jpeg, png, gif, tiff
- ✅ Limite: 50 MB par fichier
- ✅ Protection path traversal avec `realpath()`
- ✅ Répertoires isolés par UUID

### Données
- ✅ Sanitisation tous inputs (text, email, textarea)
- ✅ Escaping outputs
- ✅ Métadonnées dans posts WordPress
- ✅ Nettoyage auto répertoires vides

## 🆘 Dépannage

### Formulaire ne s'affiche pas
- Vérifier shortcode: `[verso_consultation_form]`
- Vérifier page type = "Page" (pas "Post")
- Vider le cache si plugin de caching actif

### Email non reçu
- Vérifier Postfix: `tail -f /var/log/mail.log`
- Tester: `wp eval 'wp_mail("test@example.com", "Test", "Body");'`
- Vérifier adresse: consultations@verso-vet.com existe

### Upload échoue
- Vérifier permissions: `/wp-content/uploads/verso-consultations/`
- Vérifier taille < 50 MB
- Vérifier type de fichier (pdf, jpg, png, gif, tiff)

### API retourne 403
- Vérifier clé API: `wp option get verso_api_key`
- Regenerate: `wp option delete verso_api_key && wp option add verso_api_key "$(openssl rand -hex 32)"`

### Debug
```bash
# Voir les logs WordPress
tail -f /var/www/verso-vet.com/wp-content/debug.log

# Tester un endpoint
API_KEY=$(wp option get verso_api_key)
curl -H "X-Verso-API-Key: $API_KEY" \
  "https://verso-vet.com/wp-json/verso/v1/consultations?per_page=1"
```

## 📚 Documentation

- **[SETUP.md](SETUP.md)** - Installation détaillée
- **[DASHBOARD-API.md](DASHBOARD-API.md)** - Endpoints + exemples
- **[TEMPLATE-GUIDE.md](TEMPLATE-GUIDE.md)** - Pages Divi

## 📝 Support

- Consultez **SETUP.md** pour configuration
- Consultez **DASHBOARD-API.md** pour API
- Console JS (F12) pour erreurs frontend
- `/var/log/apache2/error.log` pour erreurs serveur

## 📄 Licence

© 2026 Verso Vet. Tous droits réservés.
