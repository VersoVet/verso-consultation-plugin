# Verso Vet Consultation System - Deployment Summary

## 🎯 System Architecture

```
VERSO-VET.COM (WordPress)
    ↓
    [Consultation Form - verso-consultation-plugin.php]
    ↓ Validates form data
    ↓ Generates HMAC-SHA256 signature
    ↓ POST /wp-json/verso/v1/consultation
    ↓
ONYXSOMA (10.0.0.44)
    ├── OnyxVault (8050) - Secret storage
    │   ├── consultation_webhook_secret: 2ecb6ec8a5335ee21ec467d651b2254a
    │   └── consultation_file_secret: bf09e61dca95100d68ea36c75f35f7d2
    │
    └── Consultation-Requests Skill (8092)
        ├── POST /consultations/submit - Receives webhook
        ├── GET /consultations - List all consultations
        ├── GET /consultations/{id} - View specific consultation
        ├── GET /dashboard - Dashboard UI
        └── SQLite Database: /opt/onyx/skills/consultation-requests/data/
```

## ✅ Completed Components

### 1. Vault Configuration ✅
- `consultation_webhook_secret` - 32-char random string for HMAC signing
- `consultation_file_secret` - 32-char random string for secure file downloads

### 2. Consultation-Requests Skill (Port 8092) ✅
**Location:** `/opt/onyx/skills/consultation-requests/`
**Status:** Running and operational
**Git:** https://github.com/VersoVet/consultation-requests.git

**Features:**
- ✅ Webhook endpoint: `POST /consultations/submit` - Accepts consultation submissions with HMAC signature validation
- ✅ Database: SQLite storing all consultations with status tracking (pending/received/integrated/rejected)
- ✅ Async processing: Files download, emails sent, status updated
- ✅ Email notifications: HTML emails to consultations@verso-vet.com
- ✅ Flexible data model: Accepts both vet and owner submissions
  - Vet submission: Owner field is null, Vet info present
  - Owner submission: Owner field present, Vet field is null
- ✅ REST API for listing and viewing consultations

**Recent Updates (May 4, 2026):**
- Fixed `OwnerInfo` model to be optional (null when submitter is vet)
- Updated email notification builder to handle both vet and owner contacts
- Restarted skill at 14:52 UTC to apply changes

### 3. WordPress Plugin (verso-consultation-plugin) ✅
**Location:** `/home/onyx/projects/skills/verso-consultation-plugin/`
**Status:** Ready for deployment
**Git:** `master` branch (local repository)

**Files:**
- `verso-consultation-plugin.php` - Main plugin file with lifecycle hooks
- `includes/class-form-handler.php` - Form HTML rendering
- `includes/class-webhook-sender.php` - REST endpoint and webhook sending
- `includes/class-vault-client.php` - Vault secret retrieval
- `css/style.css` - VERSO brand styling (navy background, white card, responsive)
- `js/form.js` - Form validation and AJAX submission logic

**Features:**
- ✅ Responsive form (single column mobile, two columns desktop)
- ✅ VERSO brand colors and styling (navy #1C2445, teal #1783A7)
- ✅ Dynamic sections: Show vet/owner fields conditionally based on submitter type
- ✅ File upload support (PDF, JPG, PNG, TIFF, DICOM - max 50MB)
- ✅ Automatic Vault configuration on activation
- ✅ HMAC-SHA256 signature generation and sending
- ✅ Error handling and user feedback

## 🧪 Testing Results

### Test 1: Owner Submission (ID: 3)
```json
POST /consultations/submit
{
  "uuid": "consult-owner-1777906267319575362",
  "submitter_type": "owner",
  "owner": {"nom": "Martin", "prenom": "Marie", "email": "marie@test.com", "telephone": "0612345678"},
  "animal": {"nom": "Milu", "espece": "Chien", ...},
  "motif": "Boiterie antérieure chronique..."
}
```
**Result:** ✅ Success (ID: 3, Status: received)

### Test 2: Vet Submission (ID: 4)
```json
POST /consultations/submit
{
  "uuid": "test-vet-null-1777906375027909256",
  "submitter_type": "vet",
  "vet": {"nom": "Martin", "prenom": "Pierre", "clinique": "Clinique Vétérinaire Plus", ...},
  "owner": null,
  "animal": {"nom": "Filou", "espece": "Lapin", ...},
  "motif": "Paralysie des pattes arrière..."
}
```
**Result:** ✅ Success (ID: 4, Status: received)

### Test 3: HMAC Signature Validation
```
Secret: 2ecb6ec8a5335ee21ec467d651b2254a
Payload: JSON consultation request
Signature (SHA256): Auto-validated by skill
```
**Result:** ✅ Signature validation working

## 📋 Installation Steps for verso-vet.com

### Step 1: SSH to OVH Server
```bash
ssh user@verso-vet.com
cd /var/www/verso-vet.com/wp-content/plugins/
```

### Step 2: Deploy WordPress Plugin
```bash
# Copy plugin directory
scp -r /home/onyx/projects/skills/verso-consultation-plugin/ \
    user@verso-vet.com:/var/www/verso-vet.com/wp-content/plugins/
```

### Step 3: Configure WordPress
1. Go to WordPress Admin → Plugins
2. Activate "Verso Consultation Form" plugin
3. Go to Settings → Verso Consultation
4. Configure:
   - Vault URL: `http://10.0.0.44:8050`
   - Vault Token: `$ONYX_VAULT_TOKEN` (from local environment)
   - Skill URL: `http://10.0.0.44:8092`
5. Save settings

### Step 4: Verify Installation
1. Navigate to `/demande-consultation/` page
2. Form should display with VERSO styling
3. Submit test form
4. Check `/consultations` endpoint at skill to verify submission

## 🔄 Data Flow

```
1. User fills form on verso-vet.com/demande-consultation/
   ├── Select submitter type (vet or owner)
   ├── Fill in contact info
   ├── Fill animal data
   ├── Describe consultation motif
   └── Optional: Upload documents

2. Form validates client-side (JS)
   ├── Required fields check
   ├── File size validation
   └── Type validation

3. Form submits to WordPress REST endpoint
   POST /wp-json/verso/v1/consultation
   ├── Validate nonce (CSRF protection)
   ├── Get webhook secret from Vault
   ├── Generate HMAC-SHA256 signature
   ├── Build consultation request JSON
   ├── Save files to /wp-content/uploads/consultations/{uuid}/
   └── POST to skill webhook

4. Skill receives webhook
   POST /consultations/submit
   ├── Validate HMAC signature
   ├── Parse JSON payload
   ├── Store in SQLite database (status: pending)
   └── Schedule async processing

5. Async task processes submission
   ├── Update status to "received"
   ├── Download files from WordPress (if any)
   ├── Generate HTML email notification
   └── Send email to consultations@verso-vet.com

6. Dashboard shows submission
   GET /dashboard
   └── View all consultations, filter by status, integrate with ERP

```

## 🚀 Next Steps

### Phase 1: Deployment (Week 1)
- [ ] Deploy WordPress plugin to verso-vet.com
- [ ] Configure Vault token in WordPress settings
- [ ] Test form submission from live website
- [ ] Verify emails are being sent to consultations@verso-vet.com
- [ ] Verify files are being uploaded correctly

### Phase 2: ERP Integration (Week 2)
- [ ] Extend erp-connector with POST /clients endpoint
- [ ] Extend erp-connector with POST /animals endpoint
- [ ] Implement consultation integration in skill
- [ ] Test client creation in VetoPartner
- [ ] Test animal creation in VetoPartner

### Phase 3: Dashboard (Week 2-3)
- [ ] Develop dashboard UI for consultation management
- [ ] Implement search/filter for consultations
- [ ] Implement integration button for ERP submission
- [ ] Test ERP integration flow end-to-end

## 📞 Support Contacts

- **Skill Health:** `http://10.0.0.44:8092/health`
- **Vault Health:** `http://10.0.0.44:8050/vault/list`
- **Email Notifications:** consultations@verso-vet.com
- **Dashboard:** http://10.0.0.44:8092/dashboard

## 🔑 Secrets in Vault

| Key | Value | Status |
|-----|-------|--------|
| `consultation_webhook_secret` | 2ecb6ec8a5335ee21ec467d651b2254a | ✅ Active |
| `consultation_file_secret` | bf09e61dca95100d68ea36c75f35f7d2 | ✅ Active |

## 📊 Database Schema (SQLite)

```sql
CREATE TABLE consultations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT UNIQUE NOT NULL,
    submitted_at TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending',
    submitter_type TEXT,
    data_json TEXT NOT NULL,
    files_local TEXT,
    erp_client_id INTEGER,
    erp_animal_id INTEGER,
    erp_consult_id INTEGER,
    integrated_at TEXT,
    notes TEXT
);
```

**Statuses:**
- `pending` - Newly received, queued for processing
- `received` - Processed (files downloaded, email sent)
- `integrated` - Integrated into VetoPartner ERP
- `rejected` - Submission rejected

---

**Last Updated:** 2026-05-04 14:52 UTC
**System Ready For:** WordPress Plugin Deployment → ERP Integration → Dashboard UI
