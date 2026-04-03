// ============================================================
// AGREGAR ESTE CÓDIGO AL APPS SCRIPT EXISTENTE
// Sincroniza ventas y mensajes con MAM ERP
// ============================================================

// ⚠️ CONFIGURACIÓN MAM - Cambiar por tu URL de producción
const MAM_BASE_URL = 'https://TU_DOMINIO/ledxury';  // ← CAMBIAR
const MAM_WEBHOOK_SECRET = 'wh_mam_builderbot_2026';

// Columna R (índice 17) = "MySQL" — marcamos "OK" cuando se sincroniza
const COL_MYSQL = 17;

/**
 * TRIGGER: Se ejecuta cuando se edita el Sheet.
 * Configurar en Apps Script: Triggers → Add Trigger → onSheetEdit → On edit
 *
 * Detecta filas nuevas/modificadas y las envía a MAM.
 */
function onSheetEdit(e) {
  try {
    const sheet = e.source.getSheetByName(SHEET_NAME);
    if (!sheet) return;

    // Solo procesar si se editó la hoja "Registros"
    if (e.range.getSheet().getName() !== SHEET_NAME) return;

    const row = e.range.getRow();
    if (row <= 1) return; // Ignorar header

    const data = sheet.getRange(row, 1, 1, 20).getValues()[0];

    // Solo sincronizar si hay nombre y documento y NO está marcado como OK en MySQL
    const nombre = String(data[COL_NOMBRE] || '').trim();
    const documento = String(data[COL_DOCUMENTO] || '').trim();
    const mysqlStatus = String(data[COL_MYSQL] || '').trim().toUpperCase();

    if (nombre && documento && mysqlStatus !== 'OK') {
      syncRowToMAM(sheet, data, row);
    }
  } catch (err) {
    Logger.log('onSheetEdit error: ' + err.message);
  }
}

/**
 * FUNCIÓN MANUAL: Sincroniza TODAS las filas que no tienen "OK" en columna MySQL (R).
 * Ejecutar manualmente o programar con trigger de tiempo.
 */
function syncAllPendingRows() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName(SHEET_NAME);
  const allData = sheet.getDataRange().getValues();

  let synced = 0;
  let errors = 0;

  for (let i = 1; i < allData.length; i++) {
    const nombre = String(allData[i][COL_NOMBRE] || '').trim();
    const documento = String(allData[i][COL_DOCUMENTO] || '').trim();
    const mysqlStatus = String(allData[i][COL_MYSQL] || '').trim().toUpperCase();

    if (nombre && documento && mysqlStatus !== 'OK') {
      const result = syncRowToMAM(sheet, allData[i], i + 1);
      if (result) synced++;
      else errors++;

      // Esperar un poco entre filas para no saturar
      Utilities.sleep(500);
    }
  }

  Logger.log('Sync completado: ' + synced + ' sincronizadas, ' + errors + ' errores');
  return { synced: synced, errors: errors };
}

/**
 * Envía una fila del Sheet a MAM y marca "OK" en columna MySQL si tiene éxito.
 */
function syncRowToMAM(sheet, rowData, rowNumber) {
  try {
    const payload = {
      nombre:    String(rowData[COL_NOMBRE] || '').trim(),
      documento: String(rowData[COL_DOCUMENTO] || '').trim(),
      direccion: String(rowData[3] || '').trim(),    // D: direccion
      productos: String(rowData[4] || '').trim(),    // E: productos
      cantidad:  String(rowData[5] || '').trim(),    // F: cantidad
      voltaje:   String(rowData[6] || '').trim(),    // G: voltaje
      color:     String(rowData[7] || '').trim(),    // H: color
      celular:   String(rowData[COL_CELULAR] || '').trim(),
      total:     parseFloat(rowData[9]) || 0,        // J: total
      fecha:     String(rowData[10] || '').trim(),   // K: fecha
      vendedor:  String(rowData[11] || '').trim(),   // L: vendedor
      tipoenvio: String(rowData[13] || 'Gratis').trim(), // N: TipoEnvio
      row_index: rowNumber
    };

    const options = {
      method: 'post',
      contentType: 'application/json',
      headers: {
        'X-Webhook-Secret': MAM_WEBHOOK_SECRET
      },
      payload: JSON.stringify(payload),
      muteHttpExceptions: true
    };

    const response = UrlFetchApp.fetch(MAM_BASE_URL + '/webhook/sheet-sync', options);
    const code = response.getResponseCode();
    const body = JSON.parse(response.getContentText());

    if (code >= 200 && code < 300 && body.success) {
      // Marcar OK en columna R (MySQL)
      sheet.getRange(rowNumber, COL_MYSQL + 1).setValue('OK');
      Logger.log('Fila ' + rowNumber + ' sincronizada → Presupuesto #' + body.budget_id);
      return true;
    } else {
      Logger.log('Fila ' + rowNumber + ' error: ' + (body.error || 'HTTP ' + code));
      sheet.getRange(rowNumber, COL_MYSQL + 1).setValue('ERROR: ' + (body.error || 'HTTP ' + code));
      return false;
    }
  } catch (e) {
    Logger.log('syncRowToMAM error fila ' + rowNumber + ': ' + e.message);
    return false;
  }
}

// ============================================================
// MODIFICAR LA FUNCIÓN sendWhatsAppMessage EXISTENTE
// Agregar al FINAL de la función (antes del return), este bloque:
// ============================================================

/*
  // ── LOG EN MAM ──
  try {
    var logPayload = {
      phone: formattedPhone,
      content: mensaje,
      success: (responseCode >= 200 && responseCode < 300)
    };

    UrlFetchApp.fetch(MAM_BASE_URL + '/webhook/sheet-message', {
      method: 'post',
      contentType: 'application/json',
      headers: { 'X-Webhook-Secret': MAM_WEBHOOK_SECRET },
      payload: JSON.stringify(logPayload),
      muteHttpExceptions: true
    });
  } catch (logErr) {
    // No bloquear si falla el log
    Logger.log('Error logging message to MAM: ' + logErr.message);
  }
*/
