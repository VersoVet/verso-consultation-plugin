# verso-consultation-plugin - API Documentation

## Endpoints WordPress

### Form Submission (AJAX)
```
POST /wp-admin/admin-ajax.php
Content-Type: application/x-www-form-urlencoded
```

**Parameters:**
```
verso_action=submit_consultation
owner_nom=string (required)
owner_prenom=string (required)
owner_email=email (required)
owner_telephone=string (required)
owner_address=text (required)
animal_nom=string (required)
animal_espece=string (required)
animal_race=string (optional)
vet_nom=string (optional)
vet_prenom=string (optional)
vet_clinique=string (optional)
vet_email=email (optional)
vet_telephone=string (optional)
motif=text (required)
```

**Response:**
- HTTP 200: OK (no content returned)
- HTTP 4xx/5xx: Error (check WordPress debug log)

**Example:**
```bash
curl -X POST https://verso-vet.com/wp-admin/admin-ajax.php \
  -F "verso_action=submit_consultation" \
  -F "owner_nom=Dupont" \
  -F "owner_prenom=Jean" \
  -F "owner_email=jean@example.com" \
  -F "owner_telephone=+33612345678" \
  -F "owner_address=123 Rue de Paris" \
  -F "animal_nom=Rex" \
  -F "animal_espece=Chien" \
  -F "animal_race=Labrador" \
  -F "motif=Boiterie antérieure"
```

## Webhook Email

### Format
```
From: {owner_email}
To: consultations@verso-vet.com
Subject: VERSO_WEBHOOK UUID:{uuid} TYPE:consultation ANIMAL:{animal_nom}
Content-Type: text/plain; charset=UTF-8
```

### Body Structure
```
Nouvelle demande de consultation reçue

═══════════════════════════════════════════
PROPRIÉTAIRE/CONTACT
═══════════════════════════════════════════
Nom: {owner_nom}
Prénom: {owner_prenom}
Email: {owner_email}
Téléphone: {owner_telephone}
Adresse: {owner_address}

[OPTIONAL: Vétérinaire section]

═══════════════════════════════════════════
PATIENT ANIMAL
═══════════════════════════════════════════
Nom: {animal_nom}
Espèce: {animal_espece}
Race: {animal_race}

═══════════════════════════════════════════
MOTIF DE CONSULTATION
═══════════════════════════════════════════
{motif}

═══════════════════════════════════════════
MÉTADONNÉES
═══════════════════════════════════════════
UUID: {uuid}
Date: {current_time}
Site: {site_url}
```

## WordPress Database

### Table: wp_verso_consultations
```sql
CREATE TABLE wp_verso_consultations (
  id mediumint(9) PRIMARY KEY AUTO_INCREMENT,
  uuid varchar(50) UNIQUE,
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
  created_at datetime DEFAULT CURRENT_TIMESTAMP
)
```

## Integration with consultation-requests Skill

The email webhook is consumed by the consultation-requests skill:
1. IMAP monitor checks consultations@verso-vet.com
2. Parses VERSO_WEBHOOK emails
3. Extracts UUID from subject
4. Stores in consultation-requests SQLite database
5. Dashboard displays data from consultation-requests DB

## Testing

### 1. Form Submission
```bash
# Visit form page
https://verso-vet.com/demande-de-consultation/

# Fill and submit form
# Should see success message
```

### 2. Email Delivery
```bash
# Check if email arrived at consultations@verso-vet.com
# Should have subject: VERSO_WEBHOOK UUID:... TYPE:consultation ANIMAL:...
```

### 3. Dashboard Sync
```bash
# Wait ~1 minute for IMAP monitor to sync
# Visit http://10.0.0.44:8092/dashboard
# Should show 1 new "Received" consultation
```

## Error Handling

### Form Validation
- Empty required fields → silently return (no error shown)
- Invalid email → silently return
- Missing verso_action → silently return

### Email Sending
- If wp_mail() fails → no database insert
- Check WordPress debug log: `/var/www/verso-vet.com/wp-content/debug.log`

### Database Issues
- Table created automatically on first submission
- Uses WordPress dbDelta() for migrations
