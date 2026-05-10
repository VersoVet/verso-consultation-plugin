# verso-consultation-plugin - Changelog

## [3.2.0] - 2026-05-10

### Added
- **REST API endpoint** `/verso/v1/setup` for secure plugin configuration
- **Auto-setup page creation** on plugin activation via `verso_activate_plugin()` hook
- **Lazy initialization** via `verso_lazy_init_page()` to ensure page exists on every load
- **Safe deployment scripts**:
  - `deploy-safe.sh` - Deploy via REST API (recommended)
  - `install-plugin.sh` - Full installation with plugin upload
  - `deploy-auto.sh` - WP-CLI deployment (local with PHP)

### Changed
- Enhanced security checks in file upload handler
- Improved error handling and validation messages (all French)
- Better form HTML structure with responsive grid layouts
- Updated deployment documentation

### Security
- ✅ Path traversal protection with UUID regex + realpath() + strpos()
- ✅ MIME type validation using `mime_content_type()` (actual file inspection)
- ✅ Extension whitelist: jpg, jpeg, png, gif, webp, pdf, doc, docx
- ✅ File count limit: max 5 files per submission
- ✅ File size limit: max 10 MB per file, 30 MB total
- ✅ Filename sanitization with special character removal
- ✅ Directory protection: `.htaccess` with "deny from all"
- ✅ Autonomous cleanup: files deleted after email dispatch

### Infrastructure
- Centralized WordPress credentials via Vault (ONYX_VAULT_TOKEN)
- Three deployment methods:
  1. ZIP via WordPress Admin (manual)
  2. WP-CLI local deployment (with PHP)
  3. REST API secure deployment (recommended)

---

## [3.1.0] - 2026-05-09

### Added
- **Secure file upload support**
  - Client-side file selection and preview
  - Server-side MIME validation
  - JSON metadata attachment with file info
  - Automatic cleanup after email send

### Added
- Dedicated upload directory: `wp-content/uploads/verso-consultations/`
- 4-layer path traversal protection
- File metadata in JSON: `original_name`, `stored_name`, `mime_type`, `size`

### Changed
- Form HTML fully populated on page (all 5 sections: owner, vet, animal, motif, files)
- Enhanced email structure with pièces jointes
- Improved responsive layout with CSS Grid

---

## [3.0.0] - 2026-05-08

### Added
- AJAX form handler for consultation submissions
- Email integration with WordPress `wp_mail()`
- Database table `verso_consultations` creation
- Responsive form with Bootstrap-like styling
- Security headers: From, Reply-To, proper email format

### Added
- Form validation (required fields, email format)
- Client-side validation with JavaScript
- Loading spinner during submission
- Success/error messages
- Auto-fade messages after 3 seconds

---

## [2.0.0] - 2026-05-05

### Added
- Initial WordPress plugin structure
- Shortcode registration
- Basic form HTML generation

---

## Notes

### Deployment Checklist
- [ ] WordPress plugin directory writable
- [ ] OVH email credentials working (SMTP via php mail())
- [ ] consultation-requests skill running
- [ ] IMAP credentials in Vault
- [ ] Page created with slug `demande-de-consultation`
- [ ] Form tested via browser
- [ ] Email received in consultations@verso-vet.com
- [ ] consultation-requests /cron triggered
- [ ] Consultation appears in dashboard

### Next Phase
- [ ] consultation-requests update: Extract file attachments from IMAP emails
- [ ] ERP integration: Upload files to erp-connector via signed requests
- [ ] Dashboard display: Show consultation with associated files
