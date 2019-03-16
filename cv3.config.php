<?php

/*
 * This file is part of the Pnw Project
 *
 * (c) Paulo Marote <paulomarote@hotmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
  Where is the base dir? If necessary, you should fix only the lines below
  If your script is located in /var/pn/src/pnw-phpgtk/index.php then the following would return:
  dirname(__FILE__); // /var/pn/src/pnw-phpgtk
  dirname( dirname(__FILE__) ); // /var/pn/src
  dirname( dirname( dirname(__FILE__) ) ); // /var/pn
*/

define('PR_PATH', dirname( dirname( dirname(__FILE__) ) ) );

// Main Consts
// Configuration Files
define("PR_ETC",  PR_PATH . "/etc");

// TODO - Tranfer above to /etc?


// Original Files (not processed pdfs)
define("PR_ORIG", PR_PATH . "/orig");
// Resources - Processed pdfs and databases
define("PR_RES",  PR_PATH . "/res");
// Variable files—files whose content is expected to continually change during normal operation of the system
define("PR_VAR",  PR_PATH . "/var");
// Variable files - System databases
define("PR_DB3",  PR_PATH . "/var/db3");
// Variable files - Logs
define("PR_LOG",  PR_PATH . "/var/log");
// Variable files - Temporary Files
define("PR_TMP",  PR_PATH . "/var/tmp");
// applications
define("PR_USR",  PR_PATH . "/usr");

//define('PR_ENVIRONMENT', 'production');
define('PR_ENVIRONMENT', 'development');


// set php's error log place
ini_set('error_log', PR_LOG . '/prError.log');


?>