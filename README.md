# Verso Consultation Plugin

WordPress plugin for consultation request forms with file uploads and Verso Vet ERP integration.

## Features

- ✅ Adaptive form (veterinarian vs pet owner)
- ✅ Multi-file upload support
- ✅ HMAC signature webhook validation
- ✅ Bootstrap responsive UI
- ✅ Email notifications
- ✅ Vault secret management

## Installation

1. **Upload plugin**
   ```bash
   # Via SFTP or WordPress admin
   scp -r verso-consultation-plugin/ user@verso-vet.com:/var/www/wordpress/wp-content/plugins/
   ```

2. **Activate in WordPress Admin**
   - Go to Plugins → Installed Plugins
   - Click "Activate" on "Verso Consultation Form"

3. **Configure Settings**
   - Go to Settings → Verso Consultation
   - Enter Vault URL (default: `http://10.0.0.44:8050`)
   - Enter Vault Token
   - Enter Skill URL (default: `http://10.0.0.44:8092`)
   - Save Settings

4. **Add Secrets to Vault**
   ```bash
   # consultation_webhook_secret (32 random chars for HMAC)
   curl -s -H "X-Vault-Token: $ONYX_VAULT_TOKEN" \
     -X POST http://10.0.0.44:8050/vault/consultation_webhook_secret \
     -d '{"value": "your-random-secret-32-chars"}'

   # consultation_file_secret (32 random chars for file downloads)
   curl -s -H "X-Vault-Token: $ONYX_VAULT_TOKEN" \
     -X POST http://10.0.0.44:8050/vault/consultation_file_secret \
     -d '{"value": "your-random-secret-32-chars"}'
   ```

## Usage

### Create Consultation Page

The plugin automatically creates a `/demande-consultation/` page on activation with the shortcode `[verso_consultation_form]`.

To manually add the form to any page:
```
[verso_consultation_form]
```

### Form Sections

**1. Submitter Type** (required)
- Veterinarian referrer
- Pet owner

**2. Contact Info** (depends on type)
- For vets: Clinic, email, phone
- For owners: Name, email, phone

**3. Animal Info** (required)
- Name, species, breed
- Sex, birth date, weight
- Microchip number

**4. Consultation Details** (required)
- Specialty (imaging, surgery, orthopedics, etc.)
- Reason for consultation
- Urgent status
- Current treatments

**5. Documents** (optional)
- Accepted: PDF, JPG, PNG, TIFF, DICOM
- Max size: 50 MB total

## Architecture

### File Structure
```
verso-consultation-plugin/
├── verso-consultation-plugin.php    # Main plugin file
├── includes/
│   ├── class-vault-client.php       # Vault API client
│   ├── class-form-handler.php       # Form renderer
│   └── class-webhook-sender.php     # Webhook & submission handler
├── css/
│   └── style.css                    # Bootstrap-based styling
├── js/
│   └── form.js                      # Form logic and validation
└── README.md
```

### Data Flow

```
WordPress Form
    ↓
AJAX POST /wp-json/verso/v1/consultation
    ↓
Webhook Sender
    ├─ Validate nonce
    ├─ Upload files to /wp-content/uploads/consultations/{uuid}/
    ├─ Generate HMAC-SHA256 signature
    └─ POST to consultation-requests skill (port 8092)
    ↓
consultation-requests Skill
    ├─ Validate HMAC signature
    ├─ Store in SQLite
    ├─ Download files from OVH
    ├─ Send email notification
    └─ Status: received
```

## REST Endpoint

**POST** `/wp-json/verso/v1/consultation`

Accepts multipart form data with:
- All form fields (form-data format)
- File uploads (field: `fichiers[]`)
- Nonce token (field: `nonce`)

Response:
```json
{
  "success": true,
  "message": "Demande envoyée avec succès!",
  "uuid": "consult_abc123..."
}
```

## Security

### Webhook Validation
- HMAC-SHA256 signature on request body
- Header: `X-Verso-Signature`
- Secret: `consultation_webhook_secret` from Vault
- Verified by: consultation-requests skill

### File Uploads
- Extension whitelist: pdf, jpg, jpeg, png, tiff, dcm
- Size limit: 50 MB per file
- Stored in: `/wp-content/uploads/consultations/{uuid}/`
- Served via: consultation-requests skill with HMAC token

### CSRF Protection
- WordPress nonce validation
- Form field: `nonce`

## Troubleshooting

### Plugin not activated
- Check WordPress file permissions
- Verify PHP version (≥ 7.4)
- Check error logs in `/wp-content/debug.log`

### Form not showing
- Verify shortcode: `[verso_consultation_form]`
- Check page has content type "Page"
- Clear WordPress cache if using caching plugin

### Webhook errors
- Check Vault URL in plugin settings
- Verify Vault token is valid
- Check consultation-requests skill is running: `curl http://10.0.0.44:8092/health`
- Check logs: `curl http://10.0.0.44:8092/consultations` (skill API)

### File upload issues
- Check upload directory permissions: `/wp-content/uploads/consultations/`
- Verify file size < 50 MB
- Confirm file type in whitelist
- Check disk space available

## Development

### Testing locally
```bash
# 1. Ensure plugin is activated
# 2. Navigate to /demande-consultation/
# 3. Fill form and submit
# 4. Check browser console for errors
# 5. Check WordPress debug log

# View logs
tail -f /var/www/wordpress/wp-content/debug.log
```

### Debugging
Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Making changes
1. Modify PHP files in `includes/`
2. Update CSS in `css/style.css`
3. Update JS in `js/form.js`
4. Changes take effect immediately (no build step)

## Support

For issues or questions:
- Check plugin settings are configured correctly
- Review WordPress error logs
- Verify consultation-requests skill is running
- Contact: consultations@verso-vet.com

## License

Copyright © 2026 Verso Vet. All rights reserved.
