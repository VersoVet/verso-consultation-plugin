# Guide Complet: Créer un Article avec Divi Builder

> **Version**: 1.0 | **Plateforme**: WordPress + Divi 4.27.2 | **Date**: 2026-05-16

---

## Table des Matières

1. [Accès et Création](#accès-et-création)
2. [Configuration Initiale](#configuration-initiale)
3. [Divi Builder Basics](#divi-builder-basics)
4. [Structure Divi](#structure-divi)
5. [Éléments Courants](#éléments-courants)
6. [Styling et Design](#styling-et-design)
7. [Bonnes Pratiques](#bonnes-pratiques)
8. [Astuces Avancées](#astuces-avancées)
9. [Optimisation SEO](#optimisation-seo)
10. [Publication](#publication)

---

## Accès et Création

### Étape 1: Aller au Tableau de Bord WordPress

1. Accéder à l'admin: `https://verso-vet.com/wp-admin/`
2. Se connecter avec vos identifiants

### Étape 2: Créer un Nouvel Article

**Menu Admin → Articles → Ajouter**

Ou directement en haut à gauche: **+ Ajouter**

### Étape 3: Activer Divi Builder

Une fois l'éditeur ouvert:

```
1. En haut à droite, cliquer sur "Divi"
2. Ou cliquer "Utiliser le Divi Builder"
3. L'éditeur visuel s'ouvre
```

**Alternative**: Rester en éditeur classique et cliquer le bouton "Divi" rouge en bas à gauche

---

## Configuration Initiale

### Titre et Métadonnées

**Avant de commencer le design**:

1. **Titre**: Entrer un titre clair et SEO-friendly
   - Exemple: "5 Conseils pour la Santé de Votre Chat"
   - Idéal: 50-60 caractères

2. **Excerpt** (optionnel): Résumé du contenu
   - Utilisé dans les listes d'articles
   - ~160 caractères

3. **Catégorie**: Sélectionner une catégorie
   - Exemple: "Santé Animale", "Conseils", etc.

4. **Tags**: Ajouter des mots-clés
   - 3-5 tags pertinents

5. **Image à la Une**: Ajouter une image
   - Taille recommandée: 1200x630px
   - Format: JPG ou PNG
   - Apparaît dans les listes et les réseaux sociaux

### Paramètres Divi (Optionnel)

En bas à droite, dans **"Paramètres de la page"**:

```
• Post Layout: Choisir le layout
  - Standard (avec sidebar)
  - Full Width (pleine largeur)
  - Centered (centré)

• Post Meta: Afficher date/auteur/catégorie
  - Cocher si souhaité

• Comments: Activer les commentaires
  - Recommandé pour l'engagement
```

---

## Divi Builder Basics

### L'Interface Divi

```
┌─────────────────────────────────────────┐
│ Toolbar (en haut)                       │
│ [Visual] [Code] [Preview] [Settings]    │
├─────────────────────────────────────────┤
│                                         │
│  Canvas (zone d'édition au centre)      │
│                                         │
│  ↓ Contenu s'affiche ici en temps réel   │
│                                         │
├─────────────────────────────────────────┤
│ Panneau à gauche: Modules               │
│ Panneau à droite: Paramètres            │
└─────────────────────────────────────────┘
```

### Le Panneau Gauche: Modules

Clique sur l'icône **"+"** à gauche pour accéder aux modules:

```
SECTIONS
├── Section
├── Section avec Colonnes Prédéfinies
│   ├── 2 colonnes égales
│   ├── 3 colonnes égales
│   ├── Barre latérale (2:1)
│   └── ...

ÉLÉMENTS
├── Texte
├── Image
├── Bouton
├── Vidéo
├── Galerie
├── Slider
├── etc.
```

### Le Panneau Droit: Paramètres

Quand tu sélectionnes un élément:

```
Design       → Couleurs, polices, espacement
Advanced     → Classes CSS, ID, animations
Responsive   → Règles pour mobile/tablette
```

---

## Structure Divi

### Hiérarchie des Éléments

```
Section (conteneur principal)
├── Row (rangée)
│   ├── Column (colonne 1)
│   │   └── Module (Texte, Image, etc.)
│   └── Column (colonne 2)
│       └── Module
└── (Peut contenir plusieurs rangées)
```

### Créer une Section Simple

1. **Cliquer sur "+" en bas de la page** ou à gauche
2. **Sélectionner "Section"**
3. **Une section vide s'ajoute**
4. **Ajouter des colonnes** (voir ci-dessous)

### Ajouter des Colonnes

1. **Cliquer sur la section**
2. **En bas à gauche, cliquer "Ajouter une rangée"**
3. **Choisir la structure**:
   - 1 colonne (pleine largeur)
   - 2 colonnes (égales ou 2:1)
   - 3 colonnes
   - 4 colonnes

---

## Éléments Courants

### 1. Texte (Text Module)

**Meilleur pour**: Corps du texte, paragraphes

**Comment ajouter**:
1. Cliquer sur "+"
2. Chercher "Texte" ou "Text"
3. Taper le contenu

**Paramètres importants**:
```
Design:
  • Font Family: Choisir la police (Barlow par défaut)
  • Font Size: 14-18px pour le corps
  • Line Height: 1.6-1.8 pour la lisibilité
  • Text Color: #333 ou noir
  • Alignment: Gauche, centré, justifié

Advanced:
  • Custom CSS: Ajouter des styles personnalisés
```

### 2. Image (Image Module)

**Meilleur pour**: Photos, illustrations, icônes

**Comment ajouter**:
1. Cliquer sur "+"
2. Chercher "Image"
3. Cliquer "Télécharger une image"

**Paramètres importants**:
```
Design:
  • Image Size: Largeur de l'image
  • Border Radius: Coins arrondis
  • Box Shadow: Ombre
  • Alt Text: Description pour l'accessibilité

Advanced:
  • URL (optionnel): Lier vers une page
```

**Tailles recommandées**:
```
• Hero/Bannière: 1200x400px
• Article (pleine largeur): 1000x600px
• Image dans colonne: 600x400px
• Icône/Petite image: 200x200px
```

### 3. Bouton (Button Module)

**Meilleur pour**: Appels à l'action, liens importants

**Comment ajouter**:
1. Cliquer sur "+"
2. Chercher "Bouton"
3. Entrer le texte du bouton

**Paramètres importants**:
```
Design:
  • Button URL: Lien de destination
  • Button Text: Texte du bouton
  • Button Color: Couleur (utiliser la marque)
  • Button Size: Small, Medium, Large
  • Border Radius: Coins arrondis

Couleurs marque Verso Vet:
  • Primaire: #1c2445 (bleu foncé)
  • Accent: #e74c3c (rouge)
```

### 4. Titre (Heading Module)

**Meilleur pour**: Titres, sous-titres

**Comment ajouter**:
1. Cliquer sur "+"
2. Chercher "Titre" ou "Heading"
3. Entrer le texte

**Hiérarchie des titres**:
```
H1: Titre principal de la page (1 seulement)
H2: Sections majeures
H3: Sous-sections
H4: Détails mineurs
```

**Paramètres importants**:
```
Design:
  • Heading Level: H1, H2, H3, etc.
  • Font Size: 36px (H1), 28px (H2), 22px (H3)
  • Color: #1c2445 (bleu Verso)
  • Font Weight: 700 (bold)
  • Margin Bottom: 20px (espacement)
```

### 5. Galerie (Gallery Module)

**Meilleur pour**: Collections de photos

**Comment ajouter**:
1. Cliquer sur "+"
2. Chercher "Galerie"
3. Ajouter les images

**Paramètres importants**:
```
Design:
  • Columns: 2, 3 ou 4 colonnes
  • Gallery Item Height: Auto ou fixe
  • Image Spacing: Espacement entre images
```

### 6. Vidéo (Video Module)

**Meilleur pour**: Contenu vidéo (YouTube, Vimeo)

**Comment ajouter**:
1. Cliquer sur "+"
2. Chercher "Vidéo"
3. Coller l'URL YouTube/Vimeo

**URL acceptées**:
```
• YouTube: https://www.youtube.com/watch?v=xxxxx
• Vimeo: https://vimeo.com/xxxxx
• Vidéo locale: Télécharger un MP4
```

---

## Styling et Design

### Couleurs Verso Vet

```
Primaire:     #1c2445 (Bleu foncé)
Accent:       #e74c3c (Rouge)
Texte clair:  #333 ou #555
Fond clair:   #f9f9f9 ou #FFFFFF
Séparateur:   #e0e0e0 (Gris léger)
```

### Polices Recommandées

**En-têtes**:
- Barlow (700 weight, bold)

**Corps de texte**:
- Barlow (400 weight, regular)
- Taille: 14-16px

### Espacement (Padding & Margin)

**Section**:
```
Design → Custom Padding:
  • Top: 50px ou 100px
  • Bottom: 50px ou 100px
  • Left/Right: 30px (auto)
```

**Module**:
```
Design → Spacing:
  • Margin Top: 15-20px
  • Margin Bottom: 15-20px
```

### Bordures et Ombres

**Section avec bordure**:
```
Design → Border:
  • Width: 1px ou 2px
  • Color: #e0e0e0
  • Style: Solid
```

**Ombre (Box Shadow)**:
```
Design → Box Shadow:
  • Horizontal: 0px
  • Vertical: 2px ou 4px
  • Blur: 8px
  • Color: rgba(0,0,0,0.1)
```

---

## Bonnes Pratiques

### 1. Structure Claire

✅ **BON**:
```
H1: Titre Principal
H2: Section 1
  Paragraphe...
H2: Section 2
  Paragraphe...
```

❌ **MAUVAIS**:
```
H1: Titre
H3: Sous-titre (sauter H2)
H4: Détail (pas de hiérarchie)
```

### 2. Images Optimisées

✅ **BON**:
- Compressées (< 500KB)
- Format WebP ou optimisé
- Alt text descriptif
- Responsive

❌ **MAUVAIS**:
- Images brutes (> 2MB)
- Pas d'alt text
- Trop grandes
- Pas responsive

### 3. Lisibilité

✅ **BON**:
```
• Police: 16px minimum
• Ligne: 1.6-1.8 espacement
• Contraste: Texte sombre sur fond clair
• Paragraphes: Court (3-4 lignes)
```

❌ **MAUVAIS**:
```
• Police: 12px (trop petit)
• Texte condensé
• Faible contraste
• Murs de texte
```

### 4. Mobile-First

Toujours tester sur mobile!

**En haut à droite**: Cliquer l'icône **téléphone** pour prévisualiser

Vérifier:
- Texte lisible
- Boutons cliquables (min 48px)
- Images s'ajustent

### 5. Performances

✅ **BON**:
- Max 3-5 sections par article
- Images compressées
- Pas de vidéos auto-play
- Chargement lazy des images

❌ **MAUVAIS**:
- 20+ sections
- Images haute résolution brutes
- Vidéos multiples
- Tout charge d'un coup

---

## Astuces Avancées

### Dupliquer une Section

1. Cliquer sur la section
2. En haut à droite, cliquer **"..."** (menu)
3. Sélectionner **"Dupliquer"**

**Gain de temps**: Copier une structure pour l'adapter

### Templates Sauvegardés

1. Créer une section belle
2. Cliquer **"..."** → **"Sauvegarder comme template"**
3. Donner un nom: "Section Hero", "CTA Consultation", etc.
4. Réutiliser dans d'autres articles!

### Réutiliser des Designs

**Options**:
1. **Dupliquer un article**: Articles → Cliquer → Dupliquer
2. **Importer un template**: "+" → **"Importer"** → Télécharger
3. **Templates Divi pré-faits**: Divi → **"Packs de Design"**

### Animation au Scroll

1. Sélectionner un module
2. Aller dans **"Advanced"** → **"Animation"**
3. Choisir une animation:
   - Fade In
   - Slide
   - Bounce
   - etc.

**⚠️ Attention**: Pas plus de 2-3 animations par page (alourdit le site)

### Colonnes Inégales

Par défaut: 2 colonnes = 50/50

Pour **2:1 ou 1:2**:
1. Créer 2 colonnes
2. Cliquer sur la première colonne
3. **Design** → Largeur personnalisée (ex: 66%)
4. Deuxième colonne: 34%

---

## Optimisation SEO

### On-Page SEO

**Titre de l'article** (très important):
- Inclure le mot-clé principal
- Moins de 60 caractères
- Exemple: "Santé du Chat: 5 Conseils d'un Vétérinaire"

**Permalink (URL)**:
- Utiliser les mots-clés
- Couper les mots vides: "le", "la", "un"
- Exemple: `/sante-du-chat-conseils-veterinaire/`

**Excerpt**:
- Résumé accrocheur (~160 caractères)
- Inclure le mot-clé
- Incitatif

**Heading Structure**:
```
H1: 1 titre principal
H2: 2-3 sections majeures
H3: Sous-points si nécessaire
(Pas de sauts dans la hiérarchie)
```

**Alt Text (Images)**:
```
❌ MAUVAIS: "image.jpg", "photo"
✅ BON: "Consultation vétérinaire avec un chat"
```

**Liens internes**:
- Lier vers autres articles pertinents
- Aider la navigation

### Mots-Clés

Cibler 1-2 mots-clés principaux par article

Exemple pour article "Santé du Chat":
- Principal: "santé du chat"
- Secondaire: "conseils vétérinaire", "chat malade"

---

## Publication

### Avant de Publier

**Checklist**:

```
□ Titre accrocheur (50-60 caractères)
□ Contenu bien structuré (H1, H2, H3)
□ Images compressées et avec alt text
□ Lien vers page /consultation/ (CTA)
□ Test sur mobile (icône téléphone)
□ Liens internes (2-3 articles pertinents)
□ Catégorie et tags assignés
□ Image à la une (1200x630px)
□ Excerpt rempli
```

### Sauvegarder le Brouillon

1. En haut à droite, cliquer **"Sauvegarder le brouillon"**
2. L'article est sauvegardé sans être publié
3. Continuer plus tard: **Articles → Chercher l'article**

### Prévisualiser

1. Cliquer **"Prévisualiser"** (oeil) en haut
2. Voir comment ça apparaîtra en live
3. Vérifier sur mobile

### Publier

1. Cliquer **"Publier"** en haut à droite
2. Confirmer la publication
3. L'article est en live! 🎉

**Note**: Une fois publié, l'article apparaît immédiatement sur le site et dans Google (indexation rapide).

---

## Exemples de Mise en Page

### Article Conseil (Standard)

```
Hero Section (image + titre)
├─ Section Titre + Intro
├─ Section: Conseil 1
│  ├─ 2 colonnes: Image | Texte
├─ Section: Conseil 2
│  ├─ 2 colonnes: Texte | Image
├─ Section: Conseil 3
│  ├─ Texte + Image
└─ CTA: "Prendre rendez-vous"
   └─ Bouton → /consultation/
```

### Case Study / Témoignage

```
Hero Section
├─ Contexte
├─ Problème
├─ Solution (3 colonnes)
├─ Résultat
├─ Témoignage
└─ CTA
```

### Liste / Comparaison

```
Intro
├─ Tableau ou 3 colonnes
│  ├─ Icône
│  ├─ Titre
│  └─ Texte
├─ Tableau ou 3 colonnes
└─ CTA
```

---

## Troubleshooting

### Le contenu ne s'affiche pas

1. Actualiser la page (Ctrl+R)
2. Vider le cache: **Paramètres → Divi → Cache**
3. Vérifier que la section n'est pas masquée (masqué sur mobile)

### Les colonnes s'empilent mal

1. Cliquer sur la colonne
2. **Advanced** → **Custom Responsive**
3. Vérifier les règles mobile
4. Réinitialiser si nécessaire

### Les images sont floues

1. Vérifier la taille originale (au moins 1200px large)
2. Compresser avec TinyPNG avant upload
3. Utiliser le format WebP si possible

### Divi Builder ne se charge pas

1. Désactiver les plugins: **Plugins → Tout désactiver**
2. Réactiver un par un
3. Identifier le plugin en conflit

---

## Ressources

- **Documentation Divi**: https://www.elegantthemes.com/documentation/divi/
- **Couleurs Verso**: #1c2445, #e74c3c
- **Taille images**: 1200x630px (à la une)
- **Compression**: https://tinypng.com

---

## Checklist Finale pour Chaque Article

```
RÉDACTION:
☐ Contenu pertinent et unique
☐ Pas de plagiat
☐ Orthographe/grammaire correcte
☐ Longueur appropriée (min 500 mots)

STRUCTURE:
☐ H1, H2, H3 correctement hiérarchisés
☐ Paragraphes courts (3-4 lignes)
☐ Listes à puces si approprié
☐ Pas de murs de texte

VISUEL:
☐ Images compressées
☐ Alt text descriptif
☐ Au moins 1 image
☐ Responsive sur mobile

SEO:
☐ Mot-clé dans titre
☐ Mot-clé dans slug
☐ Mot-clé dans excerpt
☐ Liens internes (2-3)
☐ Catégorie assignée
☐ Tags pertinents

DIVI:
☐ Couleurs cohérentes
☐ Espacements réguliers
☐ Pas d'animations excessives
☐ Testé sur mobile

PUBLICATION:
☐ Preview vérifié
☐ CTA vers /consultation/
☐ Lien prêt à partager
☐ Métadonnées complètes
```

---

**Bon courage pour vos créations! 🚀**
