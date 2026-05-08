# verso-consultation-plugin - API Documentation

## AJAX Form Submission Endpoint

```
POST /wp-admin/admin-ajax.php
Content-Type: multipart/form-data (when files included)
Content-Type: application/x-www-form-urlencoded (form data only)
```

### Required Parameter
```
action=verso_submit_consultation
```

### Form Parameters

**Owner/Contact Information (Required):**
```
owner_nom          string     Owner last name
owner_prenom       string     Owner first name
owner_email        string     Owner email address (validated)
owner_telephone    string     Owner phone number
owner_address      string     Owner physical address
```

**Veterinarian (Optional):**
```
vet_nom            string     Vet last name (required if any vet field filled)
vet_prenom         string     Vet first name (required if any vet field filled)
vet_clinique       string     Clinic name (required if any vet field filled)
vet_email          string     Vet email (required if any vet field filled)
vet_telephone      string     Vet phone (required if any vet field filled)
```

**Animal/Patient (Required):**
```
animal_nom         string     Animal name (required)
animal_espece      string     Species (required)
animal_race        string     Breed (optional)
```

**Consultation (Required):**
```
motif              string     Reason for consultation (required)
```

### Responses

**Success (200 OK):**
```json
{
  "success": true,
  "data": {
    "message": "Demande envoyée avec succès",
    "uuid": "verso-1715234567-a1b2c3d4"
  }
}
```

**Validation Error (200 OK with error flag):**
```json
{
  "success": false,
  "data": {
    "message": "Veuillez remplir tous les champs obligatoires"
  }
}
```

**Invalid Email (200 OK with error):**
```json
{
  "success": false,
  "data": {
    "message": "Email propriétaire invalide"
  }
}
```

**Vet Fields Partially Filled (200 OK with error):**
```json
{
  "success": false,
  "data": {
    "message": "Si vous remplissez les infos du vétérinaire, complétez tous les champs requis"
  }
}
```

### Example cURL Request

```bash
curl -s -X POST "https://verso-vet.com/wp-admin/admin-ajax.php" \
  -d "action=verso_submit_consultation" \
  -d "owner_nom=Dupont" \
  -d "owner_prenom=Jean" \
  -d "owner_email=jean@example.com" \
  -d "owner_telephone=+33612345678" \
  -d "owner_address=123 Rue de Paris, 75001 Paris" \
  -d "animal_nom=Rex" \
  -d "animal_espece=Chien" \
  -d "animal_race=Labrador" \
  -d "motif=Boiterie antérieure droite depuis 3 jours" | jq .
```

## Email Webhook Format

### Email Details
```
From:    {owner_email}
To:      consultations@verso-vet.com
Subject: [Verso Vet] Demande {uuid} - {animal_nom} ({animal_espece})
```

### Email Body (Plain Text)
```
Nouvelle demande de consultation

Référence : verso-1715234567-a1b2c3d4
Date       : 2026-05-08 14:30:00

─── PROPRIÉTAIRE ──────────────────────────
Nom    : Jean Dupont
Email  : jean@example.com
Tél    : +33612345678
Adresse: 123 Rue de Paris, 75001 Paris

─── ANIMAL ────────────────────────────────
Nom    : Rex
Espèce : Chien
Race   : Labrador

─── VÉTÉRINAIRE RÉFÉRANT ──────────────────
Dr. Smith — Clinique Vétérinaire Paris
Email  : doctor@clinic.fr
Tél    : +33145678901

─── MOTIF DE CONSULTATION ─────────────────
Boiterie antérieure droite depuis 3 jours

───────────────────────────────────────────
Pièce jointe : consultation.json
(données complètes pour traitement automatique)
```

### Email Attachment: consultation.json
```json
{
  "uuid": "verso-1715234567-a1b2c3d4",
  "submitted_at": "2026-05-08T14:30:00+00:00",
  "owner_nom": "Dupont",
  "owner_prenom": "Jean",
  "owner_email": "jean@example.com",
  "owner_telephone": "+33612345678",
  "owner_address": "123 Rue de Paris, 75001 Paris",
  "vet_nom": "Smith",
  "vet_prenom": "Dr.",
  "vet_clinique": "Clinique Vétérinaire Paris",
  "vet_email": "doctor@clinic.fr",
  "vet_telephone": "+33145678901",
  "animal_nom": "Rex",
  "animal_espece": "Chien",
  "animal_race": "Labrador",
  "motif": "Boiterie antérieure droite depuis 3 jours"
}
```

## Data Flow

```
1. Form Submission (Browser)
   ↓
2. jQuery AJAX → POST /wp-admin/admin-ajax.php?action=verso_submit_consultation
   ↓
3. verso_handle_consultation_ajax() Handler
   - Sanitize all inputs
   - Validate required fields
   - Generate UUID
   - Build email body
   - Create consultation.json
   - ↓
4. wp_mail() Sends Email
   - Recipient: consultations@verso-vet.com
   - Subject: [Verso Vet] Demande {uuid} - {animal}
   - Body: Formatted text (plain text)
   - Attachment: consultation.json (binary)
   - ↓
5. Email Stored in Database
   - Table: wp_verso_consultations
   - Backup of form data (not actively used)
   - ↓
6. Return JSON Response
   - success: true/false
   - data.message: User-facing message
   - data.uuid: Unique identifier
   ↓
7. IMAP Monitor (consultation-requests skill)
   - Polls consultations@verso-vet.com inbox
   - Searches for emails with subject: "[Verso Vet] Demande"
   - Extracts and parses consultation.json attachment
   - Stores in SQLite database
   - Marks email as read
   ↓
8. Dashboard (http://10.0.0.44:8092)
   - Displays consultation with status: "Received"
   - Allows status changes
   - Provides consultation details view
```

## WordPress Database Storage

### Table: wp_verso_consultations
**Purpose**: Backup storage (not actively used for tracking)

```sql
CREATE TABLE wp_verso_consultations (
  id mediumint(9) PRIMARY KEY AUTO_INCREMENT,
  uuid varchar(50) UNIQUE NOT NULL,
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
)
```

**Note**: Primary consultation tracking happens in consultation-requests skill's SQLite database, not here.

## Validation Rules

### Input Validation
- **owner_nom**: Required, text field, max 100 chars
- **owner_prenom**: Required, text field, max 100 chars
- **owner_email**: Required, must be valid email format
- **owner_telephone**: Required, text field, max 20 chars
- **owner_address**: Text area, max 65535 chars
- **animal_nom**: Required, text field, max 100 chars
- **animal_espece**: Required, text field, max 100 chars
- **animal_race**: Optional, text field, max 100 chars
- **motif**: Required, text area, max 65535 chars

### Conditional Validation
- If ANY vet field is provided, ALL vet fields must be provided
- Email addresses are validated with WordPress `is_email()` function
- Text fields are sanitized with `sanitize_text_field()`
- Email fields are sanitized with `sanitize_email()`
- Text areas are sanitized with `sanitize_textarea_field()`

## Testing Endpoints

### 1. Direct AJAX Test
```bash
# Test with minimal required fields
curl -s -X POST "https://verso-vet.com/wp-admin/admin-ajax.php" \
  -d "action=verso_submit_consultation" \
  -d "owner_nom=Dupont" \
  -d "owner_prenom=Jean" \
  -d "owner_email=test@verso-vet.com" \
  -d "owner_telephone=0612345678" \
  -d "animal_nom=Rex" \
  -d "animal_espece=Chien" \
  -d "motif=Test submission" | jq .
```

Expected response: Valid JSON with success=true and UUID

### 2. Error: Missing Required Field
```bash
# Missing owner_nom
curl -s -X POST "https://verso-vet.com/wp-admin/admin-ajax.php" \
  -d "action=verso_submit_consultation" \
  -d "owner_prenom=Jean" \
  -d "owner_email=test@verso-vet.com" \
  -d "animal_nom=Rex" \
  -d "animal_espece=Chien" \
  -d "motif=Test" | jq .
```

Expected response: success=false with error message

### 3. Error: Invalid Email
```bash
# Invalid email format
curl -s -X POST "https://verso-vet.com/wp-admin/admin-ajax.php" \
  -d "action=verso_submit_consultation" \
  -d "owner_nom=Dupont" \
  -d "owner_prenom=Jean" \
  -d "owner_email=not-an-email" \
  -d "animal_nom=Rex" \
  -d "animal_espece=Chien" \
  -d "motif=Test" | jq .
```

Expected response: success=false with "Email invalide"

### 4. Error: Partial Vet Information
```bash
# Only vet_nom provided, but not other vet fields
curl -s -X POST "https://verso-vet.com/wp-admin/admin-ajax.php" \
  -d "action=verso_submit_consultation" \
  -d "owner_nom=Dupont" \
  -d "owner_prenom=Jean" \
  -d "owner_email=test@verso-vet.com" \
  -d "animal_nom=Rex" \
  -d "animal_espece=Chien" \
  -d "motif=Test" \
  -d "vet_nom=Smith" | jq .
```

Expected response: success=false with "Si vous remplissez les infos du vétérinaire..."

## Debugging

### Check WordPress Debug Log
```bash
tail -f /var/www/verso-vet.com/wp-content/debug.log
```

### Verify AJAX Handler is Registered
```bash
# Check if action hooks are present
grep -r "wp_ajax_verso_submit_consultation" /var/www/verso-vet.com/wp-content/plugins/verso-consultation-plugin/
```

### Monitor Email Queue
```bash
# Check if email was sent
ls -la /var/www/verso-vet.com/wp-content/uploads/verso_*.json
```

## Integration with consultation-requests Skill

The consultation-requests skill on http://10.0.0.44:8092 consumes these emails via:

1. **IMAP Monitor** (`src/core/imap_monitor.py`)
   - Connects to consultations@verso-vet.com
   - Searches for unread emails with subject: "[Verso Vet] Demande"
   - Extracts consultation.json attachment
   - Calls `store_consultation_from_json()`

2. **Storage Service** (`src/modules/consultations/service.py`)
   - Parses JSON attachment
   - Creates consultation record with status: "received"
   - Stores in SQLite: `/opt/onyx/skills/consultation-requests/data/consultations.db`

3. **Dashboard Display**
   - Query endpoint: `http://10.0.0.44:8092/api/consultations`
   - Web UI: `http://10.0.0.44:8092/dashboard`
