#!/usr/bin/env python3
"""
Script de conversion MySQL → PostgreSQL pour EcoRide
Convertit automatiquement schema.sql et seed.sql
"""

import re
import sys

def convert_mysql_to_postgresql(input_file, output_file):
    """Convertit un fichier SQL MySQL en PostgreSQL"""
    
    with open(input_file, 'r', encoding='utf-8') as f:
        sql = f.read()
    
    print(f"📖 Lecture de {input_file}...")
    
    # 1. Supprimer CREATE DATABASE et USE (Render crée déjà la base)
    sql = re.sub(r'CREATE DATABASE.*?;', '', sql, flags=re.IGNORECASE | re.DOTALL)
    sql = re.sub(r'USE\s+\w+;', '', sql, flags=re.IGNORECASE)
    
    # 2. Supprimer les backticks MySQL
    sql = sql.replace('`', '')
    
    # 3. AUTO_INCREMENT → SERIAL ou GENERATED ALWAYS AS IDENTITY
    sql = re.sub(
        r'(\w+)\s+INT\s+AUTO_INCREMENT\s+PRIMARY KEY',
        r'\1 SERIAL PRIMARY KEY',
        sql,
        flags=re.IGNORECASE
    )
    sql = re.sub(
        r'(\w+)\s+INT\s+AUTO_INCREMENT',
        r'\1 SERIAL',
        sql,
        flags=re.IGNORECASE
    )
    
    # 4. DATETIME → TIMESTAMP
    sql = re.sub(r'\bDATETIME\b', 'TIMESTAMP', sql, flags=re.IGNORECASE)
    
    # 5. TINYINT(1) → BOOLEAN
    sql = re.sub(r'\bTINYINT\(1\)\b', 'BOOLEAN', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\bTINYINT\b', 'SMALLINT', sql, flags=re.IGNORECASE)
    
    # 6. ENUM → VARCHAR avec CHECK
    def replace_enum(match):
        column = match.group(1)
        values = match.group(2)
        return f"{column} VARCHAR(50) CHECK ({column} IN ({values}))"
    
    sql = re.sub(
        r"(\w+)\s+ENUM\((.*?)\)",
        replace_enum,
        sql,
        flags=re.IGNORECASE
    )
    
    # 7. Supprimer ENGINE et CHARSET
    sql = re.sub(r'\s*ENGINE\s*=\s*\w+', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\s*DEFAULT\s+CHARSET\s*=\s*\w+', '', sql, flags=re.IGNORECASE)
    sql = re.sub(r'\s*COLLATE\s*=\s*\w+', '', sql, flags=re.IGNORECASE)
    
    # 8. ON UPDATE CURRENT_TIMESTAMP → Nécessite un trigger
    # On le supprime et on créera des triggers
    sql = re.sub(
        r'\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP',
        '',
        sql,
        flags=re.IGNORECASE
    )
    
    # 9. Remplacer 0 et 1 par FALSE et TRUE pour BOOLEAN
    sql = re.sub(
        r'(is_\w+|has_\w+|active)\s+(BOOLEAN\s+)?DEFAULT\s+0',
        r'\1 BOOLEAN DEFAULT FALSE',
        sql,
        flags=re.IGNORECASE
    )
    sql = re.sub(
        r'(is_\w+|has_\w+|active)\s+(BOOLEAN\s+)?DEFAULT\s+1',
        r'\1 BOOLEAN DEFAULT TRUE',
        sql,
        flags=re.IGNORECASE
    )
    
    # 10. Ajouter IF NOT EXISTS pour les tables
    sql = re.sub(
        r'CREATE TABLE\s+(\w+)',
        r'CREATE TABLE IF NOT EXISTS \1',
        sql,
        flags=re.IGNORECASE
    )
    
    # 11. Nettoyer les espaces multiples
    sql = re.sub(r'\n\n+', '\n\n', sql)
    
    # 12. Ajouter des triggers pour updated_at
    trigger_template = """
-- Trigger pour mettre à jour automatiquement updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';
"""
    
    # Détecter les tables avec updated_at et créer des triggers
    tables_with_updated_at = re.findall(
        r'CREATE TABLE IF NOT EXISTS (\w+).*?updated_at',
        sql,
        flags=re.IGNORECASE | re.DOTALL
    )
    
    if tables_with_updated_at:
        sql += "\n\n-- ==========================================\n"
        sql += "-- 🔄 TRIGGERS POUR updated_at\n"
        sql += "-- ==========================================\n"
        sql += trigger_template
        
        for table in tables_with_updated_at:
            sql += f"\nCREATE TRIGGER update_{table}_updated_at "
            sql += f"BEFORE UPDATE ON {table}\n"
            sql += f"FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();\n"
    
    # Écrire le fichier de sortie
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(sql)
    
    print(f"✅ Conversion terminée : {output_file}")
    print(f"📊 {len(tables_with_updated_at)} triggers créés pour updated_at")

def main():
    print("=" * 50)
    print("🔄 CONVERSION MySQL → PostgreSQL")
    print("=" * 50)
    
    # Convertir schema.sql
    try:
        convert_mysql_to_postgresql(
            'database/schema.sql',
            'database/schema_postgresql.sql'
        )
    except FileNotFoundError:
        print("❌ Fichier database/schema.sql introuvable")
        return 1
    
    # Convertir seed.sql
    try:
        convert_mysql_to_postgresql(
            'database/seed.sql',
            'database/seed_postgresql.sql'
        )
    except FileNotFoundError:
        print("⚠️ Fichier database/seed.sql introuvable (ignoré)")
    
    print("\n" + "=" * 50)
    print("✅ CONVERSION TERMINÉE")
    print("=" * 50)
    print("\nFichiers créés :")
    print("  📄 database/schema_postgresql.sql")
    print("  📄 database/seed_postgresql.sql")
    print("\nProchaine étape :")
    print("  👉 Vérifiez les fichiers générés")
    print("  👉 Importez-les dans Render PostgreSQL")
    
    return 0

if __name__ == '__main__':
    sys.exit(main())