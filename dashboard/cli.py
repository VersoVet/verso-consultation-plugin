#!/usr/bin/env python3
"""
CLI tool to manage consultations from SQLite database.
"""

import sqlite3
import os
import sys
from datetime import datetime
from tabulate import tabulate

DB_PATH = os.path.join(os.path.dirname(__file__), 'consultations.db')

def get_conn():
    """Get database connection."""
    if not os.path.exists(DB_PATH):
        print(f"❌ Database not found at {DB_PATH}")
        print("   Run: python3 init_db.py")
        sys.exit(1)
    return sqlite3.connect(DB_PATH)

def list_consultations(status=None, limit=50):
    """List consultations."""
    conn = get_conn()
    cursor = conn.cursor()

    query = 'SELECT id, uuid, owner_nom, owner_prenom, animal_nom, animal_espece, status, email_received_at FROM consultations'
    params = []

    if status:
        query += ' WHERE status = ?'
        params.append(status)

    query += ' ORDER BY email_received_at DESC LIMIT ?'
    params.append(limit)

    cursor.execute(query, params)
    rows = cursor.fetchall()
    conn.close()

    headers = ['ID', 'UUID', 'Owner', 'Animal', 'Espèce', 'Status', 'Reçue le']
    data = []
    for row in rows:
        data.append([
            row[0],
            row[1][:15] + '...',
            f"{row[2]} {row[3]}",
            row[4],
            row[5],
            row[6],
            row[7][:10]
        ])

    print(tabulate(data, headers=headers, tablefmt='grid'))
    print(f"\n📊 Total: {len(data)} consultations")

def show_consultation(uuid):
    """Show detailed consultation info."""
    conn = get_conn()
    cursor = conn.cursor()

    cursor.execute('SELECT * FROM consultations WHERE uuid = ?', (uuid,))
    row = cursor.fetchone()

    if not row:
        print(f"❌ Consultation not found: {uuid}")
        conn.close()
        return

    cols = [desc[0] for desc in cursor.description]
    data = dict(zip(cols, row))

    print(f"\n{'='*50}")
    print(f"Consultation: {data['uuid']}")
    print(f"{'='*50}\n")

    print("👤 PROPRIÉTAIRE")
    print(f"  Nom: {data['owner_nom']} {data['owner_prenom']}")
    print(f"  Email: {data['owner_email']}")
    print(f"  Téléphone: {data['owner_telephone']}")
    print(f"  Adresse: {data['owner_address']}")

    print("\n🐾 ANIMAL")
    print(f"  Nom: {data['animal_nom']}")
    print(f"  Espèce: {data['animal_espece']}")
    print(f"  Race: {data['animal_race']}")

    print("\n📋 CONSULTATION")
    print(f"  Motif: {data['motif']}")

    if data['vet_nom']:
        print("\n🏥 VÉTÉRINAIRE RÉFÉRANT")
        print(f"  Nom: {data['vet_nom']} {data['vet_prenom']}")
        print(f"  Clinique: {data['vet_clinique']}")
        print(f"  Email: {data['vet_email']}")
        print(f"  Téléphone: {data['vet_telephone']}")

    print("\n📊 STATUS")
    print(f"  Statut: {data['status']}")
    print(f"  Reçue le: {data['email_received_at']}")
    if data['integrated_at']:
        print(f"  Intégrée le: {data['integrated_at']}")
    if data['integration_notes']:
        print(f"  Notes: {data['integration_notes']}")

    conn.close()

def update_status(uuid, status, notes=""):
    """Update consultation status."""
    valid_statuses = ['new', 'reviewed', 'integrated', 'archived']
    if status not in valid_statuses:
        print(f"❌ Invalid status: {status}")
        print(f"   Valid: {', '.join(valid_statuses)}")
        return

    conn = get_conn()
    cursor = conn.cursor()

    # Get current status
    cursor.execute('SELECT status FROM consultations WHERE uuid = ?', (uuid,))
    row = cursor.fetchone()
    if not row:
        print(f"❌ Consultation not found: {uuid}")
        conn.close()
        return

    old_status = row[0]

    # Update consultation
    integrated_at = datetime.now().isoformat() if status == 'integrated' else None
    cursor.execute('''
        UPDATE consultations
        SET status = ?, integrated_at = ?, integration_notes = ?, updated_at = CURRENT_TIMESTAMP
        WHERE uuid = ?
    ''', (status, integrated_at, notes, uuid))

    # Log status change
    cursor.execute('''
        INSERT INTO status_log (consultation_uuid, old_status, new_status, notes)
        VALUES (?, ?, ?, ?)
    ''', (uuid, old_status, status, notes))

    conn.commit()
    conn.close()

    print(f"✅ Status updated: {old_status} → {status}")
    if notes:
        print(f"   Notes: {notes}")

def export_json(status="integrated", output_file="consultations.json"):
    """Export consultations to JSON for ERP integration."""
    import json
    conn = get_conn()
    cursor = conn.cursor()

    if status:
        cursor.execute('SELECT * FROM consultations WHERE status = ? ORDER BY email_received_at DESC', (status,))
    else:
        cursor.execute('SELECT * FROM consultations ORDER BY email_received_at DESC')

    cols = [desc[0] for desc in cursor.description]
    rows = cursor.fetchall()
    conn.close()

    data = []
    for row in rows:
        data.append(dict(zip(cols, row)))

    with open(output_file, 'w') as f:
        json.dump(data, f, indent=2, default=str)

    print(f"✅ Exported {len(data)} consultations to {output_file}")

def main():
    """CLI main entry point."""
    if len(sys.argv) < 2:
        print("Usage: python3 cli.py <command> [args]")
        print("\nCommands:")
        print("  list [status] [limit]          List consultations")
        print("  show <uuid>                     Show consultation details")
        print("  status <uuid> <status> [notes]  Update consultation status")
        print("  export [status] [output]        Export consultations to JSON")
        print("\nExamples:")
        print("  python3 cli.py list new 20")
        print("  python3 cli.py show verso-1620000000000-abc123def")
        print("  python3 cli.py status verso-... integrated 'Envoyé à VetoPartner'")
        print("  python3 cli.py export integrated consultations.json")
        return

    command = sys.argv[1]

    if command == 'list':
        status = sys.argv[2] if len(sys.argv) > 2 else None
        limit = int(sys.argv[3]) if len(sys.argv) > 3 else 50
        list_consultations(status, limit)

    elif command == 'show':
        if len(sys.argv) < 3:
            print("❌ Usage: python3 cli.py show <uuid>")
            return
        show_consultation(sys.argv[2])

    elif command == 'status':
        if len(sys.argv) < 4:
            print("❌ Usage: python3 cli.py status <uuid> <status> [notes]")
            return
        uuid = sys.argv[2]
        status = sys.argv[3]
        notes = sys.argv[4] if len(sys.argv) > 4 else ""
        update_status(uuid, status, notes)

    elif command == 'export':
        status = sys.argv[2] if len(sys.argv) > 2 else None
        output = sys.argv[3] if len(sys.argv) > 3 else "consultations.json"
        export_json(status, output)

    else:
        print(f"❌ Unknown command: {command}")

if __name__ == '__main__':
    main()
