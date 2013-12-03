<?php
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

ob_start();
?>
    <h1>Content Not Available</h1>
    <p>We're sorry, but this web site is not available in your country.</p>
<?php

$contents = ob_get_clean();

Mage::getModel('cms/page')
    ->load('geoblocked', 'identifier') // This should makes it safe to run this script
                                       // more than once without creating multiple pages.
    ->setTitle('Content Not Available')
    ->setIdentifier('geoblocked')
    ->setIsActive(true)
    ->setUnderVersionControl(false)
    ->setStores(array(0)) // All stores
    ->setContent($contents)
    ->setRootTemplate('one_column')
    ->save();

$installer->setConfigData('aligent_geoip/geoip/redirect_cms', 'geoblocked');

$installer->endSetup();