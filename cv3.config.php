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
// Resources - Helpers, docs, resources, etc
define("PR_RES",  PR_PATH . "/src/cv3/res");
configCreateFolderIfNotExists(PR_RES);

// applications
define("PR_USR",  PR_PATH . "/usr");
configCreateFolderIfNotExists(PR_USR);

// Variable files—files whose content is expected to continually change during normal operation of the system
define("PR_VAR",  PR_PATH . "/var/cv3");
configCreateFolderIfNotExists(PR_VAR);
// Variable files - System databases
define("PR_DB3",  PR_VAR . "/db3");
configCreateFolderIfNotExists(PR_DB3);
// Variable files - Logs
define("PR_LOG",  PR_VAR . "/log");
configCreateFolderIfNotExists(PR_LOG);
// Variable files - Temporary Files
define("PR_TMP",  PR_VAR . "/tmp");
configCreateFolderIfNotExists(PR_TMP);
// cv3 Fontes e Resultados
define("PR_FONTES",  PR_VAR . "/_Fontes");
configCreateFolderIfNotExists(PR_FONTES);
define("PR_RESULTADOS",  PR_VAR . "/_Resultados");
configCreateFolderIfNotExists(PR_RESULTADOS);

//define('PR_ENVIRONMENT', 'production');
define('PR_ENVIRONMENT', 'development');


// set php's error log place
ini_set('error_log', PR_LOG . '/prError.log');


function configCreateFolderIfNotExists($folder) {
  if (!is_dir($folder)) 
	 if (!mkdir($folder)) werro_die('Pasta {$folder} faltando. E também não foi possível criá-la. Está protegida contra gravação ?');
}


?>