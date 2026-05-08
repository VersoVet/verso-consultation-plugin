# Guide : Création de Pages Divi - verso-vet.com

## Résumé

Script de création de pages Divi **isolées et sûres** pour verso-vet.com. Aucune modification possible des paramètres globaux ou des autres pages.

**Emplacement du script :** `/home/onyx/projects/skills/verso-consultation-plugin/create-page.sh`

---

## Démarrage Rapide

### Créer une page avec fond bleu gradient

```bash
cd /home/onyx/projects/skills/verso-consultation-plugin
./create-page.sh "Titre de la page" gradient "Sous-titre optionnel"
```

**Exemple:**
```bash
./create-page.sh "Services" gradient "Découvrez nos services vétérinaires"
```

### Créer une page avec fond image + overlay noir

```bash
./create-page.sh "Titre de la page" image "https://verso-vet.com/.../image.jpg" "Sous-titre optionnel"
```

**Exemple:**
```bash
./create-page.sh "Chirurgie" image "https://verso-vet.com/wp-content/uploads/2025/01/chirurgie.jpg" "Nos techniques chirurgicales"
```

---

## Templates Disponibles

### 1. **template-hero-gradient.txt** — Fond bleu gradient

**Utilisation :**
```bash
./create-page.sh "Ma page" gradient
```

**Résultat :**
- Fond : dégradé bleu (#1c2445 → #0e0e0e) à 165°
- Titre : blanc, Barlow 700, 48px, centré
- Sous-titre : blanc, Barlow regular
- Contenu : section blanche standard

**Utilisé sur :**
- Page d'accueil
- Pages de template principale

---

### 2. **template-hero-image.txt** — Fond image + overlay sombre

**Utilisation :**
```bash
./create-page.sh "Services" image "https://verso-vet.com/image.jpg"
```

**Résultat :**
- Fond : image avec overlay noir (opacity 0.8) + blend mode `darken`
- Titre : blanc, Barlow 700, 48px, centré
- Sous-titre : blanc, Barlow regular
- Contenu : section blanche standard

**Utilisé sur :**
- Pages spécialités (Chirurgie, Imagerie, etc.)
- Pages praticiens

---

## Comment ça fonctionne

### Structure correcte d'une page

```
CONTENU = [Shortcodes Divi SEULS] + [HTML pur HORS shortcodes]
```

**Exemple réel (page consultation-refere):**
```
[et_pb_section...]                    ← Hero Divi (fond bleu)
  [et_pb_row...]
    [et_pb_text...]<h1>Titre</h1>...
  [/et_pb_row]
[/et_pb_section]                      ← FIN des shortcodes Divi

<div>                                 ← HTML pur APRÈS les shortcodes
  <form>...</form>
  <script>...</script>
</div>
```

**Règle critique:** Ne pas mettre d'HTML pur DANS les shortcodes Divi. Terminer les shortcodes `[/et_pb_section]` puis mettre le reste en HTML.

### Les 3 étapes du script

```
[1/3] Charger le template (fichier texte)
[2/3] Créer la page via WordPress REST API
  ↳ POST /wp/v2/pages avec:
     - title: "Titre de la page"
     - slug: "titre-de-la-page"
     - content: "[et_pb_section...]...[/et_pb_section]<div>HTML pur...</div>"
     - status: "publish"
     - meta:
         _et_pb_use_builder: "on"          ← OBLIGATOIRE
         _et_pb_old_content: "<!-- wp:divi/placeholder /-->"
[3/3] Vérifier que les shortcodes Divi sont présents
```

### Pourquoi `_et_pb_use_builder: "on"` + séparation Divi/HTML

**Divi rend correctement UNIQUEMENT si:**
1. ✅ `_et_pb_use_builder: "on"` est défini (active Divi)
2. ✅ Shortcodes Divi sont seuls (pas d'HTML pur mélangé dedans)
3. ✅ HTML pur est APRÈS `[/et_pb_section]` (complètement en dehors)

Sans cette structure :
- ❌ Divi ne rend pas les shortcodes
- ❌ Le fond bleu ne s'affiche pas
- ❌ Les styles CSS Divi ne s'appliquent pas

**Le script respecte automatiquement cette structure!**

---

## Garanties d'Isolation

✅ **Ces garanties sont techniquement appliquées :**

| Guarantee | How | Evidence |
|-----------|-----|----------|
| Seule la page cible est modifiée | API: `POST /wp/v2/pages` (insertion uniquement) | Aucune autre page touchée |
| Pas de paramètres globaux modifiés | Pas de `PUT` sur les settings/options WordPress | Themes, colors, modules inchangés |
| Pas de modules Divi partagés | Shortcodes sans `global_module="ID"` | Vérifié lors de création |
| Couleurs hex directes (pas de gcid-*) | Templates utilisent `#1c2445` not `gcid-c9469228` | Aucune référence globale |

### Comment c'est garanti

1. **Script = POST uniquement** → création de page, pas de modification
2. **Templates = shortcodes isolés** → pas de `[et_pb_global_module]`, pas de `gcid-*`
3. **Couleurs = valeurs directes** → `#1c2445`, `#FFFFFF`, `#333333` (pas de références)
4. **Vérification auto** → script vérifie shortcodes Divi présents après création

---

## Variables de Template

Les templates utilisent des variables remplacées automatiquement :

| Variable | Remplacée par | Exemple |
|----------|---|---------|
| `{{TITLE}}` | Titre de la page | "Chirurgie Orthopédique" |
| `{{SUBTITLE}}` | Sous-titre du hero | "Techniques minimales invasives" |
| `{{IMAGE_URL}}` | URL d'une image | "https://verso-vet.com/.../photo.jpg" |
| `{{CONTENT}}` | Contenu de la section blanche | `<h2>À compléter</h2>...` |

---

## Exemple Réel : Page de Consultation

**URL:** https://verso-vet.com/consultation-refere/

**Structure correcte:**
1. ✅ Hero Divi avec shortcodes `[et_pb_section]...[/et_pb_section]`
2. ✅ Formulaire HTML pur EN DEHORS des shortcodes
3. ✅ Script JavaScript pour envoyer à `consultations@verso-vet.com`
4. ✅ Métadonnée `_et_pb_use_builder: "on"` activée

**Résultat:** Fond bleu qui s'étend correctement + formulaire fonctionnel

---

## Exemple avec le script

```bash
# Créer une page "Nouveau service" avec fond bleu
./create-page.sh "Nouveau service" gradient "Description du service"

# Résultat:
# 📄 Page: Nouveau service
# 🔗 URL: https://verso-vet.com/nouveau-service/
# 📊 ID: 2325
```

---

## API WordPress REST Utilisée

Le script utilise uniquement **l'API WP standard** :

```bash
# Créer une page
POST /wp/v2/pages
  {
    "title": "Titre",
    "slug": "slug-url",
    "content": "[et_pb_section...]",  # shortcodes Divi
    "status": "publish"
  }

# Récupérer le contenu raw (pour vérification)
GET /wp/v2/pages/{id}?context=edit
```

---

## Limitation Divi REST API

Divi expose `/divi/v1/` mais elle est **limitée** :
- Nécessite un nonce WordPress (token session, pas disponible en External API)
- Sert principalement à l'éditeur visuel Divi
- Pas utile pour la création de pages depuis l'extérieur

**Solution recommandée :** Utiliser l'API WP standard avec shortcodes Divi (ce que le script fait).

---

## Règle Critique : Copier une Page Divi

Si vous copiez une page Divi existante, **vous DEVEZ utiliser `context=edit`** :

```bash
# ✅ CORRECT — obtient content.raw (shortcodes Divi)
curl -u onyx:PASSWORD "https://verso-vet.com/wp-json/wp/v2/pages/6?context=edit" \
  | jq '.content.raw'

# ❌ INCORRECT — obtient content.rendered (HTML déjà rendu, Divi n'interprète pas)
curl "https://verso-vet.com/wp-json/wp/v2/pages/6" \
  | jq '.content.rendered'
```

La différence :
- `content.raw` : shortcodes bruts `[et_pb_section...]` → Divi les interprète
- `content.rendered` : HTML rendu `<p>[et_pb_section...]</p>` → affiche les shortcodes en texte!

---

## Créer une Page Manuellement (sans le script)

Si vous ne voulez pas utiliser `create-page.sh`, voici les étapes:

### 1. Préparer le contenu

**Format:** `[Shortcodes Divi SEULS] + [HTML pur HORS shortcodes]`

Fichier `mon-contenu.txt`:
```
[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" use_background_color_gradient="on" background_color_gradient_direction="165deg" background_color_gradient_stops="#1c2445 0%|#0e0e0e 100%" custom_margin="-150px||||false|false"][et_pb_row custom_margin="300px||150px||false|false"][et_pb_column type="4_4"][et_pb_text _builder_version="4.27.4" header_font="Barlow|700|||||||" header_text_color="#FFFFFF" header_font_size="48px"]<h1>Mon Titre</h1>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]

<div style="max-width:1000px;margin:40px auto">
  <p>Contenu HTML pur ici</p>
  <form>...</form>
</div>
```

### 2. Créer la page via l'API

```bash
CONTENT=$(cat mon-contenu.txt | python3 -c "import json,sys; print(json.dumps(sys.stdin.read()))")

curl -X POST \
  -u onyx:PASSWORD \
  https://verso-vet.com/wp-json/wp/v2/pages \
  -H "Content-Type: application/json" \
  -d "{
    \"title\": \"Mon Titre\",
    \"slug\": \"mon-titre\",
    \"content\": $CONTENT,
    \"status\": \"publish\",
    \"meta\": {
      \"_et_pb_use_builder\": \"on\",
      \"_et_pb_old_content\": \"<!-- wp:divi/placeholder /-->\"
    }
  }"
```

### 3. Vérifier

```bash
curl -u onyx:PASSWORD \
  "https://verso-vet.com/wp-json/wp/v2/pages/PAGE_ID?context=edit" | \
  jq '.content.raw | .[0:100]'
```

Doit commencer par `[et_pb_section...`

---

## Points Importants

### ✅ Le fond bleu doit s'étendre sous le logo et le menu

Si le fond bleu ne s'étend **pas sous le logo/menu** (reste blanc/gris), c'est que :

**Problème :** `_et_pb_use_builder` n'est pas défini à `"on"`

**Solution :** Réactiver Divi manuellement
```bash
curl -X POST \
  -u onyx:PASSWORD \
  https://verso-vet.com/wp-json/wp/v2/pages/PAGE_ID \
  -H "Content-Type: application/json" \
  -d '{"meta": {"_et_pb_use_builder": "on"}}'
```

Le script le fait automatiquement, donc ce cas ne devrait pas arriver!

---

## Troubleshooting

### "Template non trouvé"
```
❌ Template non trouvé: /path/to/template-hero-gradient.txt
```
→ Vérifiez que le dossier `templates/` existe et contient les fichiers

### "Page créée mais shortcodes manquants"
```
⚠️ Avertissement: shortcodes Divi non détectés
```
→ Vérifiez avec :
```bash
curl -u onyx:PASS "https://verso-vet.com/wp-json/wp/v2/pages/ID?context=edit" \
  | jq '.content.raw' | head -5
```

### "Type invalide"
```
❌ Type invalide: xyz
   Utilisez: gradient ou image
```
→ Utilisez uniquement `gradient` ou `image`

---

## Dossier Structure

```
verso-consultation-plugin/
├── create-page.sh                    # Script principal (exécutable)
├── TEMPLATE-GUIDE.md                 # Ce fichier
└── templates/
    ├── template-hero-gradient.txt    # Fond bleu gradient
    └── template-hero-image.txt       # Fond image + overlay
```

---

## Récapitulatif

| Élément | Info |
|---------|------|
| **Script** | `/create-page.sh` |
| **Usage** | `./create-page.sh "Titre" gradient` |
| **Isolation** | ✅ Complète (pas de side effects) |
| **Sécurité** | ✅ Couleurs hex directes, pas de gcid-*, pas de modules partagés |
| **Shortcuts Divi** | ✅ Compatibles avec Divi 4.27+ |

