#!/usr/bin/env bash
# ============================================================================
# Deploy seguro a producción (ledxury.com), archivo por archivo.
#
#   ./db/deploy.sh application/controllers/sisvent/admin/Payments.php [más...]
#   ./db/deploy.sh --check application/...        (solo revisa, no escribe)
#   ./db/deploy.sh --force application/...        (sobrescribe aunque el
#                                                  servidor tenga algo que no
#                                                  está en git — úsalo sabiendo)
#
# POR QUÉ EXISTE
# El deploy con SCP suelto no verifica nada, y eso ya costó dos veces:
#   - 14/08: se encontraron 43 archivos en el servidor cuyo contenido no
#     existía en NINGÚN commit (~2.900 líneas). Cada SCP podía borrarlos.
#   - 13/08: al subir Invoices.php con un arreglo se fue también un redirect
#     a /v2/facturas que solo existe en local; el listado de facturas quedó
#     en 404 una hora.
#
# QUÉ VERIFICA, EN ORDEN
#   1. php -l local (no sube nada que no compile).
#   2. Si el servidor ya tiene ese contenido → no hace nada.
#   3. Si difiere, se trae la versión del servidor y pregunta a git si ese
#      contenido existe en algún commit:
#        · sí existe  → es una versión conocida, sobrescribir es recuperable.
#        · NO existe  → ABORTA. Es código que solo vive en el servidor y se
#                       perdería. Hay que rescatarlo a git primero.
#   4. Respalda el archivo en el servidor antes de sobrescribir.
#   5. Copia con nombre temporal único (dos archivos distintos pueden
#      llamarse view.php: pisarse en /tmp ya rompió un deploy).
#   6. chown apache:apache + php -l en el servidor.
#   7. Confirma que el hash quedó igual al local.
#
# Compara siempre normalizando fin de línea: el working tree es CRLF y el
# repo guarda LF, así que sin normalizar todo parece distinto.
# ============================================================================

set -uo pipefail

HOST="ec2-user@54.145.102.94"   # servidor nuevo desde 24/08/2026; el dominio aun apunta a la IP muerta
KEY="db/Amazon_MAM.pem"
ROOT="/var/www/html"

MODE="deploy"
case "${1:-}" in
    --check) MODE="check"; shift ;;
    --force) MODE="force"; shift ;;
esac

if [ $# -eq 0 ]; then
    echo "uso: $0 [--check|--force] <archivo> [archivo...]" >&2
    exit 1
fi

if [ ! -f "$KEY" ]; then
    echo "ERROR: no encuentro la llave $KEY. Ejecútalo desde la raíz del repo." >&2
    exit 1
fi

FILES=("$@")
TMPDIR=$(mktemp -d)
STAMP=$(date +%Y%m%d_%H%M%S)
BKDIR="/tmp/deploy_bak_$STAMP"
trap 'rm -rf "$TMPDIR"' EXIT

hash_norm() { tr -d '\r' < "$1" | md5sum | cut -d' ' -f1; }

# En Git Bash php no está en el PATH; el de XAMPP sí sirve.
PHP_BIN="${PHP_BIN:-}"
if [ -z "$PHP_BIN" ]; then
    if command -v php > /dev/null 2>&1;        then PHP_BIN="php"
    elif [ -x "/c/xampp/php/php.exe" ];        then PHP_BIN="/c/xampp/php/php.exe"
    elif [ -x "/xampp/php/php.exe" ];          then PHP_BIN="/xampp/php/php.exe"
    fi
fi

# ── 1. Lint local ───────────────────────────────────────────────────────────
echo "── 1. php -l local"
if [ -z "$PHP_BIN" ]; then
    echo "  AVISO: no encontré php local, me salto este paso (el servidor igual valida)."
    echo "         Si quieres el chequeo local: PHP_BIN=/ruta/a/php.exe $0 ..."
fi
for f in "${FILES[@]}"; do
    if [ ! -f "$f" ]; then echo "  ERROR: no existe $f" >&2; exit 1; fi
    if [ -n "$PHP_BIN" ]; then
        case "$f" in
            *.php) if ! "$PHP_BIN" -l "$f" > /dev/null 2>&1; then
                       echo "  ERROR de sintaxis en $f — no se sube nada" >&2
                       "$PHP_BIN" -l "$f"; exit 1
                   fi ;;
        esac
    fi
    echo "  ok  $f"
done

# ── 1b. GUARDA DE MULTI-TENANT ───────────────────────────────────────────────
# Multi-tenant quedó ARCHIVADO (decisión de Alex, reconfirmada 22/08/2026).
# Producción no tiene tabla `tenants` ni columnas `tenant_id`. Dos cosas ya
# tumbaron el sitio por esto y no vuelven a pasar sin aviso:
#   · Un modelo que declara `extends MY_Model` sin que exista
#     application/core/MY_Model.php en el servidor → "Class MY_Model not found"
#     y la página entera muere.
#   · Las migraciones 060 y 065 agregan tenant_id: romperían todas las
#     consultas contra tablas que no tienen esa columna.
for f in "${FILES[@]}"; do
    case "$f" in
        *060_pulso_multitenant*|*065_tenant_id*)
            echo "" >&2
            echo "════ ALTO ════" >&2
            echo "  $f es una migración de MULTI-TENANT y eso está archivado." >&2
            echo "  Producción no tiene tenant_id en ninguna tabla; aplicarla rompe todo." >&2
            echo "  Ver db/recuperacion/RESTAURAR_PRODUCCION.md" >&2
            exit 1 ;;
    esac
    if grep -q "extends MY_Model" "$f" 2>/dev/null; then
        NEEDS_SHIM=1
        echo "  aviso  $f extiende MY_Model — se verificará el puente en el servidor"
    fi
done

# ── 2. Hashes del servidor, en una sola conexión ─────────────────────────────
echo "── 2. estado en el servidor"
if [ "${NEEDS_SHIM:-0}" = "1" ]; then
    if ! ssh -o ConnectTimeout=30 -i "$KEY" "$HOST" "test -f $ROOT/application/core/MY_Model.php" 2>/dev/null; then
        echo "" >&2
        echo "════ ALTO ════" >&2
        echo "  Vas a subir un modelo que extiende MY_Model, y el servidor NO tiene" >&2
        echo "  application/core/MY_Model.php. Eso tumba la página completa." >&2
        echo "  Sube primero el puente:" >&2
        echo "    scp -i $KEY db/prod_variants/MY_Model.single-tenant.php \\" >&2
        echo "        $HOST:/tmp/MY_Model.php" >&2
        echo "    ssh -i $KEY $HOST \"sudo cp /tmp/MY_Model.php $ROOT/application/core/MY_Model.php\"" >&2
        exit 1
    fi
    echo "  puente MY_Model presente en el servidor: ok"
fi
printf '%s\n' "${FILES[@]}" > "$TMPDIR/list.txt"
ssh -o ConnectTimeout=30 -i "$KEY" "$HOST" "cd $ROOT && while read -r p; do
    if [ -f \"\$p\" ]; then printf '%s %s\n' \"\$(tr -d '\r' < \"\$p\" | md5sum | cut -d' ' -f1)\" \"\$p\";
    else printf 'AUSENTE %s\n' \"\$p\"; fi
done" < "$TMPDIR/list.txt" > "$TMPDIR/remote.txt" || { echo "ERROR: no pude leer el servidor" >&2; exit 1; }

A_SUBIR=(); IGUALES=0; HUERFANOS=(); AJENOS=()
for f in "${FILES[@]}"; do
    rhash=$(awk -v p="$f" '$2==p {print $1}' "$TMPDIR/remote.txt")
    lhash=$(hash_norm "$f")

    if [ -z "$rhash" ]; then
        echo "  ?   $f — el servidor no respondió por este archivo"; continue
    fi
    if [ "$rhash" = "AUSENTE" ]; then
        echo "  NUEVO      $f (no existe en el servidor)"; A_SUBIR+=("$f"); continue
    fi
    if [ "$rhash" = "$lhash" ]; then
        echo "  ya está    $f"; IGUALES=$((IGUALES+1)); continue
    fi

    # Difiere: ¿lo que tiene el servidor existe en algún commit?
    mkdir -p "$TMPDIR/remote/$(dirname "$f")"
    scp -q -i "$KEY" "$HOST:$ROOT/$f" "$TMPDIR/remote/$f" 2>/dev/null || true
    if [ -f "$TMPDIR/remote/$f" ]; then
        tr -d '\r' < "$TMPDIR/remote/$f" > "$TMPDIR/remote/$f.lf"
        sha=$(git hash-object "$TMPDIR/remote/$f.lf" 2>/dev/null)
        if [ -n "$sha" ] && git cat-file -e "$sha" 2>/dev/null; then
            # Existe en git, pero ¿esta rama ya pasó por esa versión? Si el blob
            # solo vive en otra rama (ej. el snapshot de producción), el servidor
            # tiene trabajo que esta rama nunca tuvo y subir encima lo revierte.
            ancestro="no"
            for c in $(git log --all --format=%H --find-object="$sha" -- "$f" 2>/dev/null | head -20); do
                if git merge-base --is-ancestor "$c" HEAD 2>/dev/null; then ancestro="si"; break; fi
            done
            if [ "$ancestro" = "si" ]; then
                echo "  difiere    $f (el servidor tiene una versión que esta rama ya superó)"
                A_SUBIR+=("$f")
            else
                donde=$(git branch -a --contains "$(git log --all --format=%H --find-object="$sha" -- "$f" 2>/dev/null | head -1)" 2>/dev/null | head -3 | tr -d ' *' | paste -sd, -)
                echo "  AJENO      $f (el servidor tiene trabajo que esta rama NO contiene${donde:+; está en $donde})"
                AJENOS+=("$f")
            fi
        else
            echo "  HUERFANO   $f (el servidor tiene contenido que NO está en git)"
            HUERFANOS+=("$f")
        fi
    else
        echo "  ?   $f — no pude traer la versión del servidor"
    fi
done

# ── 3. Freno de mano ────────────────────────────────────────────────────────
if [ ${#AJENOS[@]} -gt 0 ]; then
    echo
    echo "════ OJO ════"
    echo "El servidor tiene trabajo que esta rama no contiene en:"
    printf '   %s\n' "${AJENOS[@]}"
    echo
    echo "Está respaldado en git, pero subir la versión de esta rama lo revierte"
    echo "en producción. Primero tráelo a tu rama (o haz merge de la rama que lo tiene):"
    for a in "${AJENOS[@]}"; do echo "   git checkout prod/snapshot-20260814 -- $a   # y revisa el diff"; done
    echo
    if [ "$MODE" != "force" ]; then
        echo "Aborto. Con --force se sube igual (queda respaldo en $BKDIR)."
        exit 2
    fi
    echo "--force activo: se revierte igual."
    A_SUBIR+=("${AJENOS[@]}")
fi

if [ ${#HUERFANOS[@]} -gt 0 ]; then
    echo
    echo "════ ALTO ════"
    echo "El servidor tiene contenido que no existe en ningún commit de git en:"
    printf '   %s\n' "${HUERFANOS[@]}"
    echo
    echo "Subir encima lo borra sin forma de recuperarlo. Recupéralo primero:"
    echo "   git checkout -b prod/rescate-\$(date +%Y%m%d)"
    for h in "${HUERFANOS[@]}"; do echo "   scp -i $KEY $HOST:$ROOT/$h $h"; done
    echo "   git add -A && git commit -m 'Rescate: versión de producción'"
    echo
    if [ "$MODE" != "force" ]; then
        echo "Aborto. Si de verdad quieres sobrescribirlo, repite con --force."
        exit 2
    fi
    echo "--force activo: se sobrescriben igual (queda el respaldo en $BKDIR)."
    A_SUBIR+=("${HUERFANOS[@]}")
fi

if [ "$MODE" = "check" ]; then
    echo; echo "Modo --check: no se escribió nada. Listos para subir: ${#A_SUBIR[@]}, ya iguales: $IGUALES"
    exit 0
fi
if [ ${#A_SUBIR[@]} -eq 0 ]; then
    echo; echo "Nada por hacer: los $IGUALES archivos ya están desplegados."
    exit 0
fi

# ── 4. Subir ────────────────────────────────────────────────────────────────
echo "── 3. subiendo ${#A_SUBIR[@]} archivo(s)"
ssh -o ConnectTimeout=30 -i "$KEY" "$HOST" "mkdir -p $BKDIR" || exit 1

FALLOS=0
for f in "${A_SUBIR[@]}"; do
    plano=$(echo "$f" | tr '/' '_')          # nombre único: dos view.php no se pisan
    scp -q -i "$KEY" "$f" "$HOST:/tmp/dep_$plano" || { echo "  ERROR subiendo $f"; FALLOS=$((FALLOS+1)); continue; }
    out=$(ssh -o ConnectTimeout=30 -i "$KEY" "$HOST" "
        if [ -f $ROOT/$f ]; then sudo cp $ROOT/$f $BKDIR/$plano; fi
        sudo mkdir -p \$(dirname $ROOT/$f)
        sudo cp /tmp/dep_$plano $ROOT/$f && sudo chown apache:apache $ROOT/$f || exit 1
        case '$f' in *.php) php -l $ROOT/$f > /dev/null || exit 3 ;; esac
        tr -d '\r' < $ROOT/$f | md5sum | cut -d' ' -f1
        rm -f /tmp/dep_$plano
    " 2>&1)
    rc=$?
    nuevo=$(echo "$out" | tail -1)
    if [ $rc -ne 0 ]; then
        echo "  ERROR  $f → $out"; FALLOS=$((FALLOS+1))
    elif [ "$nuevo" = "$(hash_norm "$f")" ]; then
        echo "  ok     $f"
    else
        echo "  ERROR  $f — el hash del servidor no coincide con el local"; FALLOS=$((FALLOS+1))
    fi
done

echo
echo "── resumen"
echo "  subidos:   $((${#A_SUBIR[@]} - FALLOS))/${#A_SUBIR[@]}"
echo "  sin cambio: $IGUALES"
echo "  respaldos:  $BKDIR (en el servidor)"
[ $FALLOS -gt 0 ] && { echo "  FALLARON:  $FALLOS — revísalos antes de dar por hecho el deploy"; exit 1; }
echo "  Recuerda: si tocaste algo que Jorge también usa, avísale."
exit 0
