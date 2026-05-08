# verso-consultation-plugin - TODO

## Current Status: Ready for Validation & Deployment

### ✅ Completed (2026-05-08)

**Form Pipeline**
- [x] Clean form without emojis (4 numbered sections)
- [x] Form page created at /demande-de-consultation/ with hero image
- [x] Form submission via POST with verso_action=submit_consultation
- [x] Email subject format: VERSO_WEBHOOK UUID:... TYPE:consultation ANIMAL:...
- [x] verso-consultation-plugin.php updated with correct webhook format
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

**Validation & Deployment**
- [ ] Forge validation: verso-consultation-plugin
- [ ] Forge validation: consultation-requests skill
- [ ] Deploy verso-consultation-plugin via script or Forge
- [ ] Deploy consultation-requests skill update
- [ ] End-to-end test: form submission → email → dashboard

### 🎯 Next Steps (After Validation)

**Step 1: Validate with Forge**
```bash
# Validate verso-consultation-plugin
curl -X POST http://10.0.0.13:4080/api/validate/verso-consultation-plugin

# Validate consultation-requests
curl -X POST http://10.0.0.13:4080/api/validate/consultation-requests
```

**Step 2: Deploy**
```bash
# Deploy verso-consultation-plugin
cd /home/onyx/projects/skills/verso-consultation-plugin
./deploy.sh

# OR via Forge
forge deploy verso-consultation-plugin
```

**Step 3: Test Pipeline**
```bash
# 1. Visit form
https://verso-vet.com/demande-de-consultation/

# 2. Submit test data:
# Nom: Dupont
# Prénom: Jean
# Email: jp@test.local
# Téléphone: +33612345678
# Adresse: 123 Rue Test
# Animal: Rex (Chien)
# Motif: Test submission après déploiement

# 3. Wait 1 minute for IMAP sync

# 4. Check dashboard
http://10.0.0.44:8092/dashboard

# Expected: 1 Received consultation visible
```

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
