SET FOREIGN_KEY_CHECKS = 0;

-- Payment agreements
CREATE TABLE IF NOT EXISTS payment_agreements (
  idAgreement INT AUTO_INCREMENT PRIMARY KEY,
  clientId INT NOT NULL,
  agreementDate DATE NOT NULL,
  totalDebt DECIMAL(15,2) NOT NULL,
  numberOfInstallments INT NOT NULL,
  startDate DATE NOT NULL,
  frequency ENUM('semanal','quincenal','mensual') DEFAULT 'mensual',
  notes TEXT,
  createdBy VARCHAR(100),
  status ENUM('activo','completado','incumplido','cancelado') DEFAULT 'activo',
  created_at DATETIME, updated_at DATETIME, deleted_at DATETIME, deleted TINYINT DEFAULT 0,
  KEY idx_client (clientId), KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS payment_agreement_installments (
  idInstallment INT AUTO_INCREMENT PRIMARY KEY,
  agreementId INT NOT NULL,
  installmentNumber INT NOT NULL,
  dueDate DATE NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  paidAmount DECIMAL(15,2) DEFAULT 0,
  paidDate DATE,
  paymentId INT,
  status ENUM('pendiente','pagada','vencida','parcial') DEFAULT 'pendiente',
  KEY idx_agreement (agreementId), KEY idx_due (dueDate), KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Collection activities
CREATE TABLE IF NOT EXISTS collection_activities (
  idActivity INT AUTO_INCREMENT PRIMARY KEY,
  clientId INT NOT NULL,
  activityType ENUM('llamada','visita','whatsapp','email','promesa_pago','otro') NOT NULL,
  activityDate DATETIME NOT NULL,
  description TEXT,
  promiseDate DATE,
  promiseAmount DECIMAL(15,2),
  result ENUM('contactado','no_contactado','promesa','rechazo','pago_parcial','pago_total'),
  nextFollowUp DATE,
  createdBy VARCHAR(100),
  created_at DATETIME, updated_at DATETIME, deleted TINYINT DEFAULT 0,
  KEY idx_client (clientId), KEY idx_date (activityDate), KEY idx_followup (nextFollowUp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Bank statement lines
CREATE TABLE IF NOT EXISTS bank_statement_lines (
  idLine INT AUTO_INCREMENT PRIMARY KEY,
  reconciliationId INT NOT NULL,
  bankAccountId INT NOT NULL,
  transactionDate DATE NOT NULL,
  description VARCHAR(500),
  reference VARCHAR(100),
  debit DECIMAL(15,2) DEFAULT 0,
  credit DECIMAL(15,2) DEFAULT 0,
  balance DECIMAL(15,2) DEFAULT 0,
  matchedMovementId INT,
  matchStatus ENUM('pendiente','matched','unmatched_bank','manual') DEFAULT 'pendiente',
  matchedAt DATETIME,
  matchedBy VARCHAR(100),
  rowNumber INT,
  created_at DATETIME, updated_at DATETIME, deleted TINYINT DEFAULT 0,
  KEY idx_reconciliation (reconciliationId),
  KEY idx_bank (bankAccountId),
  KEY idx_date (transactionDate),
  KEY idx_match (matchStatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Statement logs
CREATE TABLE IF NOT EXISTS statement_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clientId INT NOT NULL,
  sentBy VARCHAR(100),
  sentVia ENUM('email','whatsapp','download'),
  sentAt DATETIME,
  statementDate DATE,
  totalBalance DECIMAL(15,2),
  KEY idx_client (clientId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Alter existing tables
ALTER TABLE clients ADD COLUMN IF NOT EXISTS creditLimit DECIMAL(15,2) DEFAULT 0;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS creditBlocked TINYINT DEFAULT 0;

ALTER TABLE bank_reconciliations ADD COLUMN IF NOT EXISTS statementFilePath VARCHAR(255);
ALTER TABLE bank_reconciliations ADD COLUMN IF NOT EXISTS totalMatched INT DEFAULT 0;
ALTER TABLE bank_reconciliations ADD COLUMN IF NOT EXISTS totalUnmatchedBank INT DEFAULT 0;
ALTER TABLE bank_reconciliations ADD COLUMN IF NOT EXISTS totalUnmatchedSystem INT DEFAULT 0;
ALTER TABLE bank_reconciliations ADD COLUMN IF NOT EXISTS periodMonth INT;
ALTER TABLE bank_reconciliations ADD COLUMN IF NOT EXISTS periodYear INT;

ALTER TABLE cash_movements ADD COLUMN IF NOT EXISTS reconciled TINYINT DEFAULT 0;
ALTER TABLE cash_movements ADD COLUMN IF NOT EXISTS reconciledLineId INT;

SET FOREIGN_KEY_CHECKS = 1;
