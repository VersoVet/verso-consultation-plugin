#!/bin/bash

##############################################################################
# verso-consultation-plugin - Déploiement via WordPress REST API
#
# Usage:
#   ./deploy-via-api.sh
#
# Variables d'environnement requises:
#   ONYX_VAULT_TOKEN  = Token Vault pour récupérer credentials WordPress
#
##############################################################################

set -e

# Configuration
WP_SITE_URL="https://verso-vet.com"
PLUGIN_SLUG="verso-consultation-plugin"
PLUGIN_ZIP="verso-consultation-plugin.zip"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# En-tête
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   verso-consultation-plugin - Déploiement via API         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Get credentials from Vault
echo -e "${YELLOW}▶ Récupération des credentials depuis Vault...${NC}"

if [ -z "$ONYX_VAULT_TOKEN" ]; then
  echo -e "${RED}✗ ONYX_VAULT_TOKEN non défini${NC}"
  exit 1
fi

CREDS=$(curl -s -H "X-Vault-Token: $ONYX_VAULT_TOKEN" http://10.0.0.44:8050/vault/wordpress_credentials | \
  python3 -c "import sys, json; data=json.load(sys.stdin); creds=json.loads(data['value']); print(f\"{creds['username']}:{creds['app_password']}\")" 2>/dev/null)

if [ -z "$CREDS" ]; then
  echo -e "${RED}✗ Impossible de récupérer les credentials${NC}"
  exit 1
fi

USER=$(echo "$CREDS" | cut -d: -f1)
PASS=$(echo "$CREDS" | cut -d: -f2)

echo -e "${GREEN}✓ Credentials chargés (user: $USER)${NC}"
echo ""

# Créer le ZIP
echo -e "${YELLOW}▶ Création du ZIP du plugin...${NC}"
TEMP_DIR="/tmp/verso-plugin-$$"
mkdir -p "$TEMP_DIR/$PLUGIN_SLUG"

cp verso-consultation-plugin.php "$TEMP_DIR/$PLUGIN_SLUG/"
cp -r js/ "$TEMP_DIR/$PLUGIN_SLUG/" 2>/dev/null || true
cp -r css/ "$TEMP_DIR/$PLUGIN_SLUG/" 2>/dev/null || true

# Créer readme.txt
if [ ! -f "readme.txt" ]; then
    cat > "$TEMP_DIR/$PLUGIN_SLUG/readme.txt" << 'EOF'
=== Verso Consultation Form ===
Contributors: Verso Vet
Tags: consultation, form, email
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 5.6
License: GPL v2 or later

Professional consultation form with email notifications and secure file uploads.
EOF
fi

# Créer le ZIP
cd "$TEMP_DIR"
zip -r "$PLUGIN_ZIP" "$PLUGIN_SLUG/" > /dev/null 2>&1
ZIP_PATH="$TEMP_DIR/$PLUGIN_ZIP"
ZIP_SIZE=$(du -h "$PLUGIN_ZIP" | cut -f1)
echo -e "${GREEN}✓ Plugin ZIP créé (${ZIP_SIZE})${NC}"
echo ""

# Créer multipart form data pour l'upload
echo -e "${YELLOW}▶ Upload du plugin via API...${NC}"

RESP=$(curl -s -u "$USER:$PASS" \
  -F "plugins=@$ZIP_PATH" \
  "$WP_SITE_URL/wp-admin/update.php?action=upload-plugin" \
  -H "Referer: $WP_SITE_URL/wp-admin/plugin-install.php?tab=upload")

# Vérifier la réponse
if echo "$RESP" | grep -q "Plugin installed successfully\|successfully activated"; then
  echo -e "${GREEN}✓ Plugin uploadé et activé${NC}"
elif echo "$RESP" | grep -q "Error"; then
  echo -e "${RED}✗ Erreur lors de l'upload${NC}"
  echo "$RESP" | grep -o "Error.*" | head -1
  exit 1
else
  echo -e "${YELLOW}⚠ Réponse de l'API:${NC}"
  echo "$RESP" | head -20
fi
echo ""

# Nettoyage
rm -rf "$TEMP_DIR"

# Résumé
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ Déploiement Terminé!                                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}✓ Formulaire accessible:${NC}"
echo "  https://verso-vet.com/demande-de-consultation/"
echo ""
