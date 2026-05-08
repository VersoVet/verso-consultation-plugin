#!/usr/bin/env python3
"""
Sync emails from consultation mailbox to SQLite database.
Parses consultation emails and stores them.
"""

import sqlite3
import imaplib
import email
import re
import os
import json
from datetime import datetime
from email.header import decode_header

DB_PATH = os.path.join(os.path.dirname(__file__), 'consultations.db')

# Configuration (set via environment variables)
IMAP_HOST = os.getenv('VERSO_IMAP_HOST', 'verso-vet.com')
IMAP_EMAIL = os.getenv('VERSO_IMAP_EMAIL', 'consultations@verso-vet.com')
IMAP_PASSWORD = os.getenv('VERSO_IMAP_PASSWORD', '')
IMAP_FOLDER = 'INBOX'

def parse_consultation_email(email_msg):
    """Extract consultation data from email body."""
    try:
        # Get email body
        body = ""
        if email_msg.is_multipart():
            for part in email_msg.walk():
                if part.get_content_type() == "text/plain":
                    payload = part.get_payload(decode=True)
                    body = payload.decode('utf-8', errors='ignore')
                    break
        else:
            body = email_msg.get_payload(decode=True).decode('utf-8', errors='ignore')

        # Parse structured sections
        data = {
            'uuid': None,
            'owner_nom': '',
            'owner_prenom': '',
            'owner_email': '',
            'owner_telephone': '',
            'owner_address': '',
            'animal_nom': '',
            'animal_espece': '',
            'animal_race': '',
            'motif': '',
            'vet_nom': '',
            'vet_prenom': '',
            'vet_clinique': '',
            'vet_email': '',
            'vet_telephone': '',
        }

        # Extract sections
        sections = body.split('═════')
        for section in sections:
            lines = section.strip().split('\n')
            for line in lines:
                if ': ' in line:
                    key, value = line.split(': ', 1)
                    key = key.strip().lower()
                    value = value.strip()

                    # Map to data fields
                    if 'nom:' in key and 'propriétaire' not in section.lower():
                        data['owner_nom'] = value
                    elif 'prénom:' in key:
                        data['owner_prenom'] = value
                    elif 'email' in key:
                        data['owner_email'] = value
                    elif 'téléphone' in key:
                        data['owner_telephone'] = value
                    elif 'adresse' in key:
                        data['owner_address'] = value
                    elif 'animal_nom' in key or 'nom du patient' in key:
                        data['animal_nom'] = value
                    elif 'espèce' in key:
                        data['animal_espece'] = value
                    elif 'race' in key:
                        data['animal_race'] = value
                    elif 'motif' in key:
                        data['motif'] = value
                    elif 'clinique' in key:
                        data['vet_clinique'] = value
                    elif 'vétérinaire' in key.lower():
                        if 'nom' in key:
                            data['vet_nom'] = value
                        elif 'email' in key:
                            data['vet_email'] = value

        return data if data['owner_email'] else None
    except Exception as e:
        print(f"❌ Error parsing email: {e}")
        return None

def store_consultation(conn, email_date, from_addr, email_data):
    """Store consultation in database."""
    cursor = conn.cursor()

    # Generate UUID from email date and sender
    uuid = f"verso-{int(email_date.timestamp())}-{abs(hash(from_addr)) % 1000000}"

    try:
        cursor.execute('''
            INSERT OR IGNORE INTO consultations (
                uuid, owner_nom, owner_prenom, owner_email, owner_telephone, owner_address,
                animal_nom, animal_espece, animal_race, motif,
                vet_nom, vet_prenom, vet_clinique, vet_email, vet_telephone,
                email_received_at, email_from_address, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ''', (
            uuid,
            email_data.get('owner_nom', ''),
            email_data.get('owner_prenom', ''),
            email_data.get('owner_email', ''),
            email_data.get('owner_telephone', ''),
            email_data.get('owner_address', ''),
            email_data.get('animal_nom', ''),
            email_data.get('animal_espece', ''),
            email_data.get('animal_race', ''),
            email_data.get('motif', ''),
            email_data.get('vet_nom', ''),
            email_data.get('vet_prenom', ''),
            email_data.get('vet_clinique', ''),
            email_data.get('vet_email', ''),
            email_data.get('vet_telephone', ''),
            email_date.isoformat(),
            from_addr,
            'new'
        ))
        conn.commit()
        return True
    except sqlite3.IntegrityError:
        # Consultation already exists
        return False
    except Exception as e:
        print(f"❌ Error storing consultation: {e}")
        return False

def sync_emails():
    """Connect to IMAP and sync emails."""
    if not IMAP_PASSWORD:
        print("❌ VERSO_IMAP_PASSWORD not set")
        return

    try:
        # Connect to IMAP
        print(f"📧 Connecting to {IMAP_HOST}...")
        imap = imaplib.IMAP4_SSL(IMAP_HOST)
        imap.login(IMAP_EMAIL, IMAP_PASSWORD)
        imap.select(IMAP_FOLDER)

        # Get database connection
        conn = sqlite3.connect(DB_PATH)

        # Search for consultation emails
        status, messages = imap.search(None, 'SUBJECT', '[Verso Vet]')
        if status == 'OK' and messages[0]:
            email_ids = messages[0].split()
            print(f"📨 Found {len(email_ids)} consultation emails")

            imported_count = 0
            for email_id in email_ids:
                status, msg_data = imap.fetch(email_id, '(RFC822)')
                if status == 'OK':
                    email_msg = email.message_from_bytes(msg_data[0][1])

                    # Parse email
                    email_date = email.utils.parsedate_to_datetime(email_msg['Date'])
                    from_addr = email.utils.parseaddr(email_msg['From'])[1]

                    # Extract consultation data
                    data = parse_consultation_email(email_msg)
                    if data:
                        if store_consultation(conn, email_date, from_addr, data):
                            imported_count += 1
                            print(f"  ✅ Imported: {data['owner_nom']} {data['owner_prenom']} - {data['animal_nom']}")

            conn.close()
            imap.close()
            print(f"\n✅ Sync complete: {imported_count} new consultations imported")
        else:
            print("ℹ️  No consultation emails found")
            imap.close()

    except Exception as e:
        print(f"❌ Error syncing emails: {e}")

if __name__ == '__main__':
    sync_emails()
