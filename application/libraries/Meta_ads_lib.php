<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Meta_ads_lib — Integración con Meta Marketing API
 * Lee campañas e insights de Facebook/Instagram Ads.
 */
class Meta_ads_lib {

    private $accessToken;
    private $adAccountId;
    private $apiVersion;
    private $baseUrl;

    public function __construct()
    {
        $CI =& get_instance();
        $secretsFile = APPPATH . 'config/secrets.php';
        if (file_exists($secretsFile)) {
            include($secretsFile);
            $meta = isset($config['meta_ads']) ? $config['meta_ads'] : array();
        } else {
            $meta = array();
        }
        $this->accessToken  = isset($meta['access_token']) ? $meta['access_token'] : '';
        $this->adAccountId  = isset($meta['ad_account_id']) ? $meta['ad_account_id'] : '';
        $this->apiVersion   = isset($meta['api_version']) ? $meta['api_version'] : 'v21.0';
        $this->baseUrl      = 'https://graph.facebook.com/' . $this->apiVersion;
    }

    /**
     * Obtener lista de campañas
     */
    public function getCampaigns()
    {
        $url = $this->baseUrl . '/' . $this->adAccountId . '/campaigns'
             . '?fields=id,name,status,objective,daily_budget,lifetime_budget,start_time,stop_time'
             . '&limit=50';

        return $this->_get($url);
    }

    /**
     * Obtener insights por campaña en un rango de fechas
     */
    public function getCampaignInsights($since, $until)
    {
        $url = $this->baseUrl . '/' . $this->adAccountId . '/insights'
             . '?fields=campaign_name,campaign_id,impressions,clicks,spend,cpc,cpm,ctr,actions,cost_per_action_type'
             . '&time_range=' . urlencode('{"since":"' . $since . '","until":"' . $until . '"}')
             . '&level=campaign'
             . '&limit=50';

        return $this->_get($url);
    }

    /**
     * Obtener insights de una campaña específica
     */
    public function getSingleCampaignInsights($campaignId, $since, $until)
    {
        $url = $this->baseUrl . '/' . $campaignId . '/insights'
             . '?fields=campaign_name,impressions,clicks,spend,cpc,cpm,ctr,actions,cost_per_action_type'
             . '&time_range=' . urlencode('{"since":"' . $since . '","until":"' . $until . '"}');

        return $this->_get($url);
    }

    /**
     * Obtener insights diarios de una campaña
     */
    public function getDailyInsights($campaignId, $since, $until)
    {
        $url = $this->baseUrl . '/' . $campaignId . '/insights'
             . '?fields=campaign_name,impressions,clicks,spend,cpc,ctr,actions'
             . '&time_range=' . urlencode('{"since":"' . $since . '","until":"' . $until . '"}')
             . '&time_increment=1'
             . '&limit=90';

        return $this->_get($url);
    }

    /**
     * Obtener insights por cuenta (totales)
     */
    public function getAccountInsights($since, $until)
    {
        $url = $this->baseUrl . '/' . $this->adAccountId . '/insights'
             . '?fields=impressions,clicks,spend,cpc,cpm,ctr,actions,cost_per_action_type'
             . '&time_range=' . urlencode('{"since":"' . $since . '","until":"' . $until . '"}');

        return $this->_get($url);
    }

    /**
     * Extraer conversaciones (messaging_connection) del array de actions
     */
    public function extractConversations($actions)
    {
        if (!is_array($actions)) return 0;
        foreach ($actions as $a) {
            $a = (array) $a;
            if (isset($a['action_type']) && $a['action_type'] === 'onsite_conversion.total_messaging_connection') {
                return (int) $a['value'];
            }
        }
        return 0;
    }

    /**
     * Extraer costo por conversación del array de cost_per_action_type
     */
    public function extractCostPerConversation($costActions)
    {
        if (!is_array($costActions)) return 0;
        foreach ($costActions as $a) {
            $a = (array) $a;
            if (isset($a['action_type']) && $a['action_type'] === 'onsite_conversion.total_messaging_connection') {
                return round((float) $a['value'], 2);
            }
        }
        return 0;
    }

    /**
     * Verificar si el token es válido
     */
    public function validateToken()
    {
        $url = $this->baseUrl . '/me?access_token=' . $this->accessToken;
        $result = $this->_get($url);
        return !isset($result['error']);
    }

    private function _get($url)
    {
        $url .= (strpos($url, '?') !== false ? '&' : '?') . 'access_token=' . $this->accessToken;

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $response = curl_exec($ch);
        if ($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return array('error' => $err);
        }

        curl_close($ch);
        return json_decode($response, true);
    }
}
