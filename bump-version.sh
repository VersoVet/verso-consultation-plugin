#!/bin/bash

##############################################################################
# verso-consultation-plugin - Gestionnaire de Version
#
# Usage:
#   ./bump-version.sh patch   # 3.2.0 → 3.2.1
#   ./bump-version.sh minor   # 3.2.1 → 3.3.0
#   ./bump-version.sh major   # 3.3.0 → 4.0.0
#
##############################################################################

set -e

if [ -z "$1" ]; then
    echo "Usage: ./bump-version.sh [patch|minor|major]"
    echo ""
    echo "Examples:"
    echo "  ./bump-version.sh patch   # 3.2.0 → 3.2.1"
    echo "  ./bump-version.sh minor   # 3.2.0 → 3.1.0"
    echo "  ./bump-version.sh major   # 3.2.0 → 4.0.0"
    exit 1
fi

BUMP_TYPE=$1

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Lire la version actuelle
CURRENT_VERSION=$(cat VERSION)
echo -e "${BLUE}Current version: ${GREEN}$CURRENT_VERSION${NC}"

# Parser la version
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

# Calculer la nouvelle version
case $BUMP_TYPE in
    patch)
        PATCH=$((PATCH + 1))
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    *)
        echo "Invalid bump type: $BUMP_TYPE"
        exit 1
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"
echo -e "${YELLOW}▶ Bump $BUMP_TYPE: ${GREEN}$CURRENT_VERSION${YELLOW} → ${GREEN}$NEW_VERSION${NC}"
echo ""

# Mettre à jour le fichier VERSION
echo "$NEW_VERSION" > VERSION
echo -e "${GREEN}✓ VERSION file updated${NC}"

# Mettre à jour le header du plugin
sed -i "s/ \* Version: $CURRENT_VERSION/ \* Version: $NEW_VERSION/" verso-consultation-plugin.php
echo -e "${GREEN}✓ Plugin header updated${NC}"

# Mettre à jour le manifest.json
sed -i "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" manifest.json 2>/dev/null || true
echo -e "${GREEN}✓ Manifest updated${NC}"

# Mettre à jour le TODO.md
sed -i "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/" TODO.md 2>/dev/null || true

# Mettre à jour CLAUDE.md
sed -i "s/ v$CURRENT_VERSION/ v$NEW_VERSION/" CLAUDE.md 2>/dev/null || true

echo ""

# Créer le commit
echo -e "${YELLOW}▶ Git operations...${NC}"
git add VERSION verso-consultation-plugin.php manifest.json TODO.md CLAUDE.md 2>/dev/null || true
git commit -m "chore: Bump version to $NEW_VERSION

- Updated VERSION file
- Updated plugin header (Version: $NEW_VERSION)
- Updated manifest.json
- Updated documentation

Co-Authored-By: Claude Haiku 4.5 <noreply@anthropic.com>" || echo "Nothing to commit"

# Créer un tag git
git tag -a "v$NEW_VERSION" -m "Release version $NEW_VERSION" 2>/dev/null || echo "Tag already exists"
echo -e "${GREEN}✓ Git tag: v$NEW_VERSION${NC}"

echo ""

# Créer le ZIP avec version dans le nom
echo -e "${YELLOW}▶ Building distribution...${NC}"
rm -rf dist/verso-consultation-plugin dist/verso-consultation-plugin*.zip
mkdir -p dist/verso-consultation-plugin

cp verso-consultation-plugin.php dist/verso-consultation-plugin/
cp -r js/ dist/verso-consultation-plugin/ 2>/dev/null || true
cp -r css/ dist/verso-consultation-plugin/ 2>/dev/null || true
cp readme.txt dist/verso-consultation-plugin/ 2>/dev/null || true

cd dist
ZIP_NAME="verso-consultation-plugin-v${NEW_VERSION}.zip"
zip -r "$ZIP_NAME" verso-consultation-plugin/ > /dev/null 2>&1
ZIP_SIZE=$(du -h "$ZIP_NAME" | cut -f1)
echo -e "${GREEN}✓ ZIP created: ${ZIP_NAME} (${ZIP_SIZE})${NC}"
# Also create symlink for latest
ln -sf "$ZIP_NAME" verso-consultation-plugin.zip
cd ..

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   ✅ Version Bump Complete!                               ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "  1. Verify changes: git log --oneline -3"
echo "  2. Deploy: ./deploy-safe.sh"
echo "  3. Verify on WordPress: https://verso-vet.com/wp-admin/plugins.php"
echo ""
