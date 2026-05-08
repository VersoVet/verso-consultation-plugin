# Dashboard - Gestion des Consultations

Système de suivi des demandes de consultation basé sur SQLite et synchronisation des emails.

---

## 🚀 Installation Rapide

### 1. Initialiser la base de données

```bash
cd dashboard
python3 init_db.py
```

Cela crée `consultations.db` avec les tables nécessaires.

### 2. Configurer les variables d'environnement

```bash
export VERSO_IMAP_HOST="verso-vet.com"           # Serveur IMAP
export VERSO_IMAP_EMAIL="consultations@verso-vet.com"  # Email
export VERSO_IMAP_PASSWORD="votre-mot-de-passe"  # Mot de passe
```

### 3. Synchroniser les emails (première fois)

```bash
python3 sync_emails.py
```

Cela importe toutes les consultations depuis les emails reçus.

---

## 📋 Utilisation

### Lister les consultations

```bash
# Toutes les consultations
python3 cli.py list

# Seulement les nouvelles
python3 cli.py list new

# Seulement les intégrées à l'ERP
python3 cli.py list integrated

# Limiter à 20 résultats
python3 cli.py list new 20
```

### Afficher une consultation

```bash
python3 cli.py show verso-1620000000000-abc123def
```

Affiche:
- Infos propriétaire (nom, email, téléphone, adresse)
- Infos animal (nom, espèce, race)
- Motif de consultation
- Infos vétérinaire référant (si fourni)
- Statut et dates

### Mettre à jour le statut

```bash
# Marquer comme "reviewed"
python3 cli.py status verso-1620000000000-abc123def reviewed

# Marquer comme intégrée à l'ERP
python3 cli.py status verso-1620000000000-abc123def integrated "Envoyé à VetoPartner CRM"

# Archiver
python3 cli.py status verso-1620000000000-abc123def archived
```

**Statuts disponibles:**
- `new` - Nouvelle (par défaut)
- `reviewed` - Examinée par l'équipe
- `integrated` - Intégrée à l'ERP
- `archived` - Archivée

### Exporter pour l'ERP

```bash
# Exporter les consultations intégrées
python3 cli.py export integrated consultations.json

# Exporter toutes les consultations
python3 cli.py export "" all_consultations.json

# Exporter seulement les nouvelles
python3 cli.py export new pending.json
```

Le fichier JSON contient toutes les données structurées pour import dans l'ERP.

---

## 🔄 Synchronisation Automatique

Vous pouvez planifier la synchronisation avec **cron**:

```bash
# Synchroniser chaque heure
0 * * * * cd /path/to/dashboard && python3 sync_emails.py

# Synchroniser toutes les 30 minutes
*/30 * * * * cd /path/to/dashboard && python3 sync_emails.py

# Synchroniser chaque matin à 8h
0 8 * * * cd /path/to/dashboard && python3 sync_emails.py
```

Ajoutez dans crontab:
```bash
crontab -e
# Puis ajoutez les lignes ci-dessus
```

---

## 📊 Structure de la Base de Données

### Table `consultations`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | ID unique auto-incrémenté |
| uuid | TEXT | UUID unique (verso-timestamp-random) |
| owner_nom | TEXT | Nom du propriétaire |
| owner_prenom | TEXT | Prénom du propriétaire |
| owner_email | TEXT | Email du propriétaire |
| owner_telephone | TEXT | Téléphone du propriétaire |
| owner_address | TEXT | Adresse du propriétaire |
| animal_nom | TEXT | Nom de l'animal |
| animal_espece | TEXT | Espèce (Chien, Chat, etc.) |
| animal_race | TEXT | Race de l'animal |
| motif | TEXT | Motif de consultation |
| vet_nom | TEXT | Nom vétérinaire référant |
| vet_prenom | TEXT | Prénom vétérinaire |
| vet_clinique | TEXT | Nom de la clinique |
| vet_email | TEXT | Email vétérinaire |
| vet_telephone | TEXT | Téléphone vétérinaire |
| files_json | TEXT | JSON array de fichiers joints |
| email_received_at | DATETIME | Date de réception de l'email |
| email_from_address | TEXT | Adresse email source |
| status | TEXT | Statut (new, reviewed, integrated, archived) |
| integrated_at | DATETIME | Date d'intégration à l'ERP |
| integration_notes | TEXT | Notes d'intégration |
| created_at | DATETIME | Date création en base |
| updated_at | DATETIME | Date dernière modification |

### Table `status_log`

Historique des changements de statut:

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | ID unique |
| consultation_uuid | TEXT | UUID de la consultation |
| old_status | TEXT | Ancien statut |
| new_status | TEXT | Nouveau statut |
| notes | TEXT | Notes du changement |
| changed_at | DATETIME | Date du changement |

---

## 🔍 Requêtes SQL Utiles

### Consultations par statut

```sql
SELECT COUNT(*) FROM consultations GROUP BY status;
```

### Consultations du jour

```sql
SELECT * FROM consultations 
WHERE DATE(email_received_at) = DATE('now')
ORDER BY email_received_at DESC;
```

### Consultations par espèce

```sql
SELECT animal_espece, COUNT(*) FROM consultations 
GROUP BY animal_espece 
ORDER BY COUNT(*) DESC;
```

### Historique d'une consultation

```sql
SELECT * FROM status_log 
WHERE consultation_uuid = 'verso-...' 
ORDER BY changed_at DESC;
```

---

## 📧 Format des Emails Attendu

Les emails de consultation doivent avoir le format:

```
Subject: [Verso Vet] Nouvelle demande - Rex (Chien)

Nouvelle demande de consultation reçue

═══════════════════════════════════════════
PROPRIÉTAIRE/CONTACT
═══════════════════════════════════════════
Nom: Dupont
Prénom: Jean
Email: jean@example.com
Téléphone: +33612345678
Adresse: 123 Rue de Paris, 75000 Paris

═══════════════════════════════════════════
PATIENT ANIMAL
═══════════════════════════════════════════
Nom: Rex
Espèce: Chien
Race: Labrador

═══════════════════════════════════════════
MOTIF DE CONSULTATION
═══════════════════════════════════════════
Boiterie antérieure depuis 3 jours...

[Optionnel: sections vétérinaire si fourni]
```

---

## 🔧 Dépannage

### "Database not found"
```bash
# Initialisez d'abord:
python3 init_db.py
```

### Synchronisation ne trouve aucun email
1. Vérifiez les variables d'environnement:
   ```bash
   echo $VERSO_IMAP_PASSWORD
   ```
2. Testez la connexion IMAP manuellement:
   ```bash
   python3 -c "import imaplib; imap = imaplib.IMAP4_SSL('verso-vet.com'); imap.login('consultations@verso-vet.com', 'password'); print('OK')"
   ```
3. Vérifiez que le dossier est `INBOX`

### Les emails ne se parsent pas
- Vérifiez le format de l'email (sections bien séparées par `═════`)
- Vérifiez l'encodage UTF-8
- Voir les logs de synchronisation

---

## 📈 Workflow Complet

```
1. Utilisateur remplit formulaire
   ↓
2. Email envoyé à consultations@verso-vet.com
   ↓
3. Cron appelle sync_emails.py toutes les heures
   ↓
4. Consultations importées dans SQLite (status: 'new')
   ↓
5. Équipe voit consultations: python3 cli.py list new
   ↓
6. Équipe revoit: python3 cli.py show <uuid>
   ↓
7. Équipe intègre à l'ERP (via export JSON ou API ERP)
   ↓
8. Équipe marque comme intégrée:
   python3 cli.py status <uuid> integrated "Notes d'intégration"
   ↓
9. Historique conservé dans status_log
```

---

## 🔐 Sécurité

- Les mots de passe IMAP ne sont jamais stockés (variables d'environnement)
- Les données sont stockées localement (SQLite)
- UUIDs aléatoires pour chaque consultation
- Historique complet des changements

---

## 📞 Support

- Erreurs IMAP? Vérifiez les credentials
- SQL? Utilisez les requêtes d'exemple
- Besoin de plus? Modifiez `cli.py` selon vos besoins

**Les données restent sous votre contrôle dans SQLite local!**
