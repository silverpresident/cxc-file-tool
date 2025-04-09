<?php
/**
 * @version 20190415.11
 */
if (!defined('APP_LOADED')) die('Direct file access is not allowed');
defined('LOCUS') OR define('LOCUS', 'https://shaneedwards.biz/cxc');
defined('CORE_DIR') OR define('CORE_DIR', __DIR__ . DIRECTORY_SEPARATOR .'c');
defined('INCLUDE_DIR') OR define('INCLUDE_DIR', __DIR__ . DIRECTORY_SEPARATOR .'c');
(include_once(INCLUDE_DIR . DIRECTORY_SEPARATOR . 'SAP/SAP.php')) || die('Unable to include SAP.');


