<?php
/**
 * Travis CI test script
 * @package YetiForce.Travis CI
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
// more than 8MB memory needed for graphics
// memory limit default value = 64M
AppConfig::iniSet('memory_limit', '512M');
// lifetime of session
AppConfig::iniSet('session.gc_maxlifetime', '21600');

// show or hide calendar, world clock, calculator, chat and CKEditor 
// Do NOT remove the quotes if you set these to false! 
$CALENDAR_DISPLAY = 'true';
$WORLD_CLOCK_DISPLAY = 'true';
$CALCULATOR_DISPLAY = 'true';
$CHAT_DISPLAY = 'true';
$USE_RTE = 'true';

// url for customer portal (Example: https://portal.yetiforce.com/)
$PORTAL_URL = 'https://portal';

// helpdesk support email id and support name (Example: 'support@vtiger.com' and 'vtiger support')
$HELPDESK_SUPPORT_NAME = 'your-support name';
$HELPDESK_SUPPORT_EMAIL_REPLY = '';

/* database configuration
  db_server
  db_port
  db_hostname
  db_username
  db_password
  db_name
 */

$dbconfig['db_server'] = 'localhost';
$dbconfig['db_port'] = '3306';
$dbconfig['db_username'] = 'root';
$dbconfig['db_password'] = '';
$dbconfig['db_name'] = 'yetiforce';
$dbconfig['db_type'] = 'mysql';
$dbconfig['db_status'] = 'true';

$dbconfig['db_hostname'] = $dbconfig['db_server'] . ':' . $dbconfig['db_port'];

$host_name = $dbconfig['db_hostname'];

$site_URL = 'http://localhost/';

// cache direcory path
$cache_dir = 'cache/';

// tmp_dir default value prepended by cache_dir = images/
$tmp_dir = 'cache/images/';

// import_dir default value prepended by cache_dir = import/
$import_dir = 'cache/import/';

// upload_dir default 
$upload_dir = 'cache/upload/';

// disable send files using KCFinder
$upload_disabled = false;

// maximum file size for uploaded files in bytes also used when uploading import files
// upload_maxsize default value = 3000000
$upload_maxsize = 52428800;  // 50MB
// flag to allow export functionality
// 'all' to allow anyone to use exports 
// 'admin' to only allow admins to export 
// 'none' to block exports completely 
// allow_exports default value = all
$allow_exports = 'all';

// files with one of these extensions will have '.txt' appended to their filename on upload
// upload_badext default value = php, php3, php4, php5, pl, cgi, py, asp, cfm, js, vbs, html, htm
$upload_badext = array('php', 'php3', 'php4', 'php5', 'pl', 'cgi', 'py', 'asp', 'cfm', 'js', 'vbs', 'html', 'htm', 'exe', 'bin', 'bat', 'sh', 'dll', 'phps', 'phtml', 'xhtml', 'rb', 'msi', 'jsp', 'shtml', 'sth', 'shtm');

// list_max_entries_per_page default value = 20
$list_max_entries_per_page = '20';

// limitpage_navigation default value = 5
$limitpage_navigation = '5';

// history_max_viewed default value = 5
$history_max_viewed = '5';

// default_module default value = Home
$default_module = 'Home';

// default_action default value = index
$default_action = 'index';

// set default theme
// default_theme default value = blue
$default_theme = 'softed';

// default text that is placed initially in the login form for user name
// no default_user_name default value
$default_user_name = '';

//Master currency name
$currency_name = 'Poland, Zlotych';

// default charset
// default charset default value = 'UTF-8' or 'ISO-8859-1'
$default_charset = 'UTF-8';

// default language
// default_language default value = en_us
$default_language = 'pl_pl';

// add the language pack name to every translation string in the display.
// translation_string_prefix default value = false
$translation_string_prefix = false;

//Option to cache tabs permissions for speed.
$cache_tab_perms = true;

//Option to hide empty home blocks if no entries.
$display_empty_home_blocks = false;

//Disable Stat Tracking of vtiger CRM instance
$disable_stats_tracking = false;

// Generating Unique Application Key
$application_unique_key = 'ed7d9c52b7981b35644f61e7cb7dd61a';

// trim descriptions, titles in listviews to this value
$listview_max_textlength = 40;

// Maximum time limit for PHP script execution (in seconds)
$php_max_execution_time = 0;

// Set the default timezone as per your preference
$default_timezone = 'Europe/Warsaw';

/** If timezone is configured, try to set it */
if (isset($default_timezone) && function_exists('date_default_timezone_set')) {
	@date_default_timezone_set($default_timezone);
}

// Maximum length of characters for title
$title_max_length = 60;

// Maximum length for href tag
$href_max_length = 35;

//Should menu breadcrumbs be visible? true = show, false = hide
$breadcrumbs = true;

//Separator for menu breadcrumbs default value = '>'
$breadcrumbs_separator = '>';

//Pop-up window type with record list  1 - Normal , 2 - Expanded search
$popupType = 1;

//Minimum cron frequency [min]
$MINIMUM_CRON_FREQUENCY = 1;

//Update the current session id with a newly generated one after login
$session_regenerate_id = false;

$davStorageDir = 'storage/Files';
$davHistoryDir = 'storage/FilesHistory';

// prod and demo
$systemMode = 'prod';

// Force site access to always occur under SSL (https) for selected areas. You will not be able to access selected areas under non-ssl. Note, you must have SSL enabled on your server to utilise this option.
$forceSSL = false;

// Maximum number of records in a mass edition
$listMaxEntriesMassEdit = 500;

// enable closing of mondal window by clicking on the background
$backgroundClosingModal = true;

// enable CSRF-protection
$csrfProtection = true;

// Is sending emails active. 
$isActiveSendingMails = true;

// Should the task in cron be unblocked if the script execution time was exceeded
$unblockedTimeoutCronTasks = true;

// The maximum time of executing a cron. Recommended same as the max_exacution_time parameter value.
$maxExecutionCronTime = 3600;

// System's language selection in the login window (true/false).
$langInLoginView = false;

// System's lyout selection in the login window (true/false).
$layoutInLoginView = false;

// Set the default layout 
$defaultLayout = 'basic';

// Logo is visible in footer.
$isVisibleLogoInFooter = true;
