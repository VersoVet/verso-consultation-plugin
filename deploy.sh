#!/bin/bash

##############################################################################
# Script de Déploiement - verso-consultation-plugin
# Usage: ./deploy.sh
# Déploie les fichiers du plugin vers verso-vet.com via SFTP
##############################################################################

set -e

# Couleurs pour l'output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SSH_KEY="${HOME}/.ssh/id_rsa"
REMOTE_USER="onyx"
REMOTE_HOST="verso-vet.com"
REMOTE_PATH="www/wp-content/plugins/verso-consultation-plugin"

# Vérifications préalables
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   Déploiement verso-consultation-plugin                    ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# 1. Vérifier les fichiers locaux
echo -e "${YELLOW}[1/4]${NC} Vérification des fichiers locaux..."
MISSING_FILES=0

for FILE in verso-consultation-plugin.php \
            includes/class-form-handler.php \
            includes/class-webhook-sender.php \
            includes/class-vault-client.php \
            js/form.js \
            css/style.css; do
    if [ ! -f "$PLUGIN_DIR/$FILE" ]; then
        echo -e "${RED}✗${NC} Fichier manquant: $FILE"
        MISSING_FILES=$((MISSING_FILES + 1))
    else
        echo -e "${GREEN}✓${NC} $FILE"
    fi
done

if [ $MISSING_FILES -gt 0 ]; then
    echo -e "${RED}Erreur: $MISSING_FILES fichier(s) manquant(s)${NC}"
    exit 1
fi

echo ""

# 2. Vérifier la connexion SSH
echo -e "${YELLOW}[2/4]${NC} Vérification de la connexion SSH..."
if ! ssh -i "$SSH_KEY" -o ConnectTimeout=5 "$REMOTE_USER@$REMOTE_HOST" "echo OK" &>/dev/null; then
    echo -e "${YELLOW}⚠${NC} SSH non disponible, utilisation de SFTP avec timeout..."
    USE_SFTP=1
else
    echo -e "${GREEN}✓${NC} Connexion SSH OK"
    USE_SFTP=0
fi

echo ""

# 3. Déployer les fichiers
echo -e "${YELLOW}[3/4]${NC} Déploiement des fichiers..."

if [ $USE_SFTP -eq 1 ]; then
    # Utiliser SFTP (plus robuste)
    SFTP_SCRIPT=$(mktemp)
    cat > "$SFTP_SCRIPT" << EOFSFTP
cd $REMOTE_PATH
put verso-consultation-plugin.php
put includes/class-form-handler.php includes/
put includes/class-webhook-sender.php includes/
put includes/class-vault-client.php includes/
put js/form.js js/
put css/style.css css/
bye
EOFSFTP

    if sftp -i "$SSH_KEY" -b "$SFTP_SCRIPT" "$REMOTE_USER@$REMOTE_HOST" >/dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} Fichiers déployés via SFTP"
        rm "$SFTP_SCRIPT"
    else
        echo -e "${RED}✗${NC} Erreur SFTP"
        rm "$SFTP_SCRIPT"
        exit 1
    fi
else
    # Utiliser SCP (plus rapide si SSH est OK)
    scp -i "$SSH_KEY" -r verso-consultation-plugin.php "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/" >/dev/null 2>&1
    scp -i "$SSH_KEY" -r includes/ "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/" >/dev/null 2>&1
    scp -i "$SSH_KEY" -r js/ "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/" >/dev/null 2>&1
    scp -i "$SSH_KEY" -r css/ "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/" >/dev/null 2>&1
    echo -e "${GREEN}✓${NC} Fichiers déployés via SCP"
fi

echo ""

# 4. Vider le cache WordPress (si possible)
echo -e "${YELLOW}[4/4]${NC} Nettoyage du cache..."
if [ $USE_SFTP -eq 0 ]; then
    ssh -i "$SSH_KEY" "$REMOTE_USER@$REMOTE_HOST" "cd $REMOTE_PATH/.. && wp cache flush" 2>/dev/null && \
        echo -e "${GREEN}✓${NC} Cache WordPress vidé" || \
        echo -e "${YELLOW}⚠${NC} Cache non vidé (wp-cli pas disponible)"
else
    echo -e "${YELLOW}⚠${NC} Cache à vider manuellement via WordPress admin"
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✓ Déploiement Terminé!                                  ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "URL du formulaire:"
echo "  https://verso-vet.com/demande-consultation/"
echo ""
echo "🔄 Recharge la page avec Ctrl+Shift+R pour vider le cache navigateur"
echo ""
