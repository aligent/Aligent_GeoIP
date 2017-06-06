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
     * Aligent_GeoIP_Helper_Data constructor
     *
     * can use rewrite to change include paths completely if needed
     *
     */
    public function __construct()
    {
        // Prefers local overwrites if present, otherwise fallsback to vendor
        if (file_exists(Mage::getBaseDir() . "/geoip/geoip.inc")) {
            require_once Mage::getBaseDir() . "/geoip/geoip.inc";
            require_once Mage::getBaseDir() . "/geoip/geoipcity.inc";
        }
        elseif (file_exists('geoip/geoip.inc')) {
            require_once 'geoip/geoip.inc';
            require_once 'geoip/geoipcity.inc';
        }
        elseif (file_exists(Mage::getBaseDir() . "/vendor/geoip/geoip/src/geoip.inc")) {
            require_once Mage::getBaseDir() . "/vendor/geoip/geoip/src/geoip.inc";
            require_once Mage::getBaseDir() . "/vendor/geoip/geoip/src/geoipcity.inc";
        }
        else{
            throw new Exception('unable to find any geo ip libraries');
        }
    }
    /**
     * Autodetects the country based on the following fallback mechanism: 1. Varnish, 2. The user's IP.
     * @return string|false         The two letter country code or false if none was found.
     */
    public function autodetectCountry()
    {
        $country = false;
        $detectionMethods = array(
            function() {
                if (!Mage::getIsDeveloperMode()) {
                    return false;
                }
                return Mage::app()->getRequest()->getParam('___pretend_country', false);
            },
            array($this, 'getCountryFromVarnish'),
            function () {
                $geoipHelper = Mage::helper('aligent_geoip');
                return $geoipHelper->getCountryByIpv4Addr($geoipHelper->getUserIpv4Addr());
            },
        );
        foreach ($detectionMethods as $detectionMethod) {
            $country = call_user_func($detectionMethod);
            if (false !== $country) {
                break;
            }
        }
        return $country;
    }

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
        $country = null;
        $gi = $this->geoipOpen('GeoIP.dat', GEOIP_STANDARD);
        $country = geoip_country_code_by_addr($gi, $ipAddr);
        geoip_close($gi);
        return $country != '' ? $country : false;
    }

    /** Returns the two letter continent code for the country associated with $ipAddr
     * @param $ipAddr               The IP address in dot-decimal form.
     * @return bool|null            The two letter continent code or false if none was found.
     * @throws Exception
     */
    public function getContinentByIpv4Addr($ipAddr) {
        $continent = null;
        $gi = $this->geoipOpen('GeoIP.dat', GEOIP_STANDARD);
        $country_id = geoip_country_id_by_addr($gi, $ipAddr);
        if ($country_id !== false) {
            $continent = $gi->GEOIP_CONTINENT_CODES[$country_id];
        }

        geoip_close($gi);
        return $continent != '' ? $continent : false;
    }


    public function getRecordByIpv4Addr()
    {
        $ip = $this->getUserIpv4Addr();
        $gi = $this->geoipOpen('GeoLiteCity.dat', GEOIP_STANDARD);
        $record = geoip_record_by_addr($gi,$ip);
        return $record;
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

    private function geoipOpen($filename, $flags) {
        $_filename = $filename;
        try {
            foreach ($this->geoIpDatDirs as $dir) {
                $datFilePath = $dir . DS . $filename;
                if (strpos($datFilePath, '/') !== 0) {
                    // First look for file on the include path
                    if (function_exists('stream_resolve_include_path')) {
                        $_filename = stream_resolve_include_path($datFilePath);
                        if ($_filename !== false) {
                            break;
                        }
                    }
                    // Then look for the file relative to the Magento root.
                    $_filename = Mage::getBaseDir() . DS . $datFilePath;
                    if (file_exists($_filename)) {
                        break;
                    }
                } elseif (file_exists($datFilePath)) {
                    $_filename = $datFilePath;
                    break;
                }
            }

            if (!file_exists($_filename)) {
                throw new Exception (sprintf('Unable to find file: "%s"', $_filename));
            }

            return geoip_open($_filename, $flags);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $message .= PHP_EOL;
            $message .= sprintf('include_path = %s', get_include_path());
            $message .= PHP_EOL;
            $message .= sprintf('cwd = %s', getcwd());
            $message .= PHP_EOL;
            $file = Mage::getStoreConfig('dev/log/exception_file');
            Mage::log("\n" . $message . $e->__toString(), Zend_Log::ERR, $file);
            throw $e;
        }
    }
}
