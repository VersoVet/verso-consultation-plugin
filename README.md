# 🏥 Verso Consultation Form

Professional consultation request form for veterinary practice with secure file uploads, email notifications, and ERP integration.

[![Version](https://img.shields.io/badge/version-3.5.1-blue.svg)](https://github.com/VersoVet/verso-consultation-plugin/releases)
[![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-5.6%2B-blue.svg)](https://php.net)

---

## ✨ Features

### 📋 Complete Consultation Form
- **Owner Information** - Name, email, phone, complete address (street, postal code, city, country)
- **Veterinary Reference** - Optional clinic and vet details  
- **Patient Details** - Animal name, species, breed
- **Consultation Reason** - Detailed description
- **File Attachments** - Up to 10 files (5 MB each, 50 MB total) with progressive upload UI

### 🔒 Enterprise Security
- **Path Traversal Protection** - 4-layer validation (UUID regex + realpath + strpos)
- **MIME Type Validation** - Actual file inspection, not extension-based
- **Extension Whitelist** - jpg, jpeg, png, gif, webp, pdf, doc, docx
- **Secure Storage** - Isolated directory with .htaccess protection
- **Automatic Cleanup** - Files deleted after email dispatch

### 📧 Smart Email Integration
- **Auto-dispatch** to consultations@verso-vet.com
- **Differentiated emails** - Owner confirmation + vet clinic notification
- **File attachments** - JSON metadata + uploaded files
- **Proper headers** - SPF/DKIM compliant (OVH compatible)
- **Email metadata** - Original filename, MIME type, size included
- **Reference tracking** - UUID in all communications

### 🎨 Professional User Experience
- **Inline confirmation** - Form and confirmation on same page
- **Progressive upload** - Add files one by one with visual feedback
- **Smooth transitions** - Toggle between form and confirmation
- **Always-visible header** - Logo and navigation stay during transitions
- **Reference tracking** - UUID displayed for consultation tracking

### 🚀 Multiple Deployment Methods
- **ZIP Upload** - Manual via WordPress Admin (2 min)
- **WP-CLI Local** - Automated with PHP (30 sec)  
- **GitHub** - Version control and CI/CD ready

---

## 🎯 Quick Start

### 1. Install Plugin

```bash
# Download latest version
wget https://github.com/VersoVet/verso-consultation-plugin/releases/download/v3.5.1/verso-consultation-plugin-v3.5.1.zip

# Upload via WordPress Admin
# Extensions > Add New > Upload an Extension > verso-consultation-plugin-v3.5.1.zip
# Click Install > Activate
```

### 2. Deploy Form Page

```bash
# Automatic setup via REST API (recommended)
./deploy-safe.sh

# Or manually create page with slug: demande-de-consultation
```

### 3. Access Form

```
https://verso-vet.com/demande-de-consultation/
```

---

## 📚 Documentation

### User Documentation
- **[USER_GUIDE.md](docs/USER_GUIDE.md)** - How to submit consultations
- **[FAQ.md](docs/FAQ.md)** - Common questions

### Developer Documentation  
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Technical design & security
- **[API.md](API.md)** - AJAX endpoints and JSON format
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Detailed deployment steps
- **[CHANGELOG.md](CHANGELOG.md)** - Version history

### Project Structure

```
verso-consultation-plugin/
├── verso-consultation-plugin.php    # Main plugin (AJAX + activation)
├── js/form.js                       # jQuery form handler + validation
├── css/style.css                    # Responsive styling
├── deploy-safe.sh                   # Token-auth REST API deployment
├── bump-version.sh                  # Version management script
├── README.md                        # This file
├── CHANGELOG.md                     # Release notes
├── ARCHITECTURE.md                  # Security & design details
├── API.md                           # API documentation
├── DEPLOYMENT.md                    # Deployment guide
└── VERSION                          # Current version (3.2.1)
```

---

## ⚙️ Configuration

### File Upload Limits
- **Max files:** 10 per submission
- **Max file size:** 5 MB each
- **Max total size:** 50 MB per submission
- **Progressive UI** - Add files one at a time with animated progress bars

### Email Recipient
Default: `consultations@verso-vet.com`

### Allowed File Types
```
Images:     jpg, jpeg, png, gif, webp
Documents:  pdf, doc, docx
```

### Storage Location
```
wp-content/uploads/verso-consultations/
└── verso-{timestamp}-{hash}/
    ├── photo_0.jpg
    └── document_1.pdf
    (auto-deleted after email send)
```

---

## 🔐 Security Highlights

### ✅ Path Traversal Prevention
```
UUID Format:     verso-1715234567-a1b2c3d4
Validation:      Regex + realpath() + strpos() confinement
Result:          ../../../etc/passwd attempts fail absolutely
```

### ✅ File Type Validation  
```
Method:    mime_content_type() - Inspects actual file, not declared extension
Bypass:    Impossible to upload disguised executables (*.php, *.exe)
```

### ✅ Directory Protection
```
.htaccess:  deny from all + Options -Indexes
HTTP:       Files NOT accessible via web browser
Result:     403 Forbidden if accessed directly
```

### ✅ Automatic Cleanup
```
After Email:     All uploaded files deleted
On Error:        Files still deleted (never orphaned)
Confirmation:    Plugin logs cleanup operations
```

---

## 📊 Usage Statistics

```
✅ 3 Deployment Methods
✅ 4-Layer Security Validation  
✅ 100% WordPress Standards Compliant
✅ Zero Direct Database Modifications (REST API only)
✅ Automatic Version Tracking
✅ PHP 5.6+ Compatible
✅ WordPress 5.0+ Compatible
```

---

## 🔄 Version Management

Bump version automatically:

```bash
./bump-version.sh patch    # 3.2.1 → 3.2.2
./bump-version.sh minor    # 3.2.1 → 3.3.0  
./bump-version.sh major    # 3.2.1 → 4.0.0
```

Creates:
- Updated VERSION file
- Updated plugin header
- Git commit + tag
- Versioned ZIP: `verso-consultation-plugin-v3.2.2.zip`

---

## 🐛 Troubleshooting

### Form Not Loading
1. Verify plugin is **Activated** in WordPress
2. Check page exists: `https://your-site.com/demande-de-consultation/`
3. Run `./deploy-safe.sh` to create/update page
4. Clear WordPress cache

### Files Not Received  
1. Check recipient: `consultations@verso-vet.com`
2. Verify SMTP/email is working
3. Check WordPress error logs
4. Test with smaller files

### Permission Errors
1. Ensure `wp-content/uploads/` is writable
2. Verify user has admin capabilities
3. Check PHP can read/write files

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete troubleshooting.

---

## 📞 Support

- **Email:** support@verso-vet.com
- **Issues:** [GitHub Issues](https://github.com/VersoVet/verso-consultation-plugin/issues)
- **Docs:** [Full Documentation](ARCHITECTURE.md)

---

## 📄 License

GPL v2 or later - See [LICENSE](LICENSE) file

---

## 🏢 About Verso

**Verso Vet** - Professional veterinary practice specializing in rehabilitation and preventative care.

- 🌐 Website: [verso-vet.com](https://verso-vet.com)
- 📍 France
- 🏥 Veterinary consultation & rehabilitation

---

**Made with ❤️ by Verso Vet Team**  
*Professional Healthcare for Your Pet*
