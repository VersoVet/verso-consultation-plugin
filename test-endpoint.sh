#!/bin/bash

##############################################################################
# Script de Test - verso-rest-api Endpoint
# Teste l'endpoint /wp-json/verso/v1/submit avec et sans fichiers
#
# Usage:
#   ./test-endpoint.sh [mode] [email] [animal_name]
#   mode: "json" (test JSON simple) ou "form" (test avec fichiers)
##############################################################################

# Configuration
WP_URL="${WP_URL:-https://verso-vet.com}"
TEST_MODE="${1:-json}"
TEST_EMAIL="${2:-test@verso-deploy.local}"
TEST_ANIMAL="${3:-TestDog}"

echo "🧪 Test de l'endpoint verso-rest-api"
echo "════════════════════════════════════════════════"
echo "URL: $WP_URL/wp-json/verso/v1/consultation"
echo "Mode: $TEST_MODE"
echo ""

if [ "$TEST_MODE" = "form" ]; then
    echo "📄 Mode: Formulaire multipart avec fichier test"
    echo ""

    # Créer un fichier test temporaire
    TEST_FILE=$(mktemp)
    echo "Ceci est un fichier test pour le téléchargement" > "$TEST_FILE"

    echo "📨 Envoi du formulaire avec fichier..."
    echo ""

    # Envoyer la requête multipart/form-data
    RESPONSE=$(curl -s -X POST "$WP_URL/wp-json/verso/v1/consultation" \
      -F "owner_nom=TestUser" \
      -F "owner_prenom=Deploy" \
      -F "owner_email=$TEST_EMAIL" \
      -F "owner_telephone=+33600000000" \
      -F "owner_address=123 Rue Test, 75000 Paris" \
      -F "vet_nom=Dr. Test" \
      -F "vet_prenom=Jules" \
      -F "vet_clinique=Clinique Test" \
      -F "vet_email=dr.test@clinique-test.fr" \
      -F "vet_telephone=+33612345678" \
      -F "vet_address=456 Avenue Vétérinaire, 75001 Paris" \
      -F "animal_nom=$TEST_ANIMAL" \
      -F "animal_espece=Chien" \
      -F "animal_race=Labrador" \
      -F "motif=Test automatisé du déploiement avec fichiers" \
      -F "documents=@$TEST_FILE")

    # Nettoyer le fichier test
    rm "$TEST_FILE"

else
    echo "📋 Mode: JSON simple (sans fichiers)"
    echo ""

    # Construire le payload JSON
    PAYLOAD=$(cat <<EOF
{
  "owner_nom": "TestUser",
  "owner_prenom": "Deploy",
  "owner_email": "$TEST_EMAIL",
  "owner_telephone": "+33600000000",
  "owner_address": "123 Rue Test, 75000 Paris",
  "vet_nom": "Dr. Test",
  "vet_prenom": "Jules",
  "vet_clinique": "Clinique Test",
  "vet_email": "dr.test@clinique-test.fr",
  "vet_telephone": "+33612345678",
  "vet_address": "456 Avenue Vétérinaire, 75001 Paris",
  "animal_nom": "$TEST_ANIMAL",
  "animal_espece": "Chien",
  "animal_race": "Labrador",
  "motif": "Test automatisé du déploiement"
}
EOF
)

    echo "📨 Envoi du test JSON..."
    echo ""

    # Envoyer la requête
    RESPONSE=$(curl -s -X POST "$WP_URL/wp-json/verso/v1/consultation" \
      -H "Content-Type: application/json" \
      -d "$PAYLOAD")
fi

# Afficher la réponse
echo "Réponse:"
echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"

echo ""

# Vérifier le succès
if echo "$RESPONSE" | grep -q '"success":true'; then
    echo "✅ TEST RÉUSSI!"
    echo ""
    echo "L'email a été envoyé à: $TEST_EMAIL"
    echo "Pour l'animal: $TEST_ANIMAL"
    echo ""
    if echo "$RESPONSE" | grep -q '"uuid"'; then
        UUID=$(echo "$RESPONSE" | jq -r '.uuid' 2>/dev/null)
        echo "ID de demande: $UUID"
    fi
    exit 0
else
    echo "❌ TEST ÉCHOUÉ"
    echo ""
    echo "Vérifiez:"
    echo "  1. L'endpoint est accessible: $WP_URL/wp-json/"
    echo "  2. Le plugin verso-rest-api est activé"
    echo "  3. WordPress mail est configuré"
    echo ""
    echo "Usage:"
    echo "  Mode JSON:  ./test-endpoint.sh json [email] [animal]"
    echo "  Mode Form:  ./test-endpoint.sh form [email] [animal]"
    exit 1
fi
