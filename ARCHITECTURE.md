# verso-consultation-plugin - Architecture

## Overview

verso-consultation-plugin is a WordPress plugin that manages veterinary consultation form submissions. It provides:
1. **Frontend Form** - Divi-based consultation request form
2. **Backend Processing** - Email webhook generation
3. **Data Storage** - WordPress database persistence
4. **Integration** - Email-based webhook for consultation-requests skill

## Components

### 1. verso-consultation-plugin.php (Main Plugin)
**Purpose**: Core plugin logic
**Hooks**:
- `wp_footer` (priority 1) - Process form submissions
- `register_activation_hook` - Initialize database tables

**Key Functions**:
- `verso_check_form_submission()` - Main form handler
- `verso_store_consultation_in_db()` - Database persistence
- `verso_build_email_body()` - Email content formatting
- `verso_activate_plugin()` - Database initialization

**Flow**:
```
Request arrives with verso_action=submit_consultation
    ↓
verso_check_form_submission() hook triggered in wp_footer
    ↓
Sanitize all $_POST fields
    ↓
Validate required fields + email format
    ↓
Generate UUID: verso-{timestamp}-{random}
    ↓
Build email with VERSO_WEBHOOK subject
    ↓
Send email via wp_mail()
    ↓
If email sent: Store in wp_verso_consultations table
```

### 2. Includes/ - Utility Classes

#### class-form-handler.php
**Purpose**: Form validation and processing
**Methods**:
- `validate_fields()` - Check required fields
- `sanitize_input()` - Clean user inputs
- `generate_uuid()` - Create unique consultation ID

#### class-webhook-sender.php
**Purpose**: Email webhook generation
**Methods**:
- `format_email_subject()` - Create VERSO_WEBHOOK subject
- `format_email_body()` - Structured email body
- `send_webhook_email()` - Send via wp_mail()

#### class-file-handler.php
**Purpose**: File uploads (for future use)
**Methods**:
- `validate_file()` - Check file type/size
- `store_file()` - Save uploaded files
- `cleanup_files()` - Remove old files

#### class-vault-client.php
**Purpose**: Onyx Vault integration (optional)
**Methods**:
- `get_secret()` - Retrieve secret from Vault
- `validate_credentials()` - Check Vault access

### 3. Frontend

#### js/form.js
**Purpose**: Client-side form handling
**Features**:
- Form validation
- AJAX submission to wp-admin/admin-ajax.php
- Error/success message display
- Loading state management

#### css/style.css
**Purpose**: Form styling
**Features**:
- Responsive layout
- Themed colors (#1c2445, #58c1d7)
- Section badges (1-4)
- Form input styles

#### Page: /demande-de-consultation/
**Type**: WordPress Divi page
**Elements**:
- Hero section with image (VirginieB-74721280px-72dpi.jpg)
- Form embedded via HTML (not shortcode)
- 4-section layout: Propriétaire, Vétérinaire, Animal, Motif

### 4. Templates/

#### template-hero-image.txt
**Purpose**: Divi hero template for page creation
**Variables**:
- {{TITLE}} - Page title
- {{SUBTITLE}} - Subtitle
- {{IMAGE_URL}} - Hero image URL
- {{CONTENT}} - Page content (form)

### 5. Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    verso-vet.com                            │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐   │
│  │ /demande-de-consultation/ (Page 2348)               │   │
│  │ ├─ Hero: VirginieB image + overlay                  │   │
│  │ └─ Form: HTML (4 sections)                          │   │
│  │    └─ Submit: verso_action=submit_consultation      │   │
│  └─────────────────────────────────────────────────────┘   │
│           │ POST to /wp-admin/admin-ajax.php                │
│           ↓                                                 │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ verso-consultation-plugin.php                       │   │
│  │ ├─ verso_check_form_submission() [wp_footer hook]  │   │
│  │ ├─ Validate & sanitize                             │   │
│  │ ├─ Generate UUID                                    │   │
│  │ ├─ Build email (VERSO_WEBHOOK subject)             │   │
│  │ ├─ Send email → consultations@verso-vet.com        │   │
│  │ └─ Store in wp_verso_consultations                 │   │
│  └─────────────────────────────────────────────────────┘   │
│           │ Email with webhook subject                      │
│           ↓                                                 │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ WordPress Database (wp_verso_consultations)         │   │
│  │ [Backup storage, optional]                          │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
           │ Email webhook (VERSO_WEBHOOK subject)
           ↓ consultations@verso-vet.com mailbox
┌─────────────────────────────────────────────────────────────┐
│             consultation-requests Skill                     │
│             (10.0.0.44:8092)                               │
├─────────────────────────────────────────────────────────────┤
│  IMAP Monitor (every 1 minute)                             │
│  └─ Query INBOX for UNSEEN VERSO_WEBHOOK emails           │
│  └─ Extract UUID from subject                             │
│  └─ Parse email body                                       │
│  └─ Store in SQLite: consultations.db                     │
│           │                                                │
│           ↓                                                │
│  Dashboard (http://10.0.0.44:8092/dashboard)             │
│  ├─ Pending consultations: 0                              │
│  ├─ Received consultations: 1+                            │
│  └─ Status: pending → received → integrated → archived    │
└─────────────────────────────────────────────────────────────┘
```

## Security Measures

### Input Validation
- `sanitize_text_field()` - Regular text inputs
- `sanitize_email()` - Email addresses
- `sanitize_textarea_field()` - Textarea content
- `is_email()` - Email format validation

### Data Protection
- UUIDs generated with `time()` + `uniqid()` + `md5()`
- No nonce required (simplified security)
- Requetes paramétrisées via `$wpdb->insert()`

### File Operations
- Real path validation (no path traversal)
- File type whitelist (pdf, jpg, jpeg, png, gif, tiff)
- 50MB size limit per file

### Email Security
- From/Reply-To headers set from user email
- VERSO_WEBHOOK subject for identification
- Plain text email (no HTML injection risk)

## Deployment Strategy

### Local Development
```
/home/onyx/projects/skills/verso-consultation-plugin/
├── verso-consultation-plugin.php [MODIFIED]
├── includes/
├── js/
├── css/
├── templates/
└── deploy.sh
```

### Remote (verso-vet.com)
```
/www/wp-content/plugins/verso-consultation-plugin/
├── verso-consultation-plugin.php
├── includes/
├── js/
├── css/
└── templates/
```

**Deployment Method**: SFTP via `./deploy.sh`
**Files Modified**: Only plugin directory (no risk to rest of WordPress)

## Testing Strategy

1. **Unit Tests** - None (WordPress plugin, difficult to test)
2. **Integration Tests** - Test form submission → email → database
3. **End-to-End Tests** - Submit form → verify email → verify dashboard

**Test Case**:
```
Form Submission
└─ Name: Dupont Jean
└─ Email: jp@test.local
└─ Animal: Rex (Chien)
└─ Reason: Test submission
└─ Expected: Email sent + Dashboard shows 1 Received
```

## Future Improvements

1. **Webhook Retry Logic** - Handle failed email sends
2. **File Upload Support** - Store documents with consultation
3. **SMS Notifications** - Alert staff on new submissions
4. **Custom Status Workflow** - Additional statuses beyond new/reviewed/integrated
5. **API Integration** - Direct REST API instead of email webhook
