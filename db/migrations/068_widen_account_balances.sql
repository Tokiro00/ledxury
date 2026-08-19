-- ============================================================================
-- 068: Ampliar las columnas de saldos contables de DECIMAL(10,2) a (18,2)
--
-- DECIMAL(10,2) topa en $99.999.999,99. Con el saldo inicial de MAM-Online
-- ($129.308.187) el auxiliar MAM y la subcuenta 2205 lo superan, y los
-- acumulados de débito/crédito del 1435 ya iban en ~$131M: el UPDATE de
-- saldos reventaba con "Out of range value for column 'accountDebit'"
-- (falló el apply del saldo inicial el 19/08/2026; transacción revertida).
-- El mismo tope amenazaba updateAccountBalance de Accounting_lib en el flujo
-- normal. Ampliar es seguro: solo cambia la capacidad, no los datos.
-- ============================================================================

ALTER TABLE subaccounts
  MODIFY accountBalance DECIMAL(18,2) NOT NULL,
  MODIFY accountDebit   DECIMAL(18,2) NOT NULL,
  MODIFY accountCredit  DECIMAL(18,2) NOT NULL;

ALTER TABLE auxiliary_subaccounts
  MODIFY accountBalance DECIMAL(18,2) NOT NULL,
  MODIFY accountDebit   DECIMAL(18,2) NOT NULL,
  MODIFY accountCredit  DECIMAL(18,2) NOT NULL;
