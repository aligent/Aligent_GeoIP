<?php


/**
 * @description    GeoIP helper functions
 *
 * @category    Aligent
 * @package     Aligent_GeoIP
 * @copyright   Copyright (c) 2013 Aligent Consulting. (http://www.aligent.com.au)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @author         Luke Mills <luke@aligent.com.au>
 */
class Aligent_GeoIP_Helper_Data extends Mage_Core_Helper_Abstract {

    const VARNISH_XGEOIP_SERVER_VARIABLE = 'HTTP_X_GEOIP';
    const VARNISH_XGEOIP_SERVER_HEADER = 'X-GeoIP';
    const CLOUDFLARE_GEOIP_SERVER_HEADER = 'CF-IPCountry';
    const CLOUDFLARE_GEOIP_SERVER_VARIABLE = 'HTTP_CF_IPCOUNTRY';
    public $aGeoIpHeaders = array(
        self::VARNISH_XGEOIP_SERVER_HEADER   => self::VARNISH_XGEOIP_SERVER_VARIABLE,
        self::CLOUDFLARE_GEOIP_SERVER_HEADER => self::CLOUDFLARE_GEOIP_SERVER_VARIABLE,
    );

    protected $geoIpDatDirs = array('geoip', '/usr/share/GeoIP');

    /**
     * Autodetects the country name the user is currently in based on the following fallback mechanism: 1. Varnish, 2. The user's IP.
     *
     * @return string|false The country name or false if none was found.
     */
    public function autodetectCountryName() {
        $countryCode = $this->autodetectCountry();
        if (!$countryCode || !isset(self::$CountryNames[$countryCode])) {
            return false;
        }

        return self::$CountryNames[$countryCode];
    }

    /**
     * Returns the two letter country code for $ipAddr.
     * @param string    $ipAddr     The IP address in dot-decimal form.
     * @return string|false         The two letter country code or false if none was found.
     */
    public function getCountryByIpv4Addr($ipAddr)
    {
        $country = geoip_country_code_by_name($ipAddr);
        return $country != '' ? $country : false;
    }

    /** Returns the two letter continent code for the country associated with $ipAddr
     * @param $ipAddr               The IP address in dot-decimal form.
     * @return bool|null            The two letter continent code or false if none was found.
     * @throws Exception
     */
    public function getContinentByIpv4Addr($ipAddr) {
        $continent = geoip_continent_code_by_name($ipAddr);
        return $continent != '' ? $continent : false;
    }


    public function getRecordByIpv4Addr()
    {
        $ip = $this->getUserIpv4Addr();
        return geoip_record_by_name($ip);
    }

    /**
     * Returns the two letter country code that was set in the X-GeoIP server variable.
     * @return string|false         The two letter country code or false if none was found / unknown.
     */
    public function getCountryFromVarnish()
    {
        $country = false;

        foreach ($this->aGeoIpHeaders as $vHeader => $vServerVariable) {
            if (isset($_SERVER[$vServerVariable])) {
                $country = $_SERVER[$vServerVariable];
                if (!is_string($country) || '' == $country || 'unknown' == strtolower($country)) {
                    return false;
                }
            }
        }
        return $country;
    }

    /**
     * Returns the user's IP address, attempting to get the user's 'real' address by checking all forwarded headers etc.
     *
     * @link http://www.kavoir.com/2010/03/php-how-to-detect-get-the-real-client-ip-address-of-website-visitors.html
     *
     * @return string|false     The IP address (a string representation of the dot-decimal notation). Returns false if
     *                          an IP address is not found.
     */
    public function getUserIpv4Addr()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Get the country name adjective
     * E.g. 'Australia' returns 'Australian'
     *
     * @param string $countryName The country name to adjective-ise
     *
     * @returns string The adjective, if known, otherwise the country that was passed in
     */
    public function getCountryNameAdjective($countryName) {
        switch ($countryName) {
            case "Australia":
                return $this->__("Australian");
            default:
                return $countryName;
        }
    }
}
