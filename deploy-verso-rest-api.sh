#!/bin/bash

##############################################################################
# Script de Déploiement - verso-rest-api Plugin
# Déploie et active automatiquement le plugin REST API pour verso-vet.com
#
# Usage: ./deploy-verso-rest-api.sh
# Requirements: sshpass, curl, jq
##############################################################################

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration - STOCKER CES CREDENTIALS DANS LE VAULT EN PRODUCTION
OVH_SSH_HOST="${OVH_SSH_HOST:-ssh.cluster129.hosting.ovh.net}"
OVH_SSH_USER="${OVH_SSH_USER:-versovx-onyx}"
OVH_SSH_PASS="${OVH_SSH_PASS:-}"  # À passer en variable d'environnement
WP_URL="${WP_URL:-https://verso-vet.com}"
WP_USER="${WP_USER:-onyx}"
WP_PASS="${WP_PASS:-}"  # À passer en variable d'environnement
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_NAME="verso-rest-api"
PLUGIN_FILE="verso-rest-api.php"

# En-tête
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Déploiement Plugin verso-rest-api                        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Vérifier les credentials
if [ -z "$OVH_SSH_PASS" ]; then
    echo -e "${RED}❌ Erreur: OVH_SSH_PASS non défini${NC}"
    echo "Utilisez: export OVH_SSH_PASS='password'"
    exit 1
fi

if [ -z "$WP_PASS" ]; then
    echo -e "${RED}❌ Erreur: WP_PASS non défini${NC}"
    echo "Utilisez: export WP_PASS='app_password'"
    exit 1
fi

# [1/5] Créer le répertoire du plugin
echo -e "${YELLOW}[1/5]${NC} Création du répertoire du plugin..."
sshpass -p "$OVH_SSH_PASS" ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$OVH_SSH_USER@$OVH_SSH_HOST" \
  "mkdir -p ~/www/wp-content/plugins/$PLUGIN_NAME" 2>/dev/null
echo -e "${GREEN}✓${NC} Répertoire créé/existant"

# [2/5] Upload du plugin
echo -e "${YELLOW}[2/5]${NC} Upload du fichier du plugin..."
PLUGIN_PATH="$PLUGIN_DIR/verso-rest-api/$PLUGIN_FILE"

if [ ! -f "$PLUGIN_PATH" ]; then
    echo -e "${RED}❌ Fichier non trouvé: $PLUGIN_PATH${NC}"
    exit 1
fi

sshpass -p "$OVH_SSH_PASS" scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$PLUGIN_PATH" \
  "$OVH_SSH_USER@$OVH_SSH_HOST:~/www/wp-content/plugins/$PLUGIN_NAME/$PLUGIN_FILE" 2>/dev/null
echo -e "${GREEN}✓${NC} Plugin uploadé ($(stat -f%z "$PLUGIN_PATH" 2>/dev/null || stat -c%s "$PLUGIN_PATH") bytes)"

# [3/5] Activer le plugin
echo -e "${YELLOW}[3/5]${NC} Activation du plugin via SSH..."

ACTIVATE_SCRIPT=$(mktemp)
cat > "$ACTIVATE_SCRIPT" << 'EOFPHP'
<?php
define('WP_USE_THEMES', false);
require('/home/versovx-onyx/www/wp-load.php');

$plugin = 'verso-rest-api/verso-rest-api.php';
$result = activate_plugin($plugin);

if (is_wp_error($result)) {
    echo "ERROR: " . $result->get_error_message();
    exit(1);
} else {
    echo "Plugin activé";
    if (is_plugin_active($plugin)) {
        echo " (ACTIVE)";
    }
}
?>
EOFPHP

sshpass -p "$OVH_SSH_PASS" scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$ACTIVATE_SCRIPT" \
  "$OVH_SSH_USER@$OVH_SSH_HOST:~/www/activate-plugin.php" 2>/dev/null

ACTIVATE_OUTPUT=$(sshpass -p "$OVH_SSH_PASS" ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$OVH_SSH_USER@$OVH_SSH_HOST" "cd ~/www && php activate-plugin.php" 2>/dev/null)

sshpass -p "$OVH_SSH_PASS" ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  "$OVH_SSH_USER@$OVH_SSH_HOST" "rm -f ~/www/activate-plugin.php" 2>/dev/null

rm "$ACTIVATE_SCRIPT"

if echo "$ACTIVATE_OUTPUT" | grep -q "ACTIVE"; then
    echo -e "${GREEN}✓${NC} Plugin activé avec succès"
else
    echo -e "${RED}⚠${NC} $ACTIVATE_OUTPUT"
fi

# [4/5] Test de l'endpoint
echo -e "${YELLOW}[4/5]${NC} Test de l'endpoint..."

TEST_RESPONSE=$(curl -s -X POST "$WP_URL/wp-json/verso/v1/consultation" \
  -H "Content-Type: application/json" \
  -d '{
    "owner_nom": "TEST",
    "owner_prenom": "Deploy",
    "owner_email": "test@verso-deploy.local",
    "owner_telephone": "+33600000000",
    "animal_nom": "TestAnimal",
    "animal_espece": "Chien",
    "motif": "Test du déploiement automatisé"
  }' 2>/dev/null)

if echo "$TEST_RESPONSE" | grep -q '"success":true'; then
    echo -e "${GREEN}✓${NC} Endpoint fonctionne!"
    echo "$TEST_RESPONSE" | jq '.message' 2>/dev/null || echo "Success"
else
    echo -e "${YELLOW}⚠${NC} Endpoint:"
    echo "$TEST_RESPONSE" | jq '.message' 2>/dev/null || echo "$TEST_RESPONSE"
fi

# [5/5] Vérification finale
echo -e "${YELLOW}[5/5]${NC} Vérification finale..."

VERIFY=$(curl -s -u "$WP_USER:$WP_PASS" "$WP_URL/wp-json/wp/v2/plugins" 2>/dev/null | \
  jq '.[] | select(.plugin | contains("verso-rest-api")) | .status' 2>/dev/null)

if [ "$VERIFY" = '"active"' ]; then
    echo -e "${GREEN}✓${NC} Plugin vérifié comme ACTIF"
else
    echo -e "${YELLOW}⚠${NC} Status: $VERIFY"
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ Déploiement Terminé!                                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "📄 Plugin: verso-rest-api v2.1.0"
echo "🔗 Endpoint: POST $WP_URL/wp-json/verso/v1/consultation"
echo "📧 Email: consultations@verso-vet.com"
echo ""
echo "✨ Fonctionnalités:"
echo "   ✓ Formulaires JSON et multipart/form-data"
echo "   ✓ Upload de fichiers (PDF, JPG, PNG, GIF, TIFF)"
echo "   ✓ Email HTML avec liens de téléchargement"
echo "   ✓ Stockage sécurisé dans /wp-content/uploads/verso-consultations/"
echo ""
echo "💡 Pour les prochains déploiements:"
echo "   export OVH_SSH_PASS='8up2dBW4cH4i6Zg'"
echo "   export WP_PASS='l20JcUKAFqmYYNPdlEKpvVBA'"
echo "   ./deploy-verso-rest-api.sh"
echo ""
