#!/usr/bin/env python3
"""
Initialize SQLite database for consultation tracking.
"""

import sqlite3
import os
from datetime import datetime

DB_PATH = os.path.join(os.path.dirname(__file__), 'consultations.db')

def init_database():
    """Create database and tables if they don't exist."""
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    # Create consultations table
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS consultations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            owner_nom TEXT NOT NULL,
            owner_prenom TEXT NOT NULL,
            owner_email TEXT NOT NULL,
            owner_telephone TEXT NOT NULL,
            owner_address TEXT,
            animal_nom TEXT NOT NULL,
            animal_espece TEXT NOT NULL,
            animal_race TEXT,
            motif TEXT NOT NULL,
            vet_nom TEXT,
            vet_prenom TEXT,
            vet_clinique TEXT,
            vet_email TEXT,
            vet_telephone TEXT,
            files_json TEXT,
            email_received_at DATETIME NOT NULL,
            email_from_address TEXT,
            status TEXT DEFAULT 'new',
            integrated_at DATETIME,
            integration_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ''')

    # Create status_log table for tracking changes
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS status_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            consultation_uuid TEXT NOT NULL,
            old_status TEXT,
            new_status TEXT NOT NULL,
            notes TEXT,
            changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (consultation_uuid) REFERENCES consultations(uuid)
        )
    ''')

    # Create indexes
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_uuid ON consultations(uuid)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_status ON consultations(status)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_email ON consultations(owner_email)')
    cursor.execute('CREATE INDEX IF NOT EXISTS idx_received_at ON consultations(email_received_at)')

    conn.commit()
    conn.close()
    print(f"✅ Database initialized at {DB_PATH}")

if __name__ == '__main__':
    init_database()
