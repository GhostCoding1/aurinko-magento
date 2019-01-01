<?php
namespace Piimega\CheckoutFinland\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

/**
 * CheckoutFinland Data helper
 */
class Data extends AbstractHelper{

    /**
     * @param string $url
     * @param array $post
     * @return array
     */
    public function sendPost($url, $post) {

        $options = array(
            CURLOPT_POST 			=> 1,
            CURLOPT_HEADER 			=> 0,
            CURLOPT_URL 			=> $url,
            CURLOPT_FRESH_CONNECT 	=> 1,
            CURLOPT_RETURNTRANSFER 	=> 1,
            CURLOPT_FORBID_REUSE 	=> 1,
            CURLOPT_TIMEOUT 		=> 20,
            CURLOPT_POSTFIELDS 		=> http_build_query($post)
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = simplexml_load_string($result);
        return json_decode(json_encode($result), true);
    }

    /**
     * @param array $result
     * @return array
     */
    public function prepareMethodsArray($result){
        $methods = [];
        if(!empty($result) && isset($result['payments']['payment'])){
            foreach($result['payments']['payment'] as $group){
                if(is_array($group)){
                    foreach($group as $code => $method){
                        $methods[$code] = $method;
                    }
                }
            }
        }
        return $methods;
    }
}