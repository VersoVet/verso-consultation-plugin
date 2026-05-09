#!/bin/bash
set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   verso-consultation-plugin - Mise à jour du formulaire    ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Get credentials
echo -e "${YELLOW}▶ Récupération des credentials...${NC}"
PASS=$(curl -s -H "X-Vault-Token: $ONYX_VAULT_TOKEN" http://10.0.0.44:8050/vault/wordpress_credentials | \
  python3 -c "import sys, json; print(json.load(sys.stdin).get('app_password', ''))" 2>/dev/null)

if [ -z "$PASS" ]; then
  echo -e "${RED}✗ Impossible de récupérer le mot de passe${NC}"
  exit 1
fi
echo -e "${GREEN}✓ Credentials chargés${NC}"
echo ""

# Find page
echo -e "${YELLOW}▶ Recherche de la page...${NC}"
PAGE_ID=$(curl -s -u onyx:"$PASS" "https://verso-vet.com/wp-json/wp/v2/pages?slug=demande-de-consultation" | \
  python3 -c "import sys, json; data=json.load(sys.stdin); print(data[0]['id'] if data else '')" 2>/dev/null)

if [ -z "$PAGE_ID" ]; then
  echo -e "${RED}✗ Page non trouvée${NC}"
  exit 1
fi
echo -e "${GREEN}✓ Page trouvée (ID: $PAGE_ID)${NC}"
echo ""

# Get current content
echo -e "${YELLOW}▶ Chargement du contenu...${NC}"
CONTENT=$(curl -s -u onyx:"$PASS" "https://verso-vet.com/wp-json/wp/v2/pages/$PAGE_ID" | \
  python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('content', {}).get('raw', ''))" 2>/dev/null)

if [ -z "$CONTENT" ]; then
  echo -e "${RED}✗ Erreur de lecture${NC}"
  exit 1
fi
echo -e "${GREEN}✓ Contenu chargé$(echo -n "$CONTENT" | wc -c) chars)${NC}"
echo ""

# Check if already updated
if echo "$CONTENT" | grep -q 'id="fichiers"'; then
  echo -e "${GREEN}✅ Le champ d'upload existe déjà!${NC}"
  PAGE_URL=$(curl -s -u onyx:"$PASS" "https://verso-vet.com/wp-json/wp/v2/pages/$PAGE_ID" | \
    python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('link', ''))" 2>/dev/null)
  echo "  URL: $PAGE_URL"
  exit 0
fi

# Add upload field
echo -e "${YELLOW}▶ Ajout du champ d'upload...${NC}"

UPLOAD_FIELD='<div style="margin-bottom: 35px;">
<h3 style="background: #1c2445; color: white; padding: 12px 16px; border-radius: 4px; margin: 0 0 20px 0; font-size: 16px;">5. Pièces Jointes (Optionnel)</h3>
<p style="font-size: 14px; color: #666; margin: 0 0 15px 0;">Joignez des photos ou documents utiles (max 5 fichiers, 10 MB chacun)</p>
<label style="display: block; margin-bottom: 8px; font-weight: bold;">Fichiers</label>
<input type="file" id="fichiers" name="fichiers" multiple style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
<div id="file-preview" style="margin-top: 10px;"></div>
</div>'

UPDATED=$(echo "$CONTENT" | sed "s|<!-- SUBMIT -->|<!-- SECTION 5: PIÈCES JOINTES -->${UPLOAD_FIELD}<!-- SUBMIT -->|")

if [ "$CONTENT" = "$UPDATED" ]; then
  echo -e "${RED}✗ Impossible d'ajouter le champ${NC}"
  exit 1
fi

echo -e "${GREEN}✓ Champ préparé${NC}"
echo ""

# Update page
echo -e "${YELLOW}▶ Mise à jour de la page...${NC}"

RESP=$(curl -s -X POST -u onyx:"$PASS" "https://verso-vet.com/wp-json/wp/v2/pages/$PAGE_ID" \
  -H "Content-Type: application/json" \
  -d "$(python3 -c "import json, sys; print(json.dumps({'content': '''$UPDATED'''}))")")

if echo "$RESP" | grep -q '"id"'; then
  echo -e "${GREEN}✓ Page mise à jour${NC}"
else
  echo -e "${RED}✗ Erreur: $RESP${NC}"
  exit 1
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ Formulaire mis à jour avec le champ d'upload!        ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

PAGE_URL=$(curl -s -u onyx:"$PASS" "https://verso-vet.com/wp-json/wp/v2/pages/$PAGE_ID" | \
  python3 -c "import sys, json; data=json.load(sys.stdin); print(data.get('link', ''))" 2>/dev/null)

echo -e "${YELLOW}Tester le formulaire:${NC}"
echo "  $PAGE_URL"
echo ""
