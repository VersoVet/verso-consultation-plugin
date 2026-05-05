#!/bin/bash

##############################################################################
# Script de Déploiement - verso-consultation-plugin
# Déploie le formulaire WordPress avec gestion complète du cache
#
# Usage:
#   export OVH_SSH_PASS='password'
#   ./deploy-form.sh
##############################################################################

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
OVH_SSH_HOST="${OVH_SSH_HOST:-ssh.cluster129.hosting.ovh.net}"
OVH_SSH_USER="${OVH_SSH_USER:-versovx-onyx}"
OVH_SSH_PASS="${OVH_SSH_PASS:-}"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_NAME="verso-consultation-plugin"
PLUGIN_FILE="verso-consultation-plugin.php"

# En-tête
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Déploiement verso-consultation-plugin (Formulaire)      ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Vérifier les credentials
if [ -z "$OVH_SSH_PASS" ]; then
    echo -e "${RED}❌ Erreur: OVH_SSH_PASS non défini${NC}"
    echo "Utilisez: export OVH_SSH_PASS='password'"
    exit 1
fi

# [1/5] Vérifier le fichier local
echo -e "${YELLOW}[1/5]${NC} Vérification du fichier local..."
PLUGIN_PATH="$PLUGIN_DIR/$PLUGIN_FILE"

if [ ! -f "$PLUGIN_PATH" ]; then
    echo -e "${RED}❌ Fichier non trouvé: $PLUGIN_PATH${NC}"
    exit 1
fi

PLUGIN_SIZE=$(stat -c%s "$PLUGIN_PATH")
echo -e "${GREEN}✓${NC} Fichier trouvé ($PLUGIN_SIZE bytes)"
echo ""

# [2/5] Supprimer le cache local
echo -e "${YELLOW}[2/5]${NC} Suppression du cache OVH..."
sshpass -p "$OVH_SSH_PASS" ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$OVH_SSH_USER@$OVH_SSH_HOST" \
  "rm -rf ~/www/wp-content/cache/* ~/www/.cache/* ~/www/wp-cache/* 2>/dev/null; echo OK" > /dev/null 2>&1
echo -e "${GREEN}✓${NC} Cache supprimé"
echo ""

# [3/5] Supprimer le fichier ancien sur le serveur
echo -e "${YELLOW}[3/5]${NC} Suppression du fichier ancien..."
sshpass -p "$OVH_SSH_PASS" ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$OVH_SSH_USER@$OVH_SSH_HOST" \
  "rm -f ~/www/wp-content/plugins/$PLUGIN_NAME/$PLUGIN_FILE" 2>/dev/null
echo -e "${GREEN}✓${NC} Ancien fichier supprimé"
echo ""

# [4/5] Upload du nouveau fichier
echo -e "${YELLOW}[4/5]${NC} Upload du nouveau fichier..."
sshpass -p "$OVH_SSH_PASS" scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$PLUGIN_PATH" \
  "$OVH_SSH_USER@$OVH_SSH_HOST:~/www/wp-content/plugins/$PLUGIN_NAME/$PLUGIN_FILE" 2>/dev/null
echo -e "${GREEN}✓${NC} Fichier uploadé"
echo ""

# [5/5] Purge totale du cache WordPress
echo -e "${YELLOW}[5/5]${NC} Purge complète du cache WordPress..."
sshpass -p "$OVH_SSH_PASS" ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$OVH_SSH_USER@$OVH_SSH_HOST" \
  "cd ~/www && \
   find . -name '*.tmp' -delete; \
   find wp-content -type d -name 'cache' -exec rm -rf {} + 2>/dev/null; \
   php -r \"@unlink('.htaccess.backup');\" 2>/dev/null; \
   echo OK" > /dev/null 2>&1
echo -e "${GREEN}✓${NC} Cache WordPress purgé"
echo ""

# Résumé
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ Déploiement Terminé!                                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "📄 Plugin: verso-consultation-plugin"
echo "🔗 URL: https://verso-vet.com/demande-consultation/"
echo ""
echo "🔄 Pour vider le cache navigateur:"
echo "   • Windows/Linux: Ctrl + Shift + Delete"
echo "   • Mac: Cmd + Shift + Delete"
echo "   • Ou faire un hard refresh: Ctrl+F5 (ou Cmd+Shift+R sur Mac)"
echo ""
echo "📝 Prochains déploiements:"
echo "   export OVH_SSH_PASS='password'"
echo "   ./deploy-form.sh"
echo ""
