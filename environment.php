<?php
// SETTINGS FOR NASF DATABASE CONNECTION //
$GLOBALS['db_ms_nasf']['server'] = 'sqlserver4.listech.on.ca';
$GLOBALS['db_ms_nasf']['database'] = 'LTI_PROD';
$GLOBALS['db_ms_nasf']['username'] = 'SFS';
$GLOBALS['db_ms_nasf']['password'] = 'SFS';
$GLOBALS['db_ms_nasf']['log_sql'] = false;

// SETTINGS FOR GLOVIA G2 DATABASE CONNECTION //
$GLOBALS['db_ms_erp']['server'] = 'GloviaG2\LTIGLOVIA';
$GLOBALS['db_ms_erp']['database'] = 'G2PROD';
$GLOBALS['db_ms_erp']['username'] = 'g2_data_reader';
$GLOBALS['db_ms_erp']['password'] = '8Cx4YS8NO9IKnSMAqi6n';
$GLOBALS['db_ms_erp']['log_sql'] = false;

// SETTINGS FOR NASF DATABASE CONNECTION //
$GLOBALS['db_ms_sftr']['server'] = 'LTI-DEV-SQL01.listech.on.ca';
$GLOBALS['db_ms_sftr']['database'] = 'SFTR_LOG';
$GLOBALS['db_ms_sftr']['username'] = 'SFS';
$GLOBALS['db_ms_sftr']['password'] = 'SFS';
$GLOBALS['db_ms_sftr']['log_sql'] = false;


// INCLUDES FOR ALL NECESSARY CLASS FILES //
include_once ('./CLASSES/php/msSqlConnection.php');
include_once ('./CLASSES/php/commonFunctions.php');
?>