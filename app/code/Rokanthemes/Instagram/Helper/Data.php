<?php
/**
 * Rokanthemes Infotech
 * Rokanthemes Instagram Extension
 * 
 * @category   Rokanthemes
 * @package    Rokanthemes_Instagram
 * @copyright  Copyright © 2006-2016 Rokanthemes (https://www.rokanthemesinfotech.com)
 * @license    https://www.rokanthemesinfotech.com/magento-extension-license/
 */
namespace Rokanthemes\Instagram\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
     /**
     * Return brand config value by key and store
     *
     * @param string $key
     * @param \Magento\Store\Model\Store|int|string $store
     * @return string|null
     */
    public function getConfig($key)
    {
        $result = $this->scopeConfig->getValue($key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $result;
    }
    
    
    public function getInstangramData($url)
    {
        $ch = curl_init();
        $timeout = 2500;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_PROXY, '192.168.10.1:808');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $errmsg = curl_error($ch);
        $cInfo = curl_getinfo($ch);
        curl_close($ch);

        if ($errmsg == '') {
            return $response;
        } else {
            return $errmsg;
        }
    }
    
    

}
