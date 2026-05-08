# Verso Consultation Plugin

WordPress plugin for consultation form submissions with email notifications.

## Features

- **Consultation Form**: jQuery AJAX form for submitting consultation requests
- **Email Notifications**: Sends consultation data to `consultations@verso-vet.com` with JSON attachment
- **WordPress Database**: Stores submissions in WordPress database table
- **OVH Compatible**: Secure email sending using WordPress `wp_mail()` (PHP native) with SPF/DKIM compliant headers

## Structure

```
verso-consultation-plugin/
├── verso-consultation-plugin.php    # Main plugin file (AJAX handler + activation hook)
├── js/
│   └── form.js                      # jQuery form submission handler
├── css/
│   └── style.css                    # Form styling
├── deploy-form.sh                   # Secure deployment script (scp only, no remote commands)
├── manifest.json                    # Forge configuration
└── README.md                        # This file
```

## Installation

1. Place in WordPress `/wp-content/plugins/verso-consultation-plugin/`
2. Activate plugin in WordPress admin
3. Create a page with slug `demande-de-consultation` (or `demande-consultation`)
4. Form will automatically load on that page

## Deployment

### Prerequisites

```bash
export OVH_SSH_PASS='your-ovh-password'
```

### Deploy

```bash
cd verso-consultation-plugin
./deploy-form.sh
```

The script:
- Uploads `verso-consultation-plugin.php` to plugin directory
- Uploads `js/form.js` and `css/style.css`
- **Does NOT** execute remote commands or modify site configuration
- Safe for OVH shared hosting

## How It Works

### Form Submission Flow

1. User fills form on `/demande-de-consultation/`
2. jQuery AJAX sends data to WordPress AJAX handler
3. Handler validates data, creates JSON attachment, sends email
4. Email sent to `consultations@verso-vet.com` with:
   - From: `Verso Vet <consultations@verso-vet.com>` (OVH SPF/DKIM requirement)
   - Reply-To: Client email (for responses)
   - Attachment: `consultation.json` (structured data)
5. Plugin stores submission in WordPress database table `wp_verso_consultations`

### Email Headers

```php
$headers = [
    'Content-Type: text/plain; charset=UTF-8',
    'From: Verso Vet <consultations@verso-vet.com>',  // Domain address (required by OVH)
    'Reply-To: ' . $owner_email,                      // Client can be reached
];
```

## Testing

```bash
# Test AJAX endpoint
curl -X POST "https://verso-vet.com/wp-admin/admin-ajax.php" \
  -d "action=verso_submit_consultation" \
  -d "owner_nom=Test" \
  -d "owner_prenom=User" \
  -d "owner_email=test@example.com" \
  -d "owner_telephone=0600000000" \
  -d "animal_nom=Fluffy" \
  -d "animal_espece=Chat" \
  -d "motif=Test consultation"
```

Expected response:
```json
{
  "success": true,
  "data": {
    "message": "Demande envoyée avec succès",
    "uuid": "verso-1234567890-abcdef12"
  }
}
```

## Integration with consultation-requests Skill

Emails sent by this plugin are monitored by the `consultation-requests` skill via IMAP:

1. **consultation-requests** IMAP monitor reads emails from `consultations@verso-vet.com`
2. Extracts `consultation.json` attachment
3. Stores data in SQLite database
4. Exposes via REST API: `http://10.0.0.44:8092/consultations`
5. Dashboard displays all consultations: `http://10.0.0.44:8092/dashboard`

## Security Notes

- All user input is sanitized via WordPress functions
- JSON attachment is written to uploads directory, deleted after email send
- AJAX endpoint available to unauthenticated users (necessary for client-side form)
- Email From: header is fixed domain address (prevents spoofing)
