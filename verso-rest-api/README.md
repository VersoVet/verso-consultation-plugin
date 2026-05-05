# Verso REST API Plugin

Plugin WordPress pour formulaire Verso - Envoie les demandes de consultation par email.

## Installation

1. Placer le répertoire dans `/wp-content/plugins/verso-rest-api/`
2. Activer le plugin via WordPress Admin
3. L'endpoint sera disponible à: `/wp-json/verso/v1/submit`

## Utilisation

### Endpoint

```
POST /wp-json/verso/v1/submit
Content-Type: application/json

{
  "owner_nom": "Dupont",
  "owner_prenom": "Marie",
  "owner_email": "marie@example.com",
  "owner_telephone": "+33612345678",
  "vet_nom": "Dr. Smith",
  "vet_clinique": "Clinique Vétérinaire",
  "animal_nom": "Rex",
  "animal_espece": "Chien",
  "animal_race": "Labrador",
  "motif": "Consultation pour douleur locomotrice"
}
```

### Réponse

**Succès (200):**
```json
{
  "success": true,
  "message": "Demande envoyée avec succès! Vous recevrez une confirmation par email."
}
```

**Erreur (400/500):**
```json
{
  "success": false,
  "message": "Erreur lors de l'envoi de l'email"
}
```

## Email

Les demandes sont envoyées à: `consultations@verso-vet.com`

Format de l'email:
- Sujet: `[Verso Vet] Nouvelle demande - {animal_nom} ({animal_espece})`
- Corps: Texte structuré avec tous les détails

## Configuration

Aucune configuration requise. L'email est envoyé automatiquement via le système mail de WordPress.

## Déploiement Automatisé

Voir `deploy-verso-rest-api.sh` pour le déploiement automatisé.
