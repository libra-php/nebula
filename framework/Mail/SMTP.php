<?php

namespace Nebula\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SMTP
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer();
    }

    public function init(): void
    {
        $config = config("email");
        //Server settings
        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth = true;
        if ($config["debug"]) {
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        $this->mailer->Host = $config["host"];
        $this->mailer->Port = $config["port"];
        $this->mailer->Username = $config["username"];
        $this->mailer->Password = $config["password"];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }

    public function send(
        string $subject,
        string $body,
        ?string $plain_text = null,
        array $to_addresses = [],
        array $cc_addresses = [],
        array $bcc_addresses = [],
        array $attachments = []
    ): bool {
        try {
            $config = config("email");

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->setFrom($config["from"], $config["from_name"]);
            $this->mailer->addReplyTo(
                $config["reply_to"],
                $config["reply_to_name"]
            );

            foreach ($to_addresses as $address) {
                $this->mailer->addAddress($address);
            }
            foreach ($cc_addresses as $address) {
                $this->mailer->addCC($address);
            }
            foreach ($bcc_addresses as $address) {
                $this->mailer->addBCC($address);
            }

            foreach ($attachments as $attachment) {
                $this->mailer->addAttachment($attachment);
            }

            $this->mailer->Body = $body;

            if (!is_null($plain_text)) {
                $this->mailer->AltBody = $plain_text;
            }

            return $this->mailer->send();
        } catch (Exception) {
            error_log(
                "Message could not be sent. Error: {$this->mailer->ErrorInfo}"
            );
        }
    }
}

