# TODO - Verso Consultation Plugin

> **Status**: 🟢 **PRODUCTION** | Version 3.5.1 | Last Updated: 2026-05-10

## Current Status
✅ **OPERATIONAL** - WordPress AJAX form + secure file uploads + differentiated emails + inline confirmation UX.

**Latest (2026-05-10 - v3.5.1)**:
- ✅ Complete address fields (street, postal code, city, country - France default)
- ✅ Progressive multi-file upload UI (10 files max, 5MB each, 50MB total)
- ✅ Animated progress bars for file selection
- ✅ Differentiated confirmation emails (owner + vet clinic)
- ✅ Professional post-submission confirmation section
- ✅ Inline toggle between form and confirmation (same page)
- ✅ Always-visible header during state transitions
- ✅ UUID reference for consultation tracking
- ✅ Secure file upload support with MIME validation
- ✅ Safe deletion with UUID validation + realpath() confinement
- ✅ Autonomous cleanup after email dispatch
- ✅ Form submission via AJAX (verso_submit_consultation action)
- ✅ Full integration with consultation-requests skill (IMAP monitoring)
- ✅ Safe deployment script (scp only, no remote commands)

---

## Completed ✅

### Core Features
- [x] WordPress AJAX form for consultation submissions
- [x] User input validation (required fields, email format)
- [x] JSON data serialization with JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE
- [x] Email sending to consultations@verso-vet.com
- [x] WordPress database table creation on plugin activation
- [x] Safe deployment script (rsync/scp, no remote execution)
- [x] Secure email headers (From: domain address, Reply-To: client email)
- [x] PHP native mail() via WordPress wp_mail()

### Security & File Handling (NEW v3.1.0)
- [x] Secure file upload support (max 10 files, 5MB each, 50MB total)
- [x] MIME type validation (mime_content_type on actual file, not declared)
- [x] Extension whitelist (jpg, jpeg, png, gif, webp, pdf, doc, docx)
- [x] Filename sanitization (remove special chars, prevent traversal)
- [x] Dedicated upload directory (`wp-content/uploads/verso-consultations/`)
- [x] Directory protection (`.htaccess deny from all`, `index.php` stub)
- [x] Secure deletion (`verso_safe_delete_consultation_dir()`)
  - UUID format validation (regex `verso-\d{10}-[a-f0-9]{8}`)
  - Path confinement verification (realpath() + strpos())
  - File-only deletion (no recursive subdirs)
- [x] File metadata in JSON (original_name, stored_name, mime_type, size)
- [x] Autonomous cleanup (regardless of email send success/failure)

### Frontend
- [x] Responsive form layout (Bootstrap)
- [x] Owner/Contact fields (name, email, phone, address)
- [x] Veterinarian reference fields (optional)
- [x] Patient/Animal fields (name, species, breed)
- [x] Consultation reason field (motif)
- [x] Client-side validation
- [x] AJAX loading spinner

### Deployment
- [x] `deploy-form.sh` script (scp-based, no remote commands)
- [x] Upload `verso-consultation-plugin.php`
- [x] Upload `js/form.js` and `css/style.css`
- [x] Secure, non-destructive updates

### Documentation
- [x] README.md with architecture and deployment steps
- [x] API.md with form parameters and response formats
- [x] Inline code documentation and comments
- [x] Email format documentation
- [x] Integration guide with consultation-requests

---

## Integration Status

### With consultation-requests Skill
✅ Email → IMAP Monitoring → SQLite DB → REST API → Dashboard

**Flow**:
1. Form submission → `wp_mail()` to `consultations@verso-vet.com`
2. consultation-requests `/cron` monitor fetches email every 60s
3. Extracts `consultation.json` attachment
4. Stores in SQLite with IMAP UID tracking
5. Dashboard displays with options:
   - **View** details
   - **Delete** (soft delete + IMAP removal)
   - **Integrate** with ERP (search patients via erp-connector)

---

## Test Results ✅

| Test | Status | Details |
|------|--------|---------|
| Form submission | ✅ PASS | AJAX POST to admin-ajax.php |
| Data validation | ✅ PASS | Required fields, email format |
| Email sending | ✅ PASS | Arrives at consultations@verso-vet.com |
| JSON attachment | ✅ PASS | Correct format, parseable |
| OVH SPF/DKIM | ✅ PASS | From: domain address compliant |
| Database storage | ✅ PASS | Data persisted in WordPress DB |
| Safe deployment | ✅ PASS | deploy-form.sh no remote commands |
| Integration | ✅ PASS | consultation-requests receives + processes |

---

## Future Enhancements (Optional)

### Phase 1: Frontend Improvements
- [ ] File upload for attachments (photos, documents)
- [ ] Multi-step form (wizard)
- [ ] Auto-save drafts (localStorage)
- [ ] Confirmation page after submission
- [ ] Success toast notification with UUID
- [ ] Accessibility improvements (WCAG 2.1)

### Phase 2: Advanced Features
- [ ] Appointment scheduling integration
- [ ] Real-time form status via webhook callback
- [ ] File size validation before upload
- [ ] Image compression for attachments
- [ ] Captcha/spam protection (reCAPTCHA)
- [ ] Multi-language support (FR/EN)

### Phase 3: Analytics & Tracking
- [ ] Form submission metrics (Google Analytics)
- [ ] Error rate monitoring
- [ ] Average submission time tracking
- [ ] Conversion funnel analysis
- [ ] Heatmap of form interactions

### Phase 4: Integration Enhancements
- [ ] Post-submission webhook to consultation-requests
- [ ] Status update notifications via email
- [ ] Integration status displayed to user
- [ ] Download consultation summary PDF
- [ ] Sync with verso-vet.com WooCommerce (if applicable)

---

## Architecture

```
verso-vet.com (WordPress)
    ↓
verso-consultation-plugin (form + AJAX)
    ↓
email → consultations@verso-vet.com (with JSON attachment)
    ↓
consultation-requests (IMAP monitor every 60s)
    ↓
SQLite DB (with IMAP UID tracking)
    ↓
REST API (v1.0.20+)
    ↓
Dashboard UI (delete + ERP integrate)
```

---

## Configuration

### Email
- **Recipient**: `consultations@verso-vet.com`
- **From**: `Verso Vet <consultations@verso-vet.com>` (OVH requirement)
- **Reply-To**: Client email (from form)
- **Attachment**: `consultation.json` (auto-generated)

### WordPress Pages
- **Form page**: `/demande-de-consultation/` or `/demande-consultation/`
- **Database table**: `wp_verso_consultations`
- **AJAX action**: `verso_submit_consultation`

---

## Known Limitations

1. **No authentication** - Form available to all (intended for public submissions)
2. **No file upload** - Only structured data (JSON), no attachments from client
3. **Synchronous email** - wp_mail() blocks form submission until email sent
4. **Single mailbox** - Monitors only consultations@verso-vet.com (no routing)
5. **No webhook callback** - Form doesn't know if email was successfully received by skill

---

## Deployment Checklist

- [ ] WordPress plugin directory writable
- [ ] OVH email credentials working (SMTP via php mail())
- [ ] consultation-requests skill running on 10.0.0.44:8092
- [ ] IMAP credentials in Vault (imap_host, imap_username, imap_password)
- [ ] Page created with slug `demande-de-consultation`
- [ ] Form tested via browser
- [ ] Email received in consultations@verso-vet.com
- [ ] consultation-requests /cron triggered
- [ ] Consultation appears in dashboard

---

## Version History

| Version | Date | Notes |
|---------|------|-------|
| 3.5.1 | 2026-05-10 | **Bug fix** - Keep header visible during form/confirmation toggle |
| 3.5.0 | 2026-05-10 | **UX redesign** - Inline toggle between form and confirmation on same page + "Submit another request" button |
| 3.4.3 | 2026-05-10 | **Bug fix** - Show confirmation section after submission |
| 3.4.2 | 2026-05-10 | **Email improvements** - Differentiated owner and vet confirmation emails |
| 3.4.1 | 2026-05-10 | **Confirmation emails** - Add professional post-submission UX + email notifications |
| 3.4.0 | 2026-05-10 | **Form enhancement** - Complete address fields + multi-file upload UI (10 files, 5MB each) |
| 3.1.0 | 2026-05-09 | **Secure file uploads** - MIME validation, path traversal protection, safe deletion |
| 3.0.0 | 2026-05-09 | **Full integration** - consultation-requests delete + ERP features |
| 2.0.0 | 2026-05-05 | **IMAP architecture** - Native PHP mail() |
| 1.0.0 | 2026-04-20 | **Initial release** - WordPress plugin |
