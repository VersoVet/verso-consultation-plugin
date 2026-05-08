#!/bin/bash

##############################################################################
# Outil de Déploiement verso-consultation-plugin
#
# RÈGLE STRICTE:
#   - Upload UNIQUEMENT les fichiers du plugin
#   - Aucune commande distante (pas de cache clear, pas de rm, rien)
#   - Pas de modification du comportement WordPress
#
# Usage:
#   export OVH_SSH_PASS='votre_mot_de_passe'
#   ./deploy-form.sh
##############################################################################

set -e

# Configuration OVH
OVH_SSH_HOST="ssh.cluster129.hosting.ovh.net"
OVH_SSH_USER="versovx-onyx"
PLUGIN_REMOTE="/homez.1657/versovx/www/wp-content/plugins/verso-consultation-plugin"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Vérifier que le mot de passe OVH est défini
if [ -z "$OVH_SSH_PASS" ]; then
    echo -e "${RED}❌ OVH_SSH_PASS non défini${NC}"
    echo "Usage: export OVH_SSH_PASS='votre_mot_de_passe' && ./deploy-form.sh"
    exit 1
fi

# En-tête
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Déploiement verso-consultation-plugin (sécurisé)         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Setup du SCP avec sshpass
SCP="sshpass -p $OVH_SSH_PASS scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null"

echo -e "${YELLOW}Cible:${NC} $PLUGIN_REMOTE"
echo ""

# Upload verso-consultation-plugin.php
echo -e "${YELLOW}▶${NC} Upload verso-consultation-plugin.php..."
if $SCP "$PLUGIN_DIR/verso-consultation-plugin.php" \
    "$OVH_SSH_USER@$OVH_SSH_HOST:$PLUGIN_REMOTE/" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} verso-consultation-plugin.php"
else
    echo -e "${RED}✗${NC} Erreur upload verso-consultation-plugin.php"
    exit 1
fi

# Upload js/form.js
echo -e "${YELLOW}▶${NC} Upload js/form.js..."
if $SCP "$PLUGIN_DIR/js/form.js" \
    "$OVH_SSH_USER@$OVH_SSH_HOST:$PLUGIN_REMOTE/js/" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} js/form.js"
else
    echo -e "${RED}✗${NC} Erreur upload js/form.js"
    exit 1
fi

# Upload css/style.css
echo -e "${YELLOW}▶${NC} Upload css/style.css..."
if $SCP "$PLUGIN_DIR/css/style.css" \
    "$OVH_SSH_USER@$OVH_SSH_HOST:$PLUGIN_REMOTE/css/" 2>/dev/null; then
    echo -e "${GREEN}✓${NC} css/style.css"
else
    echo -e "${RED}✗${NC} Erreur upload css/style.css"
    exit 1
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ Déploiement Terminé (sécurisé)                       ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "Fichiers déployés dans:"
echo "  $PLUGIN_REMOTE/"
echo ""
echo "Prochaines étapes:"
echo "  1. Vider le cache navigateur : Ctrl+Shift+R"
echo "  2. Tester le formulaire : https://verso-vet.com/demande-consultation/"
echo ""
