#!/bin/bash

##############################################################################
# Script de Création de Pages Divi - verso-vet.com
# Crée une page WordPress avec un template Divi isolé et sûr
#
# Usage:
#   ./create-page.sh "Titre de la page" gradient "Sous-titre optionnel"
#   ./create-page.sh "Titre de la page" image "https://url-image.jpg" "Sous-titre optionnel"
#
# Options:
#   gradient : Fond bleu dégradé (#1c2445 → #0e0e0e)
#   image    : Fond avec image + overlay noir 0.8 opacity
#
##############################################################################

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATES_DIR="$SCRIPT_DIR/templates"

# Get WordPress credentials from Vault
if [ -z "$ONYX_VAULT_TOKEN" ]; then
    echo -e "${RED}❌ ONYX_VAULT_TOKEN not set${NC}"
    exit 1
fi

WP_CREDS=$(curl -s -H "X-Vault-Token: $ONYX_VAULT_TOKEN" http://10.0.0.44:8050/vault/wordpress_credentials)
WP_URL=$(echo "$WP_CREDS" | python3 -c "import json, sys; d=json.load(sys.stdin); print(json.loads(d['value'])['site_url'])" 2>/dev/null)
WP_USER=$(echo "$WP_CREDS" | python3 -c "import json, sys; d=json.load(sys.stdin); print(json.loads(d['value'])['username'])" 2>/dev/null)
WP_PASS=$(echo "$WP_CREDS" | python3 -c "import json, sys; d=json.load(sys.stdin); print(json.loads(d['value'])['app_password'])" 2>/dev/null)

if [ -z "$WP_USER" ] || [ -z "$WP_PASS" ]; then
    echo -e "${RED}❌ Failed to retrieve WordPress credentials from Vault${NC}"
    exit 1
fi

# En-tête
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Création de Page Divi - verso-vet.com                   ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Vérifier les arguments
if [ $# -lt 2 ]; then
    echo -e "${RED}Usage:${NC}"
    echo "  ./create-page.sh \"Titre\" gradient [sous-titre]"
    echo "  ./create-page.sh \"Titre\" image URL_IMAGE [sous-titre]"
    echo ""
    echo "Exemples:"
    echo "  ./create-page.sh \"Ma page\" gradient"
    echo "  ./create-page.sh \"Services\" image https://verso-vet.com/...photo.jpg"
    exit 1
fi

TITLE="$1"
TEMPLATE_TYPE="$2"

# Valider le type de template
if [ "$TEMPLATE_TYPE" != "gradient" ] && [ "$TEMPLATE_TYPE" != "image" ]; then
    echo -e "${RED}❌ Type invalide: $TEMPLATE_TYPE${NC}"
    echo "   Utilisez: gradient ou image"
    exit 1
fi

# Traiter les paramètres selon le type
if [ "$TEMPLATE_TYPE" = "gradient" ]; then
    SUBTITLE="${3:-}"
    TEMPLATE_FILE="$TEMPLATES_DIR/template-hero-gradient.txt"

    if [ ! -f "$TEMPLATE_FILE" ]; then
        echo -e "${RED}❌ Template non trouvé: $TEMPLATE_FILE${NC}"
        exit 1
    fi

    echo -e "${YELLOW}[1/3]${NC} Chargement du template gradient..."
    CONTENT=$(cat "$TEMPLATE_FILE")

elif [ "$TEMPLATE_TYPE" = "image" ]; then
    if [ -z "$3" ]; then
        echo -e "${RED}❌ URL de l'image manquante${NC}"
        echo "Usage: ./create-page.sh \"Titre\" image URL_IMAGE [sous-titre]"
        exit 1
    fi

    IMAGE_URL="$3"
    SUBTITLE="${4:-}"
    TEMPLATE_FILE="$TEMPLATES_DIR/template-hero-image.txt"

    if [ ! -f "$TEMPLATE_FILE" ]; then
        echo -e "${RED}❌ Template non trouvé: $TEMPLATE_FILE${NC}"
        exit 1
    fi

    echo -e "${YELLOW}[1/3]${NC} Chargement du template image..."
    CONTENT=$(cat "$TEMPLATE_FILE")

    # Remplacer l'URL d'image
    CONTENT="${CONTENT//\{\{IMAGE_URL\}\}/$IMAGE_URL}"
fi

# Remplacer les variables
CONTENT="${CONTENT//\{\{TITLE\}\}/$TITLE}"
CONTENT="${CONTENT//\{\{SUBTITLE\}\}/$SUBTITLE}"
CONTENT="${CONTENT//\{\{CONTENT\}\}/<h2>À compléter</h2><p>Contenu à ajouter</p>}"

# Générer le slug (nom de la page dans l'URL)
SLUG=$(echo "$TITLE" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]/-/g' | sed 's/-+/-/g' | sed 's/^-\|-$//')

echo -e "${GREEN}✓${NC} Template chargé"
echo ""

# [2/3] Créer la page via l'API WordPress
echo -e "${YELLOW}[2/3]${NC} Création de la page WordPress..."

# Préparer le payload JSON
read -r -d '' PAYLOAD << EOF || true
{
  "title": "$TITLE",
  "slug": "$SLUG",
  "content": $(echo "$CONTENT" | python3 -c "import json, sys; print(json.dumps(sys.stdin.read()))"),
  "status": "publish",
  "meta": {
    "_et_pb_use_builder": "on",
    "_et_pb_old_content": "<!-- wp:divi/placeholder /-->"
  }
}
EOF

# Envoyer la requête
RESPONSE=$(curl -s -X POST \
    -u "$WP_USER:$WP_PASS" \
    "$WP_URL/wp-json/wp/v2/pages" \
    -H "Content-Type: application/json" \
    -d "$PAYLOAD")

# Parser la réponse
PAGE_ID=$(echo "$RESPONSE" | python3 -c "
import json, sys
try:
    data = json.load(sys.stdin)
    if 'id' in data:
        print(data['id'])
    else:
        print('ERROR: ' + data.get('message', str(data)))
except:
    print('ERROR: Réponse invalide')
" 2>/dev/null)

if [[ "$PAGE_ID" =~ ^ERROR ]]; then
    echo -e "${RED}❌ Erreur lors de la création:${NC}"
    echo "$PAGE_ID"
    exit 1
fi

echo -e "${GREEN}✓${NC} Page créée (ID: $PAGE_ID)"
echo ""

# [3/3] Vérifier que les shortcodes Divi sont présents
echo -e "${YELLOW}[3/3]${NC} Vérification des shortcodes Divi..."

VERIFY=$(curl -s -u "$WP_USER:$WP_PASS" \
    "$WP_URL/wp-json/wp/v2/pages/$PAGE_ID?context=edit" | \
    python3 -c "
import json, sys
data = json.load(sys.stdin)
raw = data.get('content', {}).get('raw', '')
if 'et_pb_section' in raw:
    print('OK')
else:
    print('FAIL')
")

if [ "$VERIFY" = "OK" ]; then
    echo -e "${GREEN}✓${NC} Shortcodes Divi détectés"
else
    echo -e "${YELLOW}⚠${NC}  Avertissement: shortcodes Divi non détectés"
fi

echo ""

# Résumé
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ Page créée avec succès!                               ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "📄 Page: $TITLE"
echo "🔗 URL: $WP_URL/$SLUG/"
echo "📊 ID: $PAGE_ID"
echo "🎨 Template: $TEMPLATE_TYPE"
echo ""
echo "ℹ️  Garanties d'isolation:"
echo "   ✓ Seule cette page est modifiée"
echo "   ✓ Pas de modification des paramètres globaux"
echo "   ✓ Pas de modules Divi partagés"
echo "   ✓ Couleurs en hex direct (#1c2445, etc.)"
echo ""
