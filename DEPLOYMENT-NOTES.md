# Deployment Notes - verso-consultation-plugin

## Current Issue & Solution (2026-05-08)

### Problem Identified
The AJAX form submission was returning HTTP 0 (WordPress error) instead of a proper JSON response.

**Root Cause**: The form.js was sending AJAX requests to `/wp-admin/admin-ajax.php` but was not including the required `action` parameter in the POST data. WordPress AJAX routing requires this parameter to know which handler to invoke.

### Solution Implemented
Modified `js/form.js` line 110-111 to explicitly append the action parameter:

```javascript
const formData = new FormData(form[0]);
// Add action parameter for WordPress AJAX routing
formData.append('action', 'verso_submit_consultation');
```

This ensures the request reaches the `verso_handle_consultation_ajax()` handler registered via:
```php
add_action('wp_ajax_verso_submit_consultation', 'verso_handle_consultation_ajax');
add_action('wp_ajax_nopriv_verso_submit_consultation', 'verso_handle_consultation_ajax');
```

## Files Modified
- `js/form.js` - Added action parameter to FormData (commit: aee7feb)

## What Needs to Happen Next

The code fix is committed and ready, but **deployment to verso-vet.com is blocked by project constraints** that prohibit direct SSH/FTP access to the server.

### Constraint Reference
From CLAUDE.md:
```
⚠️ INTERDICTION: Modification Directe via FTP/SSH

RÈGLE STRICTE: 
- ❌ JAMAIS modifier verso-vet.com directement via FTP/SSH
- ❌ JAMAIS toucher les fichiers du serveur via les credentials OVH
- ❌ JAMAIS modifier la base de données directement

RAISON: Risque de corruption du site WordPress et perte de données
```

### Recommended Deployment Method

**Via WordPress Admin Panel (Preferred):**
1. Login to https://verso-vet.com/wp-admin with user `onyx`
2. Navigate to: Plugins → verso-consultation-plugin
3. Manually edit file: `js/form.js`
4. Add these lines after `const formData = new FormData(form[0]);`:
   ```javascript
   // Add action parameter for WordPress AJAX routing
   formData.append('action', 'verso_submit_consultation');
   ```
5. Save the file
6. Test by submitting the form

OR

**Via Git Pull (if Git is available on server):**
```bash
cd /var/www/verso-vet/wp-content/plugins/verso-consultation-plugin
git pull origin dev
```

## Expected Behavior After Deployment

### Successful AJAX Response
When form is submitted, browser should receive:
```json
{
  "success": true,
  "data": {
    "message": "Demande envoyée avec succès",
    "uuid": "verso-1715234567-a1b2c3d4"
  }
}
```

### Email Generated
- **To**: consultations@verso-vet.com
- **Subject**: `[Verso Vet] Demande verso-{uuid} - {animal_nom} ({animal_espece})`
- **Body**: Formatted text with all consultation data
- **Attachment**: `consultation.json` containing structured consultation data

### Full Pipeline
1. Form submission via AJAX
2. PHP handler generates UUID, builds email with JSON attachment
3. Email sent via WordPress wp_mail (runs in PHP-FPM context, not CLI)
4. IMAP monitor picks up email from consultations@verso-vet.com inbox
5. JSON attachment parsed and stored in consultation-requests database
6. Dashboard at http://10.0.0.44:8092/dashboard shows consultation as "Received"

## Technical Details

### Why This Works on OVH Shared Hosting

OVH blocks sendmail from SSH/CLI context but allows it from PHP-FPM (web requests). The WordPress AJAX pattern leverages this:

1. **Old approach** (broken): wp_footer hook → executes during page render → CLI context → sendmail blocked
2. **New approach** (fixed): wp_ajax handler → executes in PHP-FPM → sendmail works

### AJAX Request Flow
```
Browser Form → jQuery AJAX
  ↓
POST /wp-admin/admin-ajax.php
  with: action=verso_submit_consultation + form data
  ↓
WordPress routing system
  ↓
verso_handle_consultation_ajax() handler
  (registered via add_action hooks)
  ↓
Generate UUID
Build email with JSON attachment
Call wp_mail() in PHP-FPM context ← Sendmail works here!
Store to WordPress database
Return JSON response
  ↓
JavaScript success handler processes response
```

## Testing After Deployment

### 1. Quick AJAX Test
```bash
curl -s -X POST "https://verso-vet.com/wp-admin/admin-ajax.php" \
  -d "action=verso_submit_consultation" \
  -d "owner_nom=Test" \
  -d "owner_prenom=User" \
  -d "owner_email=test@example.com" \
  -d "owner_telephone=0612345678" \
  -d "animal_nom=Fluffy" \
  -d "animal_espece=Cat" \
  -d "motif=Test"
```

Expected: Valid JSON response (not `0`)

### 2. Form Browser Test
1. Navigate to https://verso-vet.com/demande-de-consultation/
2. Fill in all required fields
3. Click submit
4. Should see success message
5. Check email inbox for consultation email within 1 minute

### 3. End-to-End Pipeline Test
1. Submit form (as above)
2. Wait 60 seconds
3. Trigger IMAP monitor: `curl -s http://10.0.0.44:8092/cron`
4. Check dashboard: `http://10.0.0.44:8092/dashboard`
5. Verify consultation shows with status "Received"

## Notes for Team

- The action parameter fix is a one-line addition to js/form.js
- No changes needed to PHP handler (already correctly registered)
- No changes needed to consultation-requests skill (already handles JSON)
- No changes needed to IMAP monitoring (already working)
- This is a critical fix that unblocks the entire email pipeline

## Version Control

- **Branch**: dev
- **Commit**: aee7feb (fix: Add action parameter to AJAX form submission)
- **Status**: Ready for deployment
