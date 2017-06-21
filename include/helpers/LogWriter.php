<?php

/**
 * Class LogWriter
 *
 * @author mluex
 */
class LogWriter
{

    const LOG_ERROR = "error";
    const LOG_DEBUG = "debug";
    const LOG_WARNING = "warning";
    const LOG_INFO = "info";
    const LOG_ALL = "all";
    const LOG_NONE = "none";

    private $globalNotifyLevel = self::LOG_ERROR;
    private $globalLogLevel = self::LOG_WARNING;

    function __construct()
    {
        $configLogLevel = Script::config("log/loglevel");
        if ($this->isValidLevel($configLogLevel)) {
            $this->globalLogLevel = $configLogLevel;
        }

        $configNotifyLevel = Script::config("log/notifylevel");
        if ($this->isValidLevel($configNotifyLevel)) {
            $this->globalNotifyLevel = $configNotifyLevel;
        }
    }

    private function isValidLevel($level)
    {
        return ($level === self::LOG_DEBUG || $level === self::LOG_INFO || $level === self::LOG_ERROR || $level === self::LOG_WARNING);
    }

    /**
     * Writes message to designated log file and sends an mail if required
     *
     * @param $level string Log level
     * @param $msg string Error message
     * @param $echoLog bool True if log message should be echoed
     */
    public function log($level, $msg, $echoLog = true)
    {
        if (isset($level) && !empty($level)) {
            $level = strtolower($level);
        } else {
            $level = "unknown";
        }

        $infoData = "[" . date("Y-m-d H-i-s") . "] ";

        if ($echoLog === true) {
            echo $infoData . $msg . "<br />";
        }

        if ($this->shouldBeLogged($level)) {

            if (defined("LOG_PATH")) {
                @file_put_contents(LOG_PATH . DS . strtolower($level) . ".log", $infoData . $msg . "\r\n", FILE_APPEND);
            } else {
                @file_put_contents(ROOT_PATH . DS . "fatal_error.log", "LOG_PATH not defined!!!!\r\n", FILE_APPEND);
            }
        }

        if ($this->shouldBeMailed($level)) {
            if (is_string(Script::config("log/recipients")) && !empty(Script::config("log/recipients"))) {

                $mailFooter = Script::config("log/mail_footer");
                $mailHeader = Script::config("log/mail_header");

                $message = '';
                $message .= '<p><strong>Time:</strong><br />' . date("Y-m-d H-i-s") . '<br />';
                $message .= '<strong>Level:</strong><br />' . $level . '</p>';

                $message .= '<p><strong>Error:</strong><br />' . $msg . '</p>';

                $message = $mailHeader . $message . $mailFooter;

                $recipients = explode(",",Script::config("log/recipients"));
                foreach($recipients as &$recipient) {
                    $recipient = trim($recipient);
                }

                $params = array(
                    "subject" => "[" . Script::config("log/ident") . "] New log message with level " . strtoupper($level),
                    "body" => $message,
                    "recipients" => $recipients
                );

                if(!Script::sendMail($params)) {
                    // Fallback to php mail, if smtp failed

                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type: text/html; charset=" . Script::config("general/charset") . "\r\n";
                    $headers .= "From: " . Script::config("log/ident") . " <" . Script::config("outbox/sender_address") . ">\r\n" .
                        "X-Mailer: PHP/" . phpversion();

                    foreach($recipients as $recipient) {
                        @mail($recipient, $params["subject"], $params["body"], $headers);
                    }
                }

            } else {
                $this->log("error","Log recipients not defined");
            }
        }
    }

    /**
     * Checks if log message should be logged
     *
     * @param $msgLevel String Log level of message
     * @return boolean
     */
    private function shouldBeLogged($msgLevel)
    {
        return $this->shouldBeHandled($this->getLogLevel(), $msgLevel);
    }

    /**
     * Compares log level
     *
     * @param $globalLevel  String Configured log level
     * @param $msgLevel     String Log level of message
     * @return boolean
     */
    private function shouldBeHandled($globalLevel, $msgLevel)
    {
        $msgLevel = strtolower($msgLevel);
        if ($msgLevel === self::LOG_DEBUG) {
            return true;
        }
        switch ($globalLevel) {
            case self::LOG_ALL:
                if (($msgLevel === self::LOG_ERROR) || ($msgLevel == self::LOG_WARNING) || ($msgLevel === self::LOG_INFO)) {
                    return true;
                } else {
                    return false;
                }
                break;
            case self::LOG_WARNING:
                if (($msgLevel === self::LOG_ERROR) || ($msgLevel === self::LOG_WARNING)) {
                    return true;
                } else {
                    return false;
                }
                break;
            case self::LOG_ERROR:
                if ($msgLevel === self::LOG_ERROR) {
                    return true;
                } else {
                    return false;
                }
                break;
            case self::LOG_NONE:
                return false;
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * Returns configured log level or ALL in case of failure
     *
     * @return string Log level
     */
    private function getLogLevel()
    {
        if (!isset($this->globalLogLevel)) {
            return self::LOG_ALL;
        } else {
            return strtolower($this->globalLogLevel);
        }
    }

    /**
     * Checks if log message should be mailed
     *
     * @param $msgLevel String Log level of message
     * @return boolean
     */
    private function shouldBeMailed($msgLevel)
    {
        return $this->shouldBeHandled($this->getNotifyLevel(), $msgLevel);
    }

    /**
     * Returns configured notify level or ALL in case of failure
     *
     * @return string Log level
     */
    private function getNotifyLevel()
    {
        if (!isset($this->globalNotifyLevel)) {
            return self::LOG_ALL;
        } else {
            return strtolower($this->globalNotifyLevel);
        }
    }

}