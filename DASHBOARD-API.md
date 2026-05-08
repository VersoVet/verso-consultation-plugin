# API Dashboard - Verso Consultation

API pour accéder aux consultations et gérer les documents via le dashboard.

---

## Configuration

### Générer une clé API

```bash
# Via WordPress CLI (en SSH sur verso-vet.com)
wp option add verso_api_key "your-secure-random-key-here"

# Ou via code PHP dans wp-admin
```

---

## Endpoints

Tous les endpoints du dashboard nécessitent l'en-tête:
```
X-Verso-API-Key: YOUR_API_KEY
```

### 1. Lister les consultations

**GET** `/wp-json/verso/v1/consultations`

**Paramètres:**
- `per_page` (int, defaut: 20) - Nombre de résultats par page
- `paged` (int, defaut: 1) - Numéro de page
- `status` (string) - Filtrer par statut: `new`, `reviewed`, `processed`, `archived`
- `search` (string) - Rechercher par nom/email/animal

**Exemple:**
```bash
curl -H "X-Verso-API-Key: YOUR_API_KEY" \
  "https://verso-vet.com/wp-json/verso/v1/consultations?status=new&per_page=10"
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 2327,
      "title": "Jean Dupont - Rex (Chien)",
      "status": "new",
      "created": "2026-05-08T10:30:00Z",
      "owner": {
        "nom": "Dupont",
        "email": "jean@example.com"
      },
      "animal": {
        "nom": "Rex",
        "espece": "Chien"
      },
      "file_count": 3,
      "url": "https://verso-vet.com/wp-json/verso/v1/consultations/2327"
    }
  ],
  "pagination": {
    "total": 42,
    "pages": 5,
    "current_page": 1,
    "per_page": 10
  }
}
```

---

### 2. Obtenir une consultation complète

**GET** `/wp-json/verso/v1/consultations/{id}`

**Exemple:**
```bash
curl -H "X-Verso-API-Key: YOUR_API_KEY" \
  "https://verso-vet.com/wp-json/verso/v1/consultations/2327"
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "id": 2327,
    "title": "Jean Dupont - Rex (Chien)",
    "status": "new",
    "uuid": "verso-1620000000000-abc123def",
    "created": "2026-05-08T10:30:00Z",
    "owner": {
      "nom": "Dupont",
      "prenom": "Jean",
      "email": "jean@example.com",
      "telephone": "+33612345678",
      "address": "123 Rue de Paris, 75000 Paris"
    },
    "animal": {
      "nom": "Rex",
      "espece": "Chien",
      "race": "Labrador"
    },
    "consultation": {
      "motif": "Boiterie antérieure depuis 3 jours..."
    },
    "veterinaire": {
      "nom": "Dupuis",
      "prenom": "Marie",
      "clinique": "Clinique Animale de Paris",
      "email": "marie@clinique.fr",
      "telephone": "+33123456789"
    },
    "files": [
      "https://verso-vet.com/wp-content/uploads/verso-consultations/verso-1620000000000-abc123def/radiographie.pdf",
      "https://verso-vet.com/wp-content/uploads/verso-consultations/verso-1620000000000-abc123def/analyse.jpg"
    ]
  }
}
```

---

### 3. Lister les fichiers d'une consultation

**GET** `/wp-json/verso/v1/consultations/{id}/files`

**Exemple:**
```bash
curl -H "X-Verso-API-Key: YOUR_API_KEY" \
  "https://verso-vet.com/wp-json/verso/v1/consultations/2327/files"
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "name": "radiographie.pdf",
      "url": "https://verso-vet.com/wp-content/uploads/verso-consultations/.../radiographie.pdf",
      "delete_url": "https://verso-vet.com/wp-json/verso/v1/delete/verso-1620000000000-abc123def/radiographie.pdf"
    },
    {
      "name": "analyse.jpg",
      "url": "https://verso-vet.com/wp-content/uploads/verso-consultations/.../analyse.jpg",
      "delete_url": "https://verso-vet.com/wp-json/verso/v1/delete/verso-1620000000000-abc123def/analyse.jpg"
    }
  ],
  "count": 2
}
```

---

### 4. Mettre à jour le statut

**POST** `/wp-json/verso/v1/consultations/{id}/status`

**Corps (JSON):**
```json
{
  "status": "reviewed"
}
```

**Statuts valides:**
- `new` - Nouvelle consultation
- `reviewed` - Consultée/revue
- `processed` - Traitée
- `archived` - Archivée

**Exemple:**
```bash
curl -X POST -H "X-Verso-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"status":"processed"}' \
  "https://verso-vet.com/wp-json/verso/v1/consultations/2327/status"
```

**Réponse:**
```json
{
  "success": true,
  "message": "Statut mis à jour",
  "id": 2327,
  "status": "processed"
}
```

---

### 5. Supprimer un fichier

**DELETE** `/wp-json/verso/v1/delete/{uuid}/{filename}`

Pas besoin d'authentification (protégé par UUID).

**Exemple:**
```bash
curl -X DELETE \
  "https://verso-vet.com/wp-json/verso/v1/delete/verso-1620000000000-abc123def/radiographie.pdf"
```

**Réponse:**
```json
{
  "success": true,
  "message": "Fichier supprimé"
}
```

---

## 📧 Email Automatique

À chaque soumission de consultation, un email est automatiquement envoyé à `consultations@verso-vet.com` avec:

- Les informations du propriétaire
- Les informations du vétérinaire référant (si fourni)
- Les informations de l'animal
- Le motif de la consultation
- **Les URLs de téléchargement pour les fichiers joints**
- L'ID et UUID de la consultation pour tracking

Tous les fichiers uploadés sont directement téléchargeables via les liens fournis dans l'email.

---

## Exemples d'Intégration

### Node.js

```javascript
async function getConsultations(apiKey) {
  const response = await fetch(
    'https://verso-vet.com/wp-json/verso/v1/consultations?status=new',
    {
      headers: {
        'X-Verso-API-Key': apiKey
      }
    }
  );
  return response.json();
}

async function updateStatus(id, status, apiKey) {
  const response = await fetch(
    `https://verso-vet.com/wp-json/verso/v1/consultations/${id}/status`,
    {
      method: 'POST',
      headers: {
        'X-Verso-API-Key': apiKey,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status })
    }
  );
  return response.json();
}
```

### Python

```python
import requests

API_KEY = "YOUR_API_KEY"
BASE_URL = "https://verso-vet.com/wp-json/verso/v1"

def get_consultations(status='new'):
    response = requests.get(
        f"{BASE_URL}/consultations",
        params={'status': status},
        headers={'X-Verso-API-Key': API_KEY}
    )
    return response.json()

def get_consultation(id):
    response = requests.get(
        f"{BASE_URL}/consultations/{id}",
        headers={'X-Verso-API-Key': API_KEY}
    )
    return response.json()

def update_status(id, status):
    response = requests.post(
        f"{BASE_URL}/consultations/{id}/status",
        json={'status': status},
        headers={'X-Verso-API-Key': API_KEY}
    )
    return response.json()
```

### cURL

```bash
# Lister les consultations
curl -H "X-Verso-API-Key: YOUR_API_KEY" \
  "https://verso-vet.com/wp-json/verso/v1/consultations?status=new"

# Obtenir une consultation
curl -H "X-Verso-API-Key: YOUR_API_KEY" \
  "https://verso-vet.com/wp-json/verso/v1/consultations/2327"

# Mettre à jour le statut
curl -X POST -H "X-Verso-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"status":"processed"}' \
  "https://verso-vet.com/wp-json/verso/v1/consultations/2327/status"
```

---

## Notes Importantes

1. **Authentification:** Tous les endpoints du dashboard (sauf DELETE) nécessitent `X-Verso-API-Key`
2. **Files:** Les fichiers sont stockés dans `/wp-content/uploads/verso-consultations/{uuid}/`
3. **Deletion:** Le DELETE endpoint est public (protégé par UUID aléatoire)
4. **Email:** Chaque nouvelle consultation génère automatiquement un email à consultations@verso-vet.com
5. **Statuts:** Les 4 statuts principaux sont `new`, `reviewed`, `processed`, `archived`

---

## Sécurité

- ✅ API Key dans les en-têtes HTTP
- ✅ UUIDs aléatoires pour les fichiers
- ✅ Validation des statuts autorisés
- ✅ Protection path traversal sur les fichiers
