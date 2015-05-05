<?php

class Aligent_GeoIP_Model_Cron {

    public function runUpdateGeoip() {
        $this->_runUpdate('aligent_geoip/geoip/autoupdate_geoip', 'GeoIP.dat.gz');
    }

    public function runUpdateGeolitecity() {
        $this->_runUpdate('aligent_geoip/geoip/autoupdate_geolitecity', 'GeoLiteCity.dat.gz');
    }

    protected function _runUpdate($vStoreKey, $vFilename) {
        $vFolder = Mage::getStoreConfig('aligent_geoip/geoip/folder');
        $vUpdateUrl = Mage::getStoreConfig($vStoreKey);
        if ($vUpdateUrl !== null) {
            $oFile = file_get_contents($vUpdateUrl);
            $vFilename = $vFolder . $vFilename;
            file_put_contents($vFilename, $oFile);
            $this->_gunzip($vFilename);
        }
    }

    protected function _gunzip($vFileName) {
        // Raising this value may increase performance
        $iBufferSize = 4096; // read 4kb at a time
        $vOutFilename = str_replace('.gz', '', $vFileName);
        // Open our files (in binary mode)
        $oFile = gzopen($vFileName, 'rb');
        $oOutFile = fopen($vOutFilename, 'wb');
        // Keep repeating until the end of the input file
        while(!gzeof($oFile)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($oOutFile, gzread($oFile, $iBufferSize));
        }
        // Files are done, close files
        fclose($oOutFile);
        gzclose($oFile);
    }
}