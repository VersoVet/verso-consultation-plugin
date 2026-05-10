# verso-consultation-plugin - Guide de Développement Forge

> **FICHIER GÉNÉRÉ PAR FORGE - À RÉGÉNÉRER SI BESOIN**: `forge regenerate-claude verso-consultation-plugin`

---

## Écosystème verso-consultation-plugin

### Type de Skill
- **Type**: `custom` (WordPress plugin, pas Python)
- **Statut**: 🟢 PRODUCTION (v3.0.0)
- **Port**: N/A (routing disabled - plugin WordPress uniquement)
- **Target Deployment**: verso-vet.com (OVH, sans systemd)

### Cycle de Vie du Plugin
```
Form Submission → AJAX Handler → Email Dispatch → IMAP Monitoring → ERP Integration
```

---

## ⚠️ STRUCTURE - À LIRE EN PREMIER!

### Fichiers Obligatoires (Forge Phase 1)
```
verso-consultation-plugin/
├── CLAUDE.md              ← CE FICHIER (auto-généré)
├── ARCHITECTURE.md        ← Structure + modules (📌 À CRÉER)
├── API.md                 ✅ Documentation endpoints
├── TODO.md                ✅ Tâches et status
├── manifest.json          ✅ Configuration Forge
├── .gitignore             ✅ Sécurité
│
├── verso-consultation-plugin.php  ← Plugin principal
├── deploy-form.sh                 ← Déploiement sécurisé (scp only)
│
├── js/
│   └── form.js            ← AJAX + validation (jQuery)
│
└── css/
    └── style.css          ← Responsive styling (Verso branding)
```

### Validation Critique (18 phases Forge)

#### Phase 1: Structure ✅
- `CLAUDE.md` présent
- `ARCHITECTURE.md` présent
- `API.md` documenté
- `TODO.md` à jour
- `manifest.json` valide
- `.gitignore` complèt

#### Phase 2: Fraîcheur ✅
- `TODO.md` reflète l'état du code
- `ARCHITECTURE.md` documenté

#### Phase 3: Manifest.json ⚠️ IMPORTANT
```json
{
  "core": {
    "name": "verso-consultation-plugin",
    "type": "custom",
    "description": "WordPress plugin for consultation form submissions",
    "brain_area": "cerebellum"
  },
  "forge": {
    "type": "custom"
  },
  "heart": {
    "deployment": {
      "target_host": "10.0.0.90",
      "skip_systemd": true
    }
  },
  "routing": {
    "disabled": true
  }
}
```

#### Phase 7: Git & .gitignore ✅
```
# SECURITY-CRITICAL
.env
*.key
*.pem
credentials*
secrets*

# WordPress
wp-config-local.php
local-config.php

# Logs
*.log
debug.log

# Python (future)
__pycache__/
*.pyc
.pytest_cache/
```

---

## Architecture du Plugin (v3.0.0)

### Upload & Stockage Sécurisé (TEMPORAIRE)

⚠️ **IMPORTANT — Aucun stockage permanent dans ce plugin**:
- Fichiers uploadés → verso-consultations/{uuid}/ (intermédiaire)
- Email envoyé → consultations@verso-vet.com (avec pièces jointes)
- Cleanup → répertoire {uuid}/ supprimé automatiquement
- **Stockage permanent** = `consultation-requests` (IMAP monitor extrait les pièces jointes du mail)

#### Répertoire Dédié (Temporaire)
```
wp-content/uploads/verso-consultations/           ← Racine (deny from all)
├── .htaccess                                      ← Bloque accès HTTP
├── index.php                                      ← Silence stub
└── verso-{timestamp}-{hash}/                      ← Par UUID (TEMPORAIRE)
    ├── photo.jpg
    ├── document.pdf
    └── [SUPPRIMÉ APRÈS EMAIL] ✓
```

**Constante Source de Vérité**:
```php
define('VERSO_UPLOAD_SUBDIR', 'verso-consultations');
```

#### Sécurité Chemin (Path Traversal Prevention)
1. **UUID validation** → `preg_match('/^verso-\d{10}-[a-f0-9]{8}$/', ...)`
2. **realpath() resolution** → Résout symlinks & `..`
3. **strpos() confinement** → Vérifie inclusion dans base directory
4. **Fichiers seuls** → Pas de `rmdir -r` récursif

**Fonction critique**: `verso_safe_delete_consultation_dir($uuid)` (voir ARCHITECTURE.md)

### Upload Validation

#### Limites de Stockage
- Max **10 fichiers** par soumission
- Max **5 MB** par fichier
- Max **50 MB** total

#### MIME Whitelist (double vérification)
```
image/jpeg, image/png, image/gif, image/webp
application/pdf
application/msword
application/vnd.openxmlformats-officedocument.wordprocessingml.document
```

Vérifié avec `mime_content_type($tmp_file)` (pas l'extension déclarée).

#### Extensions Whitelist
```
jpg, jpeg, png, gif, webp, pdf, doc, docx
```

### Email & Pièces Jointes

#### Structure de l'Email
```
From:       Verso Vet <consultations@verso-vet.com>
Reply-To:   {owner_email}
Attachments:
  - consultation.json (métadonnées structured)
  - photo_0.jpg      (pièce jointe client)
  - document_1.pdf   (pièce jointe client)
```

#### JSON Metadata (NEW v3.1.0)
```json
{
  "uuid": "verso-1715234567-a1b2c3d4",
  "files": [
    {
      "original_name": "photo.jpg",
      "stored_name": "photo_0.jpg",
      "mime_type": "image/jpeg",
      "size": 245120
    }
  ]
}
```

### Page Formulaire - Generation

#### Structure HTML
La fonction `verso_create_consultation_page()` génère la page `/demande-de-consultation/` en:
1. Créant une variable `$form_html` contenant du HTML pur (pas de Divi Builder)
2. Créant/mettant à jour une page WordPress avec slug `demande-de-consultation`
3. Utilisant `wp_insert_post()` ou `wp_update_post()` selon si la page existe

#### Layout
- **Hero header**: Titre centré + description
- **Deux colonnes**: 
  - Colonne gauche (2/3): Formulaire avec 5 sections numérotées
  - Colonne droite (1/3): Sidebar avec infos
- **Responsive**: Mono-colonne sur mobile (max-width: 768px)

#### Styling
- Couleurs: #1c2445 (primary), #e74c3c (accent red)
- CSS embarqué dans la chaîne HTML
- Classes préfixées `verso-` pour éviter les conflits
- **Pas de dépendance Divi Builder**: fonctionne avec n'importe quel thème

#### Sections du Formulaire
1. **Propriétaire**: Nom, Prénom, Email, Téléphone, Adresse (tous requis)
2. **Vétérinaire** (optionnel): Clinique, Nom, Email, Téléphone
3. **Animal** (requis): Nom, Espèce, Race
4. **Motif** (requis): Textarea description
5. **Pièces jointes** (optionnel): Upload de fichiers, max 10, 5 MB chacun

### Intégration ERP (Feature B)

#### Flow
1. **IMAP Monitor** (`consultation-requests`) extrait les pièces jointes email
2. **Stockage local** → SQLite DB + `{uuid}/` dans consultation-requests
3. **Dashboard → Integrate button** → Upload vers `erp-connector`
4. **ERP Storage** → Animal record + HMAC-signed upload

**Note**: consultation-requests doit être mis à jour pour extraire les pièces jointes IMAP (hors scope plugin).

---

## Bonnes Pratiques du Plugin

### Code PHP

#### ✅ Sécurité
- `sanitize_text_field()`, `sanitize_email()`, `sanitize_textarea_field()` obligatoires
- `$wpdb->prepare()` ou `$wpdb->insert()` pour DB (pas de concaténation SQL)
- Nonce verification souhaitée (mais public form acceptée sans nonce)
- Validate UUID format avant toute opération FS

#### ✅ Erreurs
- `wp_send_json_error()` pour erreurs utilisateur
- `wp_send_json_success()` pour succès
- Messages francophones

#### ✅ Files
- Utiliser `wp_upload_dir()` pour chemins
- `move_uploaded_file()` pour déplacer depuis tmp
- `realpath()` + vérification de confinement avant suppression
- Cleanup systématique (succès ET échec)

### Code JavaScript

#### ✅ Validation Client
- Champs requis marqués `required`
- Email validé avec pattern HTML5
- Vet fields: all-or-nothing (JS lines 93-100)
- Fichiers: validation taille + MIME (côté JS)

#### ✅ AJAX
- FormData avec `processData: false, contentType: false` pour multipart
- Timeout 60s
- Gestion des erreurs 4xx/5xx

#### ✅ UX
- Bouton soumission disabled pendant POST
- Message de succès avec UUID
- Auto-fade des messages après 3s

---

## Workflow Session (Développement)

### Au Démarrage
1. Lire ce fichier (CLAUDE.md)
2. Lire `/opt/onyx/forge/CLAUDE.md` (règles Forge globales)
3. Lire `ARCHITECTURE.md` (structure du plugin)
4. Lire `TODO.md` (tâches en cours)
5. Lire `API.md` (endpoints/format)
6. Lire `manifest.json` (configuration)

### Avant Chaque Commit
```bash
# 1. Code quality (WordPress standards)
php -l verso-consultation-plugin.php    # Syntax check

# 2. Security review
grep -n "sanitize\|wp_verify_nonce\|realpath" verso-consultation-plugin.php
grep -n "sql\|\$wpdb" verso-consultation-plugin.php  # Vérifier prepared statements

# 3. Git
git status
git diff                                 # Review changes

# 4. Deployment
./deploy-form.sh  (manual, requires OVH_SSH_PASS)
```

---

## Déploiement

### Prérequis
```bash
export OVH_SSH_PASS='votre_mot_de_passe'
# (Credentials dans Vault: ovh_ssh_password — à supprimer selon CLAUDE.md root)
```

### Script `deploy-form.sh`
- **Sûr**: scp only, pas de commandes distantes
- **Cible**: `/homez.1657/versovx/www/wp-content/plugins/verso-consultation-plugin/`
- **Fichiers**: verso-consultation-plugin.php, js/form.js, css/style.css
- **Activation**: Manuel via WordPress admin (ou WP-CLI)

### Étapes Post-Deploy
1. WordPress admin → Plugins → Activate verso-consultation-plugin
2. Créer page `/demande-de-consultation/`
3. Tester formulaire via navigateur
4. Vérifier email reçu à `consultations@verso-vet.com`
5. Vérifier consultation-requests IMAP monitor (cron toutes les 60s)
6. Vérifier dans dashboard consultation-requests

---

## Références

| Doc | Usage | Localisation |
|-----|-------|-------------|
| **ARCHITECTURE.md** | Structure + modules sécurité | `/home/onyx/projects/skills/verso-consultation-plugin/ARCHITECTURE.md` |
| **API.md** | Endpoints, formats, exemples | `API.md` |
| **TODO.md** | Tâches, bugs, enhancements | `TODO.md` |
| **Forge Globales** | Règles Forge 18 phases | `/opt/onyx/forge/CLAUDE.md` |
| **Vault** | Credentials (IMAP, ERP secrets) | `curl http://10.0.0.44:8050/vault/...` |
| **consultation-requests** | IMAP + ERP integration | `http://10.0.0.44:8092` (port 8092) |
| **erp-connector** | VetoPartner ERP | `http://10.0.0.44:8101` (port 8101) |
