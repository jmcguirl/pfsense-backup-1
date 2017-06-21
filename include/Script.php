<?php

/**
 * Main class
 *
 * @author mluex
 */

class Script
{

    const BACKUP_MODE_MAIL = "mail";
    const BACKUP_MODE_STORE = "store";

    protected static $registryData = array();
    protected static $logWriter = null;
    protected static $config = null;
    protected static $mailer = null;
    protected static $curlHelper = null;

    public static function set($key, $value) {
        if (!is_array(self::$registryData)) {
            self::$registryData = array();
        }
        self::$registryData[$key] = $value;
    }

    public static function get($key) {
        if (is_array(self::$registryData) && array_key_exists($key, self::$registryData)) {
            return self::$registryData[$key];
        }

        return null;
    }

    public static function delete($key) {
        if (is_array(self::$registryData) && array_key_exists($key, self::$registryData)) {
            unset(self::$registryData[$key]);
        }
    }

    public static function config($route, $defaultValue = null) {
        if (self::$config === null) {
            self::$config = new ConfigHelper();
        }

        if(!self::$config->configLoaded()) {
            self::quit("Config not loaded","error");
        }

        return self::$config->getValue($route, $defaultValue);
    }

    public static function log($level, $msg, $echoLog = true) {
        if (self::$logWriter === null) {
            self::$logWriter = new LogWriter();
        }

        self::$logWriter->log($level, $msg, $echoLog);
    }

    public static function sendMail($params) {
        if (self::$mailer === null) {
            self::$mailer = new Mailer();
        }

        return self::$mailer->send($params);
    }

    public static function getCurlHelper() {
        if (self::$curlHelper === null) {
            self::$curlHelper = new CurlHelper();
        }

        return self::$curlHelper;
    }

    public static function quit($msg, $level = "error") {
        self::log($level,$msg, true);
        die("");
    }

    public static function dispatchHeartbeat() {
        $heartbeatUrl = self::config("general/heartbeat_url");
        if(!is_string($heartbeatUrl) || empty($heartbeatUrl)) {
            self::log("info","Could not dispatch heartbeat - url not defined");
            return;
        }

        @file_get_contents($heartbeatUrl);
        self::log("info","Dispatched heartbeat pulse");
    }

    public static function prepare() {
        if(!function_exists('curl_version')) {
            self::quit("CURL is not available on your webserver");
        }

        $pfsenseAddress = self::config("backup/pfsense_address");
        if(!is_string($pfsenseAddress) || empty($pfsenseAddress)) {
            self::quit("Please specify the pfSense address");
        }

        $username = self::config("backup/credentials/username");
        if(!is_string($username) || empty($username)) {
            self::quit("Please specify the pfSense username");
        }

        $password = self::config("backup/credentials/password");
        if(!is_string($password) || empty($password)) {
            self::quit("Please specify the pfSense password");
        }

        $password = base64_decode($password);
        if($password === false || empty($password)) {
            self::quit("The pfSense password is not stored as BASE64");
        }

        $backupMode = self::config("backup/mode");
        if(!is_string($backupMode) || (strtolower($backupMode) !== self::BACKUP_MODE_MAIL && strtolower($backupMode) !== self::BACKUP_MODE_STORE)) {
            self::quit("Invalid backup mode");
        }

        // Create cookie directory
        if(!file_exists(COOKIE_PATH)) {
            @mkdir(COOKIE_PATH);
        }

        if(!file_exists(COOKIE_PATH)) {
            self::quit("Cookie directory does not exist and could not be created");
        }

        // Check if cookie directory is writable
        if(!is_writable(COOKIE_PATH)) {
            self::quit("Cookie directory is not writable");
        }

        switch ($backupMode):
            case self::BACKUP_MODE_MAIL:
                // Create tmp directory
                if(!file_exists(TMP_PATH)) {
                    @mkdir(TMP_PATH);
                }

                if(!file_exists(TMP_PATH)) {
                    self::quit("tmp directory does not exist and could not be created");
                }

                // Check if tmp directory is writable
                if(!is_writable(TMP_PATH)) {
                    self::quit("tmp directory is not writable");
                }

                $mailRecipients = self::config("backup/mode_configs/mail/recipient_addresses");
                if(!is_string($mailRecipients) || empty($mailRecipients)) {
                    self::quit("No mail recipients defined");
                }

                $validRecipients = 0;
                $mailRecipients = explode(",",$mailRecipients);
                foreach($mailRecipients as $mailRecipient) {
                    $mailRecipient = trim($mailRecipient);
                    if(filter_var($mailRecipient,FILTER_VALIDATE_EMAIL) === false) {
                        self::log("warning", $mailRecipient . " is not valid mail address");
                        continue;
                    }
                    $validRecipients++;
                }

                if($validRecipients === 0) {
                    self::quit("No valid mail recipient defined");
                }
            break;
            case self::BACKUP_MODE_STORE:
                $targetDirectory = self::config("backup/mode_configs/store/target_directory");
                if(!is_string($targetDirectory) || empty($targetDirectory)) {
                    self::quit("Target directory for backups not defined");
                }

                $targetDirectory = realpath($targetDirectory);
                if(!file_exists($targetDirectory)) {
                    self::quit("Target directory does not exist");
                }

                if(!is_writable($targetDirectory)) {
                    self::quit("Target directory is not writable");
                }
            break;
        endswitch;
    }
}