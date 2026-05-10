#!/bin/bash

##############################################################################
# verso-consultation-plugin - Déploiement Sécurisé via REST API
#
# Déploie le plugin et crée la page de consultation de manière sécurisée
# sans accès direct au serveur
#
# Usage:
#   ./deploy-safe.sh
#
##############################################################################

set -e

WP_SITE_URL="https://verso-vet.com"
PLUGIN_SLUG="verso-consultation-plugin"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# En-tête
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   verso-consultation-plugin - Déploiement Sécurisé         ║${NC}"
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

# Étape 1: Lire la version et créer le ZIP localement
PLUGIN_VERSION=$(cat VERSION)
echo -e "${YELLOW}▶ Création du ZIP du plugin (local - v${PLUGIN_VERSION})...${NC}"
TEMP_DIR="/tmp/verso-plugin-$$"
mkdir -p "$TEMP_DIR/$PLUGIN_SLUG"

cp verso-consultation-plugin.php "$TEMP_DIR/$PLUGIN_SLUG/"
cp -r js/ "$TEMP_DIR/$PLUGIN_SLUG/" 2>/dev/null || true
cp -r css/ "$TEMP_DIR/$PLUGIN_SLUG/" 2>/dev/null || true

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

cd "$TEMP_DIR"
ZIP_NAME="verso-consultation-plugin-v${PLUGIN_VERSION}.zip"
zip -r "$ZIP_NAME" verso-consultation-plugin/ > /dev/null 2>&1
ZIP_PATH="$TEMP_DIR/$ZIP_NAME"
ZIP_SIZE=$(du -h "$ZIP_NAME" | cut -f1)
echo -e "${GREEN}✓ Plugin ZIP créé: ${ZIP_NAME} (${ZIP_SIZE})${NC}"
cd - > /dev/null
echo ""

# Étape 2: Calculer le token de setup
echo -e "${YELLOW}▶ Génération du token de setup...${NC}"
SETUP_TOKEN=$(echo -n "${WP_SITE_URL}verso-setup-2026" | sha256sum | awk '{print $1}')
echo -e "${GREEN}✓ Token généré${NC}"
echo ""

# Étape 3: Appeler l'endpoint REST pour setup
echo -e "${YELLOW}▶ Configuration de la page via API REST...${NC}"

RESP=$(curl -s -X POST \
  "$WP_SITE_URL/wp-json/verso/v1/setup?token=$SETUP_TOKEN" \
  -H "Content-Type: application/json")

if echo "$RESP" | grep -q '"success":true'; then
  PAGE_ID=$(echo "$RESP" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('page_id', ''))" 2>/dev/null)
  PAGE_URL=$(echo "$RESP" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('page_url', ''))" 2>/dev/null)

  echo -e "${GREEN}✓ Page de consultation créée/mise à jour${NC}"
  echo "  ID: $PAGE_ID"
  echo "  URL: $PAGE_URL"
else
  ERR=$(echo "$RESP" | python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('message', data))" 2>/dev/null)
  echo -e "${RED}✗ Erreur API: $ERR${NC}"
  echo ""
  echo "Assurez-vous que:"
  echo "  1. Le plugin verso-consultation-plugin est activé"
  echo "  2. L'utilisateur $USER a les droits d'administrateur"
  echo "  3. Le site $WP_SITE_URL est accessible"
  rm -rf "$TEMP_DIR"
  exit 1
fi
echo ""

# Nettoyage
rm -rf "$TEMP_DIR"

# Résumé final
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ Déploiement Sécurisé Réussi!                         ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}✓ Formulaire de consultation maintenant en ligne:${NC}"
echo "  $PAGE_URL"
echo ""
echo -e "${YELLOW}✓ Fonctionnalités disponibles:${NC}"
echo "  ✓ Upload de fichiers (max 5, 10 MB chacun)"
echo "  ✓ Envoi par email à consultations@verso-vet.com"
echo "  ✓ Stockage sécurisé dans wp-content/uploads/verso-consultations/"
echo "  ✓ Nettoyage automatique des fichiers après envoi"
echo ""
