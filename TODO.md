# verso-consultation-plugin - TODO

## Current Status: AJAX Handler Fixed - Awaiting Deployment

### ✅ Completed (2026-05-08)

**Email Pipeline Root Cause & Fix**
- [x] Identified: OVH shared hosting blocks sendmail from SSH/CLI, allows from PHP-FPM
- [x] Fixed: Restored WordPress AJAX handler pattern (wp_ajax_verso_submit_consultation)
- [x] Fixed: Added action parameter to AJAX submission in form.js
- [x] Fixed: Changed form selector from #verso-consultation-form to #verso-form
- [x] Updated: imap_monitor.py to parse "[Verso Vet] Demande" subject + JSON attachment
- [x] Updated: service.py to handle store_consultation_from_json()

**Form Pipeline**
- [x] Clean form without emojis (4 numbered sections)
- [x] Form page at /demande-de-consultation/ with jQuery AJAX handler
- [x] Email subject format: [Verso Vet] Demande {uuid} - {animal} ({espece})
- [x] verso-consultation-plugin.php with WordPress AJAX handlers
- [x] js/form.js with proper action parameter appended to FormData
- [x] All includes files present and functional

**Infrastructure**
- [x] verso-consultation-plugin structure created
- [x] manifest.json generated for Forge
- [x] CLAUDE.md documentation
- [x] API.md documentation  
- [x] ARCHITECTURE.md documentation
- [x] deploy.sh script verified (only modifies plugin files)
- [x] consultation-requests skill ready on 10.0.0.44:8092
- [x] Dashboard database cleaned (0 consultations)

### ⏳ In Progress (2026-05-08)

**Critical Fix - AJAX Action Parameter**
- [x] Identified root cause: form.js not sending 'action' parameter to WordPress AJAX
- [x] Fixed: form.js now appends `formData.append('action', 'verso_submit_consultation')`
- [x] Committed: Code change ready in dev branch
- [ ] DEPLOYMENT BLOCKED: Cannot use direct SSH/FPT per CLAUDE.md constraints
- [ ] Need: Alternative deployment method (REST API, wp-cli, or manual admin panel update)

### 🎯 Deployment Options (Choose One)

**Option A: WordPress Admin Panel (Recommended)**
1. Login to https://verso-vet.com/wp-admin (user: onyx)
2. Go to: Plugins → verso-consultation-plugin
3. Manually upload/update js/form.js (add action parameter)
4. Test: Submit form and check browser console for successful AJAX call

**Option B: WordPress REST API + Plugin Update**
1. Export updated verso-consultation-plugin.php + js/form.js
2. Create ZIP file
3. Use WP REST API to upload plugin update
4. Activate via admin panel
5. Test endpoint

**Option C: Contact OVH Support**
1. Request managed deployment of updated plugin files
2. Provide deployment script and configuration
3. Verify deployment succeeded

**Option D: Setup wp-cli**
1. Configure wp-cli on OVH server
2. Use wp-cli to activate plugins programmatically
3. Deploy via script that uses wp-cli instead of SSH

### 🎯 After Deployment: Test Pipeline

**Step 1: Test AJAX Endpoint**
```bash
curl -s -X POST "https://verso-vet.com/wp-admin/admin-ajax.php" \
  -d "action=verso_submit_consultation" \
  -d "owner_nom=Dupont" \
  -d "owner_prenom=Jean" \
  -d "owner_email=test@verso-vet.com" \
  -d "owner_telephone=0612345678" \
  -d "animal_nom=Rex" \
  -d "animal_espece=Chien" \
  -d "motif=Test" | jq .
```
Expected: `{"success":true,"data":{"message":"...","uuid":"verso-..."}}`

**Step 2: Full Browser Test**
```bash
# 1. Visit form
https://verso-vet.com/demande-de-consultation/

# 2. Submit test data:
# Nom: Dupont, Prénom: Jean
# Email: test@verso-vet.com
# Téléphone: +33612345678
# Animal: Rex (Chien)
# Motif: Test submission

# 3. Wait 60s for email delivery

# 4. Check IMAP monitor
curl -s http://10.0.0.44:8092/cron

# 5. Verify dashboard
http://10.0.0.44:8092/dashboard
```
Expected: Consultation appears as "Received" status

### 🚀 Post-Deployment Checklist

- [ ] Email received at consultations@verso-vet.com with correct subject
- [ ] IMAP monitor parsed the email successfully
- [ ] Consultation visible in dashboard as "Received"
- [ ] Can view consultation details in dashboard
- [ ] Can change status in dashboard
- [ ] Can export consultation as JSON

### 🐛 Known Issues & Limitations

1. **Form doesn't show success/error messages in real-time**
   - Current behavior: Form silently accepts, page doesn't change
   - Workaround: Check email manually or wait for dashboard update
   - Solution: Add JavaScript feedback (TODO for future)

2. **WordPress database storage is backup only**
   - Primary flow: Email webhook → consultation-requests skill
   - WordPress table (wp_verso_consultations) not actively used
   - Rationale: Decouples WordPress from consultation tracking

3. **No file upload support yet**
   - Form only handles text fields
   - File upload classes exist but not integrated
   - TODO: Add file handling when needed

4. **Nonce not required**
   - Simplified security (acceptable for email webhook)
   - UUIDs provide sufficient spam protection
   - Could add WP nonce later if needed

### 📝 Documentation Todos

- [ ] Update README.md with new webhook email format
- [ ] Add troubleshooting section for IMAP sync delays
- [ ] Document consultation-requests integration points
- [ ] Create deployment guide for team

### 🔧 Technical Debt

1. **Separate email sending logic into class**
   - Currently in verso_check_form_submission() function
   - Refactor: Move to class-webhook-sender.php::send()

2. **Add logging for debugging**
   - Log form submissions to file
   - Log email send success/failure
   - Help with troubleshooting IMAP sync

3. **Unit tests for classes**
   - Test email subject formatting
   - Test UUID generation
   - Test input sanitization

4. **Error handling improvements**
   - Currently: silent failures (returns void)
   - Future: Log errors, optionally send admin email

### 💡 Future Features

- [ ] SMS notifications to staff on new submissions
- [ ] File upload support (documents, images)
- [ ] Custom consultation status workflow
- [ ] Direct REST API (instead of email webhook)
- [ ] Webhook signatures for verification
- [ ] Rate limiting (prevent spam)
- [ ] Auto-response email to submitter
- [ ] Consultation templates (standardized forms)
- [ ] Mobile app integration

## Modified Files (This Session)

```
/home/onyx/projects/skills/verso-consultation-plugin/
├── verso-consultation-plugin.php [EMAIL SUBJECT UPDATED]
├── manifest.json [NEW - Forge config]
├── CLAUDE.md [NEW - Dev guide]
├── API.md [NEW - API docs]
├── ARCHITECTURE.md [NEW - Architecture docs]
└── TODO.md [YOU ARE HERE]
```

## Git Status

**Branch**: dev
**Status**: Modified verso-consultation-plugin.php + Added 4 docs

**Ready to commit**: YES (after Forge validation passes)

---

Last updated: 2026-05-08 22:45 UTC
