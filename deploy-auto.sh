#!/bin/bash

##############################################################################
# verso-consultation-plugin - Déploiement Automatisé via WP-CLI
#
# Usage:
#   ./deploy-auto.sh                      # Utilise variables d'env
#   WP_CLI_PATH=/path/to/wp ./deploy-auto.sh
#
# Variables d'environnement requises:
#   WP_CLI_PATH      = Chemin vers WordPress CLI (défaut: wp)
#   WP_SITE_URL      = URL du site WordPress (défaut: https://verso-vet.com)
#   WP_ADMIN_USER    = Utilisateur admin (défaut: onyx)
#
##############################################################################

set -e

# Configuration
WP_CLI_PATH="${WP_CLI_PATH:-wp}"
WP_SITE_URL="${WP_SITE_URL:-https://verso-vet.com}"
WP_ADMIN_USER="${WP_ADMIN_USER:-onyx}"
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
echo -e "${BLUE}║   verso-consultation-plugin - Déploiement Automatisé       ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Vérifier les prérequis
echo -e "${YELLOW}▶ Vérification des prérequis...${NC}"

if ! command -v $WP_CLI_PATH &> /dev/null; then
    echo -e "${RED}✗ WP-CLI non trouvé: $WP_CLI_PATH${NC}"
    echo "  Installez WP-CLI: https://wp-cli.org/#installing"
    exit 1
fi
echo -e "${GREEN}✓ WP-CLI trouvé${NC}"

if [ ! -f "verso-consultation-plugin.php" ]; then
    echo -e "${RED}✗ verso-consultation-plugin.php non trouvé${NC}"
    echo "  Lancez ce script depuis le répertoire du plugin"
    exit 1
fi
echo -e "${GREEN}✓ Fichiers du plugin trouvés${NC}"
echo ""

# Étape 1: Créer le ZIP
echo -e "${YELLOW}▶ Création du ZIP du plugin...${NC}"
TEMP_DIR="/tmp/verso-plugin-$$"
mkdir -p "$TEMP_DIR/$PLUGIN_SLUG"

cp verso-consultation-plugin.php "$TEMP_DIR/$PLUGIN_SLUG/"
cp -r js/ "$TEMP_DIR/$PLUGIN_SLUG/" 2>/dev/null || true
cp -r css/ "$TEMP_DIR/$PLUGIN_SLUG/" 2>/dev/null || true

# Créer readme.txt s'il n'existe pas
if [ ! -f "$TEMP_DIR/$PLUGIN_SLUG/readme.txt" ]; then
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
ZIP_SIZE=$(du -h "$PLUGIN_ZIP" | cut -f1)
echo -e "${GREEN}✓ Plugin ZIP créé (${ZIP_SIZE})${NC}"
echo ""

# Étape 2: Déployer via WP-CLI
echo -e "${YELLOW}▶ Déploiement du plugin...${NC}"
echo "  Site: $WP_SITE_URL"
echo "  Plugin: $PLUGIN_SLUG"
echo ""

# Supprimer la version ancienne si elle existe
echo -e "${YELLOW}▶ Suppression de la version existante...${NC}"
if $WP_CLI_PATH plugin list --url="$WP_SITE_URL" | grep -q "$PLUGIN_SLUG"; then
    $WP_CLI_PATH plugin deactivate "$PLUGIN_SLUG" --url="$WP_SITE_URL" 2>/dev/null || true
    $WP_CLI_PATH plugin delete "$PLUGIN_SLUG" --url="$WP_SITE_URL" 2>/dev/null || true
    echo -e "${GREEN}✓ Version existante supprimée${NC}"
else
    echo -e "${YELLOW}  (Aucune version existante)${NC}"
fi
echo ""

# Installer la nouvelle version
echo -e "${YELLOW}▶ Installation de la nouvelle version...${NC}"
$WP_CLI_PATH plugin install "$TEMP_DIR/$PLUGIN_ZIP" \
    --url="$WP_SITE_URL" \
    --activate
echo -e "${GREEN}✓ Plugin installé et activé${NC}"
echo ""

# Étape 3: Vérifier l'installation
echo -e "${YELLOW}▶ Vérification de l'installation...${NC}"
if $WP_CLI_PATH plugin list --url="$WP_SITE_URL" | grep -q "verso-consultation-plugin.*active"; then
    echo -e "${GREEN}✓ Plugin est ACTIF${NC}"
else
    echo -e "${RED}✗ Le plugin n'est pas actif!${NC}"
    exit 1
fi
echo ""

# Étape 4: Vérifier la page
echo -e "${YELLOW}▶ Vérification de la page de consultation...${NC}"
PAGE_ID=$($WP_CLI_PATH post list \
    --url="$WP_SITE_URL" \
    --post_type=page \
    --name=demande-de-consultation \
    --field=ID \
    --format=ids)

if [ -n "$PAGE_ID" ]; then
    PAGE_URL=$($WP_CLI_PATH post get $PAGE_ID --url="$WP_SITE_URL" --field=url)
    echo -e "${GREEN}✓ Page trouvée${NC}"
    echo "  URL: $PAGE_URL"
else
    echo -e "${YELLOW}⚠ Page 'demande-de-consultation' non trouvée${NC}"
    echo "  Créez-la manuellement ou relancez avec --create-page"
fi
echo ""

# Nettoyage
rm -rf "$TEMP_DIR"

# Résumé
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ Déploiement Réussi!                                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Prochaines étapes:${NC}"
echo "  1. Vérifiez le formulaire: $WP_SITE_URL/demande-de-consultation/"
echo "  2. Ajoutez le champ d'upload (voir DEPLOY.md)"
echo "  3. Testez l'envoi d'une consultation"
echo ""
