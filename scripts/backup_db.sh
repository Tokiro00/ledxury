#!/bin/bash
# ============================================================================
# scripts/backup_db.sh
# Backup diario de la DB de Ledxury con rotación de 7 días.
# ============================================================================
#
# Instalación en producción (una sola vez):
#   1) Crear directorio:    sudo mkdir -p /var/backups/ledxury && sudo chown ec2-user:ec2-user /var/backups/ledxury
#   2) Hacer ejecutable:    sudo chmod +x /var/www/html/scripts/backup_db.sh
#   3) Crontab del root o ec2-user (sudo crontab -e):
#         0 3 * * *  /var/www/html/scripts/backup_db.sh >> /var/log/ledxury_backup.log 2>&1
#      Esto lo ejecuta cada día a las 3 AM.
#
# Lo que hace:
#   - Lee credenciales DB de application/config/database.php
#   - mysqldump completo (estructura + datos), comprimido con gzip
#   - Guarda en /var/backups/ledxury/ledxury_YYYYMMDD_HHMM.sql.gz
#   - Borra backups con más de 7 días
#   - Si quieres restaurar: zcat archivo.sql.gz | mysql -u <user> -p <db>
#
# Tamaño esperado: la DB de Ledxury comprimida pesa ~10-20MB
# Espacio libre necesario: ~150MB para 7 días + margen
# ============================================================================

set -e

# Configuración
APP_DIR="/var/www/html"
BACKUP_DIR="/var/backups/ledxury"
RETAIN_DAYS=7
DB_CONFIG="${APP_DIR}/application/config/database.php"

# Validaciones
if [ ! -f "$DB_CONFIG" ]; then
    echo "ERROR: no existe $DB_CONFIG"
    exit 1
fi
if [ ! -d "$BACKUP_DIR" ]; then
    mkdir -p "$BACKUP_DIR" || { echo "ERROR: no pude crear $BACKUP_DIR"; exit 1; }
fi

# Extraer credenciales del database.php (sin parsear PHP — usa awk)
DBUSER=$(awk -F"'" '/=>/ && /username/{print $4; exit}' "$DB_CONFIG")
DBPASS=$(awk -F"'" '/=>/ && /password/{print $4; exit}' "$DB_CONFIG")
DBNAME=$(awk -F"'" '/=>/ && /database/ && !/dbdriver/{print $4; exit}' "$DB_CONFIG")
DBHOST=$(awk -F"'" '/=>/ && /hostname/{print $4; exit}' "$DB_CONFIG")
DBHOST=${DBHOST:-127.0.0.1}

if [ -z "$DBUSER" ] || [ -z "$DBNAME" ]; then
    echo "ERROR: no pude extraer credenciales DB"
    exit 1
fi

# Archivo .my.cnf temporal (más seguro que pasar password por CLI)
MYCNF=$(mktemp)
chmod 600 "$MYCNF"
cat > "$MYCNF" <<EOF
[client]
user=$DBUSER
password=$DBPASS
host=$DBHOST
EOF

# Nombre del archivo: ledxury_20260429_0300.sql.gz
TIMESTAMP=$(date +%Y%m%d_%H%M)
OUTFILE="$BACKUP_DIR/ledxury_${TIMESTAMP}.sql.gz"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Iniciando backup de $DBNAME → $OUTFILE"

# Dump + gzip (single transaction para no bloquear writes en producción)
if mysqldump --defaults-extra-file="$MYCNF" \
    --single-transaction \
    --quick \
    --routines --triggers --events \
    --default-character-set=utf8mb4 \
    "$DBNAME" 2>"${BACKUP_DIR}/last_error.log" | gzip -9 > "$OUTFILE"; then

    SIZE=$(du -h "$OUTFILE" | cut -f1)
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup OK: $OUTFILE ($SIZE)"
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR durante mysqldump. Ver ${BACKUP_DIR}/last_error.log"
    rm -f "$MYCNF"
    rm -f "$OUTFILE"
    exit 1
fi

rm -f "$MYCNF"

# Rotar: borrar backups con más de RETAIN_DAYS días
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Rotando backups con más de $RETAIN_DAYS días..."
DELETED=$(find "$BACKUP_DIR" -name "ledxury_*.sql.gz" -mtime +${RETAIN_DAYS} -delete -print | wc -l)
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Borrados $DELETED backups antiguos"

# Listar lo que queda
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backups disponibles:"
ls -lh "$BACKUP_DIR"/ledxury_*.sql.gz 2>/dev/null | awk '{print "  " $9 " (" $5 ")"}'

# Espacio libre
DF=$(df -h "$BACKUP_DIR" | awk 'NR==2 {print $4 " libres de " $2}')
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Disco: $DF"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Backup completado."
