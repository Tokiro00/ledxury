<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Accountingsettings_model extends CI_Model {

    // ========================================================================
    // CRUD BASICO
    // ========================================================================

    public function getSettings() {
        $this->db->select('accounting_settings.*, subaccounts.accountName as subaccount_name, subaccounts.pucCode as subaccount_puc');
        $this->db->from('accounting_settings');
        $this->db->join('subaccounts', 'subaccounts.id = accounting_settings.subaccount_id', 'left');
        $this->db->order_by('accounting_settings.id', 'asc');
        return $this->db->get()->result();
    }

    public function getSetting($key) {
        $this->db->from('accounting_settings');
        $this->db->where('setting_key', $key);
        return $this->db->get()->row();
    }

    public function getSettingValue($key) {
        $setting = $this->getSetting($key);
        return $setting ? $setting->subaccount_id : null;
    }

    /**
     * Alias usado por Accounting_lib::getConfiguredAccount().
     * Devuelve el subaccount_id mapeado a $key (NULL si no hay).
     */
    public function getSubaccountId($key) {
        return $this->getSettingValue($key);
    }

    /**
     * Devuelve el código PUC asociado al setting (via JOIN con subaccounts).
     * Usado por Accounting_lib::getConfiguredPucCode().
     */
    public function getPucCode($key) {
        $this->db->select('subaccounts.pucCode');
        $this->db->from('accounting_settings');
        $this->db->join('subaccounts', 'subaccounts.id = accounting_settings.subaccount_id', 'left');
        $this->db->where('accounting_settings.setting_key', $key);
        $row = $this->db->get()->row();
        return $row ? $row->pucCode : null;
    }

    public function saveSetting($key, $subaccountId) {
        date_default_timezone_set("America/Bogota");
        $existing = $this->getSetting($key);

        if ($existing) {
            $this->db->where('setting_key', $key);
            return $this->db->update('accounting_settings', array(
                'subaccount_id' => $subaccountId,
                'updated_at' => date('Y-m-d H:i:s')
            ));
        } else {
            return $this->db->insert('accounting_settings', array(
                'setting_key' => $key,
                'subaccount_id' => $subaccountId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ));
        }
    }

    public function saveMultiple($settings) {
        foreach ($settings as $key => $subaccountId) {
            $this->saveSetting($key, $subaccountId ?: null);
        }
        return true;
    }

    // ========================================================================
    // UTILITARIOS
    // ========================================================================

    public function lastID() {
        return $this->db->insert_id();
    }
}
