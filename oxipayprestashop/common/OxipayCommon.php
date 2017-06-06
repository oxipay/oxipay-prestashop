<?php

class OxipayCommon
{
    /**
     * validates and associative array that contains a hmac signature against an api key
     * @param $query array
     * @param $api_key string
     * @return bool
     */
    public static function isValidSignature($query, $api_key)
    {
        $actualSignature = $query['x_signature'];
        unset($query['x_signature']);

        $expectedSignature = self::generateSignature($query, $api_key);
        return $actualSignature == $expectedSignature;
    }

    /**
     * generates a hmac based on an associative array and an api key
     * @param $query array
     * @param $api_key string
     * @return string
     */
    public static function generateSignature($query, $api_key)
    {
        $clear_text = '';
        ksort($query);
        foreach ($query as $key => $value) {
            $clear_text .= $key . $value;
        }
        $hash = hash_hmac( "sha256", $clear_text, $api_key);
        $hash = str_replace('-', '', $hash);
        return $hash;
    }

    public static function getCountryInfoFromGatewayUrl() { //TODO: it seems the admin can change the ISO codes
        $gatewayUrl = Configuration::get('OXIPAY_GATEWAY_URL');
        $gatewayUrlData = parse_url($gatewayUrl);
        $host = $gatewayUrlData['host'];

        if (strpos($host, '.com.au') !== false) {
            return array('countryCode' => 'AU', 'currencyCode' => 'AUD', 'countryName' => 'Australia');
        } else if (strpos($host, '.co.nz') !== false) {
            return array('countryCode' => 'NZ', 'currencyCode' => 'NZD', 'countryName' => 'New Zealand');
        } else {
            $message = "Couldn't determine country from gateway URL: $gatewayUrl";
            PrestaShopLogger::addLog($message, 1);
            throw new Exception($message); 
        }
    }

}