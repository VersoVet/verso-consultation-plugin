# verso-consultation-plugin - Architecture & Security

**Version**: 3.1.0  
**Status**: 🟢 PRODUCTION  
**Last Updated**: 2026-05-09

---

## Vue d'ensemble

```
Client Browser
    ↓
Form (jQuery AJAX) ← form.js + style.css
    ↓
verso_submit_consultation action
    ↓
verso_handle_consultation_ajax()
├── Validate inputs (sanitization)
├── Validate file uploads (MIME, size)
├── Store files in verso-consultations/{uuid}/
├── Build consultation.json + metadata
├── wp_mail() with attachments (consultation.json + files)
├── Store in WordPress DB (backup)
├── verso_safe_delete_consultation_dir() cleanup
└── Return JSON response
    ↓
consultation-requests (IMAP monitor)
    ↓
SQLite DB + extracted files
    ↓
Dashboard UI (delete + integrate)
    ↓
erp-connector (VetoPartner)
```

---

## Modules Fonctionnels

### 1. Form & Frontend

**Fichiers**: `js/form.js`, `css/style.css`

#### Validation Côté Client (JS)
- Required fields: `owner_nom`, `owner_prenom`, `owner_email`, `owner_telephone`, `animal_nom`, `animal_espece`, `motif`
- Email format: HTML5 pattern validation
- Vet fields: If ANY filled, ALL required (lines 93-100)
- File uploads: Count (≤5), size preview, total warning (>50 MB)
- Submit button: Disabled during POST

#### UX Patterns
- `.was-validated` class for Bootstrap validation styling
- Loading spinner on submit
- Success message with UUID (auto-fade 3s)
- Error messages with specific details
- Scroll animation on form submission

---

### 2. AJAX Handler

**Fichier**: `verso-consultation-plugin.php` (lines 17-107)

#### Endpoint
```
POST /wp-admin/admin-ajax.php?action=verso_submit_consultation
Content-Type: multipart/form-data
```

#### Input Sanitization (lines 19-34)
```php
sanitize_text_field()        → nom, prenom, telephone, address
sanitize_email()             → owner_email, vet_email
sanitize_textarea_field()    → motif, owner_address
```

#### Validation (lines 37-43)
- Required fields check
- Email format validation with `is_email()`
- File count/size checks (new v3.1)
- MIME type validation (new v3.1)

#### Request/Response

**Request** (JSON form data):
```json
{
  "action": "verso_submit_consultation",
  "owner_nom": "Dupont",
  "owner_prenom": "Jean",
  "owner_email": "jean@example.com",
  "owner_telephone": "+33612345678",
  "owner_address": "123 Rue de Paris",
  "animal_nom": "Rex",
  "animal_espece": "Chien",
  "animal_race": "Labrador",
  "motif": "Boiterie depuis 3 jours",
  "vet_nom": "Smith",
  "vet_prenom": "Dr.",
  "vet_clinique": "Clinique Paris",
  "vet_email": "vet@clinic.fr",
  "vet_telephone": "+33145678901"
}
```

**Response** (success):
```json
{
  "success": true,
  "data": {
    "message": "Demande envoyée avec succès",
    "uuid": "verso-1715234567-a1b2c3d4"
  }
}
```

**Response** (error):
```json
{
  "success": false,
  "data": {
    "message": "Veuillez remplir tous les champs obligatoires"
  }
}
```

---

### 3. File Upload & Storage (NEW v3.1.0)

#### Architecture Sécurisée

```
wp-content/uploads/verso-consultations/
├── .htaccess                    ← deny from all
├── index.php                    ← Silence stub
└── verso-1715234567-a1b2c3d4/   ← UUID subdirectory
    ├── photo_0.jpg
    ├── radio_1.pdf
    └── (cleanup after email send)
```

#### Constante Source de Vérité
```php
define('VERSO_UPLOAD_SUBDIR', 'verso-consultations');
```

#### Initialisation du Répertoire
```php
function verso_init_upload_dir(): void {
    $upload_dir = wp_upload_dir();
    $verso_dir = $upload_dir['basedir'] . '/' . VERSO_UPLOAD_SUBDIR;
    
    if (!is_dir($verso_dir)) {
        wp_mkdir_p($verso_dir);
        
        // .htaccess: Deny HTTP access
        file_put_contents(
            $verso_dir . '/.htaccess',
            "deny from all\nOptions -Indexes\n"
        );
        
        // index.php: Silence
        file_put_contents(
            $verso_dir . '/index.php',
            '<?php // Silence is golden.'
        );
    }
}
```

**Appelée dans**:
- `register_activation_hook()` (activation du plugin)
- Lazy-init dans `verso_handle_consultation_ajax()` avant le premier upload

#### Validation des Fichiers Uploadés

**Limites**:
```php
const VERSO_MAX_FILES = 5;           // max fichiers par soumission
const VERSO_MAX_FILE_SIZE = 10485760; // 10 MB per file
const VERSO_MAX_TOTAL_SIZE = 31457280; // 30 MB total
```

**MIME Whitelist** (validation stricte):
```php
$allowed_mimes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

// Vérifié avec mime_content_type($tmp_file) — pas déclaré par client
```

**Extensions Whitelist** (double vérification):
```php
$allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
```

#### Stockage Sécurisé

```php
function verso_sanitize_filename(string $filename): string {
    // Extract extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Validate extension
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
    if (!in_array($ext, $allowed_exts)) {
        $ext = 'bin';
    }
    
    // Sanitize base name (remove special chars)
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $base);
    $base = substr($base, 0, 50); // limit length
    
    return $base . '.' . $ext;
}
```

**Déplacement**:
```php
$verso_dir = $upload_dir['basedir'] . '/' . VERSO_UPLOAD_SUBDIR . '/' . $uuid;
wp_mkdir_p($verso_dir);

foreach ($_FILES['fichiers']['tmp_name'] as $idx => $tmp) {
    $safe_name = verso_sanitize_filename($_FILES['fichiers']['name'][$idx]);
    
    // Avoid collisions with indexed names
    $safe_name = pathinfo($safe_name, PATHINFO_FILENAME) . '_' . $idx 
               . '.' . pathinfo($safe_name, PATHINFO_EXTENSION);
    
    if (move_uploaded_file($tmp, $verso_dir . '/' . $safe_name)) {
        $uploaded_files[] = [
            'original_name' => $_FILES['fichiers']['name'][$idx],
            'stored_name' => $safe_name,
            'mime_type' => mime_content_type($verso_dir . '/' . $safe_name),
            'size' => filesize($verso_dir . '/' . $safe_name),
        ];
    }
}
```

#### Suppression Sécurisée (ANTI-TRAVERSAL)

```php
function verso_safe_delete_consultation_dir(string $uuid): void {
    // ÉTAPE 1: Valider le format UUID (regex stricte)
    // Prévient injection de ../../../etc/passwd via UUID
    if (!preg_match('/^verso-\d{10}-[a-f0-9]{8}$/', $uuid)) {
        return; // Refus absolu
    }
    
    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . VERSO_UPLOAD_SUBDIR;
    $target_dir = $base_dir . DIRECTORY_SEPARATOR . $uuid;
    
    // ÉTAPE 2: Résoudre les chemins réels
    // realpath() résout les symlinks et supprime les ..
    $real_base = realpath($base_dir);
    $real_target = realpath($target_dir);
    
    if ($real_base === false || $real_target === false) {
        return; // Chemin inexistant = pas d'action
    }
    
    // ÉTAPE 3: Vérifier confinement strict
    // S'assure que $real_target commence par $real_base/
    if (strpos($real_target, $real_base . DIRECTORY_SEPARATOR) !== 0) {
        return; // Tentative de traversée détectée = refus absolu
    }
    
    // ÉTAPE 4: Supprimer uniquement fichiers
    // Pas de rmdir -r récursif non contrôlé
    $files = glob($real_target . DIRECTORY_SEPARATOR . '*');
    if ($files !== false) {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    @rmdir($real_target); // Ne supprime que dir vide
}
```

**Test de sécurité** (injection UUID):
```php
// Cette tentative est refusée :
verso_safe_delete_consultation_dir('verso-1715234567-a1b2c3d4/../../etc/passwd');
// → Regex UUID fail → return sans action
```

---

### 4. Email Composition & Sending

**Fichier**: `verso-consultation-plugin.php` (lines 81-89)

#### JSON Metadata (NEW v3.1.0)

```php
$consultation_data = json_encode([
    'uuid' => $uuid,
    'submitted_at' => current_time('c'),
    'owner_nom' => $owner_nom,
    'owner_email' => $owner_email,
    'animal_nom' => $animal_nom,
    'animal_espece' => $animal_espece,
    'motif' => $motif,
    'files' => [                           // NEW
        [
            'original_name' => 'photo.jpg',
            'stored_name' => 'photo_0.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 245120
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
```

#### Email Headers
```php
$headers = [
    'Content-Type: text/plain; charset=UTF-8',
    'From: Verso Vet <consultations@verso-vet.com>', // OVH SPF/DKIM requirement
    'Reply-To: ' . sanitize_email($owner_email),     // Client reachable
];
```

#### Pièces Jointes (NEW v3.1.0)

```php
$attachments = [$json_path]; // consultation.json toujours présent

// Ajouter fichiers uploadés
foreach ($uploaded_files as $file_info) {
    $file_path = $verso_dir . '/' . $file_info['stored_name'];
    if (is_file($file_path)) {
        $attachments[] = $file_path;
    }
}

// wp_mail() envoie tous les attachments
$result = wp_mail($to, $subject, $email_body, $headers, $attachments);
```

**Email Reçu**:
```
From: Verso Vet <consultations@verso-vet.com>
Reply-To: jean@example.com
To: consultations@verso-vet.com
Subject: [Verso Vet] Demande verso-1715234567-a1b2c3d4 - Rex (Chien)

Nouvelle demande de consultation

Référence : verso-1715234567-a1b2c3d4
Date       : 2026-05-09 14:30:00

─── PROPRIÉTAIRE ──────────────────────────
Nom    : Jean Dupont
Email  : jean@example.com
Tél    : +33612345678
Adresse: 123 Rue de Paris, 75001 Paris

─── ANIMAL ────────────────────────────────
Nom    : Rex
Espèce : Chien
Race   : Labrador

─── MOTIF DE CONSULTATION ─────────────────
Boiterie antérieure droite depuis 3 jours

───────────────────────────────────────────
Pièces jointes:
  - consultation.json
  - photo_0.jpg
  - document_1.pdf
```

---

### 5. Database Storage (Backup)

**Fichier**: `verso-consultation-plugin.php` (lines 109-164)

#### Schema
```sql
CREATE TABLE IF NOT EXISTS wp_verso_consultations (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    uuid varchar(50) NOT NULL UNIQUE,
    owner_nom varchar(100),
    owner_prenom varchar(100),
    owner_email varchar(100),
    owner_telephone varchar(20),
    owner_address longtext,
    animal_nom varchar(100),
    animal_espece varchar(100),
    animal_race varchar(100),
    motif longtext,
    vet_nom varchar(100),
    vet_prenom varchar(100),
    vet_clinique varchar(200),
    vet_email varchar(100),
    vet_telephone varchar(20),
    status varchar(50) DEFAULT 'new',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uuid (uuid)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Rôle**: Backup local (non actif pour tracking). La source de vérité est dans `consultation-requests` SQLite.

**Insertion sécurisée**:
```php
$wpdb->insert($table_name, [
    'uuid' => $uuid,
    'owner_nom' => $owner_nom,
    // ...
], ['%s', '%s', // ...]);
```

Utilise prepared statements — safe contre SQL injection.

---

### 6. Integration avec consultation-requests (Feature B)

#### Data Flow
```
1. Form submission + email dispatch
   ↓
2. consultation-requests IMAP monitor (cron every 60s)
   ├── Connect to consultations@verso-vet.com
   ├── Extract consultation.json attachment
   ├── Extract files attachments (NEW v3.1.0 requires update)
   ├── Parse JSON into SQLite
   └── Mark email as read
   ↓
3. Dashboard displays consultation
   ├── View button
   ├── Delete button (soft delete + IMAP removal)
   └── Integrate button
   ↓
4. Integrate with ERP
   ├── POST to erp-connector /consultations
   ├── Upload files with HMAC-SHA256 signature
   └── Update status to "integrated"
```

#### JSON Metadata pour consultation-requests
```json
{
  "uuid": "verso-1715234567-a1b2c3d4",
  "submitted_at": "2026-05-09T14:30:00+00:00",
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

**Note**: `consultation-requests` doit être mis à jour pour extraire les pièces jointes IMAP au-delà de `consultation.json` (implémentation future, hors scope du plugin).

---

## Sécurité

### Matrice de Risques & Mitigations

| Risque | Vecteur | Mitigation |
|--------|---------|-----------|
| **Path Traversal** | UUID in cleanup | regex validation + realpath() + strpos() confinement |
| **MIME Spoofing** | Extension-only validation | mime_content_type() sur fichier réel |
| **Extension Bypass** | Unknown ext (.php, .sh) | Double whitelist (MIME + ext) |
| **Fichiers Orphelins** | Email send failure | Cleanup systématique (try/finally) |
| **HTTP Direct Access** | uploads/ public | .htaccess deny + index.php stub |
| **Débordement Disque** | Large uploads | 5 fichiers max / 10 MB / 30 MB total |
| **SQL Injection** | DB insert | `$wpdb->insert()` prepared statements |
| **XSS** | User input in response | JSON response, no HTML. JS escapeHtml() |
| **CSRF** | No nonce | Public form (acceptable, low risk) |

---

## Testing Checklist

### Soumission Normale
- [ ] Soumettre sans fichiers → réponse succès identique v3.0
- [ ] Soumettre avec 1 image JPEG → email reçu avec pièce jointe
- [ ] Vérifier consultation.json contient section `files`

### Validation Upload
- [ ] 6 fichiers → erreur "Trop de fichiers (maximum 5)"
- [ ] 1 fichier 15 MB → erreur "Fichier trop volumineux (maximum 10 MB)"
- [ ] Fichier .php → erreur "Type de fichier non autorisé"
- [ ] Total > 30 MB → erreur "Taille totale excessive (maximum 30 MB)"

### Sécurité
- [ ] Répertoire `verso-consultations/{uuid}/` supprimé après envoi
- [ ] `.htaccess` bloque accès HTTP direct (GET /wp-content/uploads/verso-consultations/ → 403)
- [ ] UUID `verso-1715234567-a1b2c3d4/../../etc/passwd` → refusé cleanup (test interne)
- [ ] Fichiers orphelins (email fail) → nettoyés

### ERP Integration
- [ ] IMAP monitor récupère l'email avec attachments
- [ ] consultation-requests extrait consultation.json + fichiers
- [ ] Dashboard affiche "Integrate" button
- [ ] Click integrate → fichiers uploadés vers erp-connector

---

## Dépendances Externes

| Service | Port | Rôle |
|---------|------|------|
| verso-vet.com WordPress | 80/443 | Plugin host |
| consultations@verso-vet.com | IMAP | Email inbox |
| consultation-requests | 8092 | IMAP monitoring + ERP integration |
| erp-connector | 8101 | VetoPartner ERP |
| Vault | 8050 | IMAP credentials (future) |

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| **3.1.0** | 2026-05-09 | File upload support + ERP metadata + security hardening |
| 3.0.0 | 2026-05-09 | Full IMAP integration + safe deployment |
| 2.0.0 | 2026-05-05 | Email attachments via PHP wp_mail() |
| 1.0.0 | 2026-04-20 | Initial WordPress plugin |
