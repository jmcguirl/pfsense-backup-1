<?php

/**
 * pfSense backup script
 *
 * @copyright   Copyright (c) 2017 bannerstop GmbH (https://www.bannerstop.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License 3 (GPL 3.0)
 * @author      mluex
 */

define("DEBUG", true);

define("ROOT_PATH", dirname(__FILE__));
define("DS", DIRECTORY_SEPARATOR);
define("INC_PATH", ROOT_PATH . DS . "include");

require_once(INC_PATH . DS . "loader.php");

Script::prepare();

$curlHelper = Script::getCurlHelper();

$backupUrl = Script::config("backup/pfsense_protocol") . "://" . Script::config("backup/pfsense_address") . "/diag_backup.php";
$curlHelper->setCookieFile("pfsense-" . Script::config("backup/pfsense_address") . "txt");

// Request pfSense Backup & Restore page
$html = $curlHelper->get($backupUrl);
if($curlHelper->requestFailed()) {
    Script::quit("Could not communicate with pfSense");
}

$dom = DomParser::str_get_html($html);

if(stripos($dom,'/css/pfSense') === false) {
    Script::quit("This does not look like a pfSense");
}

// Check if login is required
$isLoginpage = count($dom->find("body#login")) > 0;
if($isLoginpage) {
    Script::log("info","Login required ...");

    // Perform login request
    $csrfField = $dom->find('input[name=__csrf_magic]');
    if(count($csrfField) !== 1) {
        Script::quit("Could not retrieve CSRF key");
    }

    $postData = array(
        "usernamefld" => Script::config("backup/credentials/username"),
        "passwordfld" => base64_decode(Script::config("backup/credentials/password")),
        "__csrf_magic" => $csrfField[0]->value,
        "login" => "",
    );

    $loginRequest = $curlHelper->post($backupUrl,$postData);
    if($curlHelper->requestFailed()) {
        Script::quit("Login request failed");
    }

    $dom = DomParser::str_get_html($loginRequest);
    $isLoginpageAgain = count($dom->find("body#login")) > 0;

    if($isLoginpageAgain) {
        Script::log("error","pfSense login failed");
    } else {
        Script::log("info","Login successful!");
    }
}

$isBackupPage = count($dom->find('form[action=/diag_backup.php]')) > 0;
if(!$isBackupPage) {
    Script::log("info","No on target page, requesting it again ...");

    $html = $curlHelper->get($backupUrl);
    if($curlHelper->requestFailed()) {
        Script::quit("Could not communicate with pfSense");
    }

    $dom = DomParser::str_get_html($html);
}

// Compose post request for config export
$csrfField = $dom->find('input[name=__csrf_magic]');
if(count($csrfField) !== 1) {
    Script::quit("Could not retrieve CSRF key");
}

// - Prepare backuparea selection
$backupArea = Script::config("backup/backuparea");
if(!is_string($backupArea)) {
    $backupArea = "";
}
$backupArea = strtolower($backupArea);
if($backupArea === "all") {
    $backupArea = "";
}

// - Prepare nopackages checkout
$noPackages = Script::config("backup/nopackages");
if(!is_string($noPackages)) {
    $noPackages = "";
}
$noPackages = strtolower($noPackages);
if($noPackages !== "yes") {
    $noPackages = "";
}

// - Prepare donotbackuprrd checkout
$doNotBackupRRD = Script::config("backup/donotbackuprrd");
if(!is_string($doNotBackupRRD)) {
    $doNotBackupRRD = "";
}
$doNotBackupRRD = strtolower($doNotBackupRRD);
if($doNotBackupRRD !== "yes") {
    $doNotBackupRRD = "";
}

// - Prepare encrypt checkout
$encrypt = Script::config("backup/encrypt");
if(!is_string($encrypt)) {
    $encrypt = "";
}
$encrypt = strtolower($encrypt);
if($encrypt !== "yes") {
    $encrypt = "";
}

// - Prepare encrypt_password field
if($encrypt === "yes") {
    $encryptPassword = Script::config("backup/encrypt_password");
    if(!is_string($encryptPassword)) {
        $encryptPassword = "";
    }
} else {
    $encryptPassword = "";
}

$postData = array(
    "__csrf_magic" => $csrfField[0]->value,
    "backuparea" => $backupArea,
    "donotbackuprrd" => $doNotBackupRRD,
    "nopackages" => $noPackages,
    "encrypt" => $encrypt,
    "encrypt_password" => $encryptPassword,
    "download" => "Download configuration as XML",
);

$export = $curlHelper->post($backupUrl,$postData);
if($curlHelper->requestFailed()) {
    Script::quit("Backup export failed");
}

$backupMode = Script::config("backup/mode");
if(!is_string($backupMode) || empty($backupMode)) {
    $backupMode = "store";
}

$backupMode = strtolower($backupMode);

if($backupMode === Script::BACKUP_MODE_STORE) {

    $targetDirectory = Script::config("backup/mode_configs/store/target_directory");
    $overrideFile = Script::config("backup/mode_configs/store/override_file");
    if(!is_numeric($overrideFile) || (int) $overrideFile > 1 || (int) $overrideFile < 0) {
        $overrideFile = 0;
    } else {
        $overrideFile = (int) $overrideFile;
    }

    if($overrideFile === 1) {
        $filename = "pfsense-" . Script::config("backup/pfsense_address") . ".xml";
    } else {
        $i = 2;
        $sourceFilename = date("YmdHi") . "-pfsense-" . Script::config("backup/pfsense_address");
        $extension = "xml";
        $filename = $sourceFilename . " (" . $i . ")";
        while(file_exists($targetDirectory . DS . $filename . "." . $extension)) {
            $i++;
            $filename = $sourceFilename . " (" . $i . ")";
        }
        $filename .= "." . $extension;
    }

    $putResult = @file_put_contents($targetDirectory . DS . $filename, $export);
    if($putResult === false) {
        Script::quit("Could not save export under " . $targetDirectory . DS . $filename);
    }

    Script::dispatchHeartbeat();
    Script::quit("Config backup saved under " . $targetDirectory . DS . $filename . " !", "info");

} elseif($backupMode === Script::BACKUP_MODE_MAIL) {

    $filename = date("YmdHi") . "-pfsense-" . Script::config("backup/pfsense_address") . ".xml";
    $putResult = @file_put_contents(TMP_PATH . DS . $filename, $export);
    if($putResult === false) {
        Script::quit("Could not save temporary export under " . TMP_PATH . DS . $filename);
    }

    $params = array(
        "subject" => "[" . Script::config("log/ident") . "] pfSense config export " . date("YmdHi"),
        "body" => "Backup attached.",
        "recipients" => array(),
        "attachments" => array(),
        "is_html" => 0
    );

    $mailRecipients = explode(",",Script::config("backup/mode_configs/mail/recipient_addresses"));
    foreach($mailRecipients as $mailRecipient) {
        $mailRecipient = trim($mailRecipient);
        if(filter_var($mailRecipient,FILTER_VALIDATE_EMAIL) !== false) {
            $params["recipients"][] = $mailRecipient;
        }
    }

    $params["attachments"][$filename] = TMP_PATH . DS . $filename;

    $mailResult = Script::sendMail($params);
    if($mailResult === false) {
        @unlink(TMP_PATH . DS . $filename);
        Script::quit("Could not send email");
    }

    @unlink(TMP_PATH . DS . $filename);

    Script::dispatchHeartbeat();
    Script::quit("Config backup sent via mail !","info");
}