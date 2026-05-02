#!/bin/bash
# ============================================================================
# scripts/install_backup_cron.sh
# Instala el backup automático de DB (corre 1 sola vez en la oficina).
# ============================================================================
#
# Uso:    sudo bash /var/www/html/scripts/install_backup_cron.sh
# ============================================================================

set -e

APP_DIR="/var/www/html"
BACKUP_DIR="/var/backups/ledxury"
SCRIPT="${APP_DIR}/scripts/backup_db.sh"
LOG_FILE="/var/log/ledxury_backup.log"

echo "=== Instalando backup automático de DB ==="

# 1. Crear directorio
echo "Creando ${BACKUP_DIR}..."
mkdir -p "$BACKUP_DIR"
chown ec2-user:ec2-user "$BACKUP_DIR"
chmod 750 "$BACKUP_DIR"

# 2. Hacer ejecutable el script
chmod +x "$SCRIPT"

# 3. Test corriendo el script ahora (primer backup manual)
echo ""
echo "=== Ejecutando primer backup de prueba ==="
"$SCRIPT"

# 4. Verificar que el archivo existe
LATEST=$(ls -t ${BACKUP_DIR}/ledxury_*.sql.gz 2>/dev/null | head -1)
if [ -z "$LATEST" ]; then
    echo "ERROR: el backup de prueba no se creó. Abortando instalación de cron."
    exit 1
fi
echo "✅ Primer backup OK: $LATEST"

# 5. Instalar entrada en crontab del root (3 AM diario)
CRON_LINE="0 3 * * * ${SCRIPT} >> ${LOG_FILE} 2>&1"
EXISTING=$(crontab -l 2>/dev/null | grep -F "${SCRIPT}" || true)

if [ -n "$EXISTING" ]; then
    echo "ℹ️  Cron ya instalado: ${EXISTING}"
else
    echo ""
    echo "=== Agregando entrada al crontab del root ==="
    (crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -
    echo "✅ Cron instalado: corre cada día a las 3:00 AM"
fi

# 6. Crear log file con permisos correctos
touch "$LOG_FILE"
chmod 644 "$LOG_FILE"

echo ""
echo "=== Resumen ==="
echo "Script:     $SCRIPT"
echo "Backups en: $BACKUP_DIR"
echo "Log:        $LOG_FILE"
echo "Schedule:   diario a las 3 AM"
echo "Retención:  7 días"
echo ""
echo "Para verificar mañana después del 1er run automático:"
echo "  ls -lh $BACKUP_DIR"
echo "  tail $LOG_FILE"
echo ""
echo "Para restaurar un backup:"
echo "  zcat $BACKUP_DIR/ledxury_YYYYMMDD_HHMM.sql.gz | mysql -u <user> -p <db>"
