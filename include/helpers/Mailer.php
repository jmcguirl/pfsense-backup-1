<?php

/**
 * Class Mailer
 *
 * @author mluex
 */
class Mailer
{

    /**
     * Validates option array
     *
     * @param $params array List of options
     * @return bool|array False if invalid, array or params with corrections if valid
     */
    private function validateParams($params) {
        if(!is_array($params)) {
            Script::log("error","Invalid mail options - not an array");
            return false;
        }

        // Add subarrays if not given
        if(!array_key_exists("body",$params)) {
            $params["body"] = "";
        }

        if(!array_key_exists("vars", $params) || !is_array($params["vars"])) {
            $params["vars"] = array();
        }

        if(!array_key_exists("attachments", $params) || !is_array($params["attachments"])) {
            $params["attachments"] = array();
        }

        if(!array_key_exists("recipients", $params) || !is_array($params["recipients"])) {
            $params["recipients"] = array();
        }

        if(!array_key_exists("is_html", $params) || !is_numeric($params["is_html"])) {
            $params["is_html"] = 1;
        }
        $params["is_html"] = (int) $params["is_html"];

        // Validate parameters
        foreach($params["vars"] as $placeholder => &$replacement) {
            if(!is_string($replacement)) {
                Script::log("warning","Invalid template var " . $placeholder . " in mail");
                unset($params["vars"][$placeholder]);
            }
        }

        foreach($params["attachments"] as $attachment => &$path) {
            if(!is_string($path)) {
                Script::log("warning","Invalid attachment " . $attachment . " in mail");
                unset($params["attachments"][$attachment]);
            }
        }

        foreach($params["recipients"] as $index => &$recipient) {
            if(!is_string($recipient) || filter_var($recipient,FILTER_VALIDATE_EMAIL) === false) {
                Script::log("warning","Invalid recipient address " . $recipient . " in mail");
                unset($params["recipients"][$index]);
            }
        }

        // Check requirements
        if(!array_key_exists("subject",$params)) {
            Script::log("error","Invalid mail options - empty subject");
            return false;
        }

        if(count($params["recipients"]) === 0) {
            Script::log("error","Invalid mail options - no recipient");
            return false;
        }

        return $params;
    }

    /**
     * Sends mail via SMTP
     *
     * @param $params array List of options
     * @return bool False in case of failure
     */
    public function send($params) {
        if(!$params = $this->validateParams($params)) {
            Script::log("error", "SMTP options are invalid");
            return false;
        }

        $mail = new PHPMailer();

        $mail->IsSMTP();
        $mail->Host       = Script::config("outbox/smtp/host");
        $mail->Port       = Script::config("outbox/smtp/port");
        $mail->SMTPSecure = Script::config("outbox/smtp/encryption");

        if(is_numeric(Script::config("outbox/smtp/auth")) && (int) Script::config("outbox/smtp/auth") === 1) {
            $mail->SMTPAuth   = true;
            $mail->Username   = Script::config("outbox/smtp/user");
            $mail->Password   = Script::config("outbox/smtp/password");
        }

        if(empty($params["body"])) {
            $mail->AllowEmpty = true;
            $mail->Body = "";
        } else {
            $mail->Body = replacePlaceholders($params["body"], $params["vars"]);
        }

        $mail->Subject = replacePlaceholders($params["subject"], $params["vars"]);

        if($params["is_html"] === 1) {
            $mail->IsHTML(true);
        } else {
            $mail->IsHTML(false);
        }

        $mail->SetFrom(Script::config("outbox/sender_address"), Script::config("outbox/sender_name"));

        foreach($params["recipients"] as $recipient) {
            $mail->addAddress($recipient);
        }

        foreach($params["attachments"] as $attachment => $attachmentPath) {
            $mail->AddAttachment(
                $attachmentPath,
                $attachment,
                'base64',
                mime_content_type($attachmentPath)
            );
        }

        if(!$mail->Send()) {
            Script::log("error","Could not send mail: " . $mail->ErrorInfo);
            return false;
        } else {
            Script::log("info","Successfully sent mail!");
            return true;
        }
    }

}