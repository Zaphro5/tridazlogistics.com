<?php

require __DIR__ . '/website1/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class PHP_Email_Form
{
    public $to = [];
    public $from_name;
    public $from_email;
    public $subject;
    public $smtp;
    public $ajax;

    private $body;
    private $headers;

    public function add_message($content, $label, $length = 0)
    {
        $this->body .= ($length > 0) ? str_pad($label, $length) : $label;
        $this->body .= ": " . $content . "\n";
    }

    public function send()
    {
        $this->prepare_headers();

        if ($this->ajax) {
            if (!$this->validate_ajax()) {
                return "Error: Invalid AJAX request.";
            }
        } else {
            if (!$this->validate_post()) {
                return "Error: Invalid POST request.";
            }
        }

        if ($this->smtp) {
            return $this->send_smtp();
        } else {
            return $this->send_mail();
        }
    }

    private function validate_ajax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    }

    private function validate_post()
    {
        return isset($_POST['name']) && isset($_POST['email']) && isset($_POST['subject']) && isset($_POST['message']);
    }

    private function prepare_headers()
    {
        $this->headers = "From: {$this->from_name} <{$this->from_email}>" . PHP_EOL;
        $this->headers .= "Reply-To: {$this->from_email}" . PHP_EOL;
        $this->headers .= "MIME-Version: 1.0" . PHP_EOL;
        $this->headers .= "Content-Type: text/plain; charset=utf-8" . PHP_EOL;
        $this->headers .= "X-Mailer: PHP/" . phpversion();
    }

    private function send_mail()
    {
        $to = implode(', ', $this->to);
        $success = mail($to, $this->subject, $this->body, $this->headers);

        if ($success) {
            return "Email sent successfully.";
        } else {
            return "Error: Failed to send email.";
        }
    }

    private function send_smtp()
    {
        if (!isset($this->smtp['host']) || !isset($this->smtp['username']) || !isset($this->smtp['password']) || !isset($this->smtp['port'])) {
            return "Error: SMTP credentials not set.";
        }

        $host = $this->smtp['host'];
        $username = $this->smtp['username'];
        $password = $this->smtp['password'];
        $port = $this->smtp['port'];

        $mailer = new PHPMailer(true);

        try {
            // Server settings
            $mailer->SMTPDebug = SMTP::DEBUG_OFF;  // Enable verbose debug output if needed
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->SMTPAuth = true;
            $mailer->Username = $username;
            $mailer->Password = $password;
            $mailer->Port = $port;

            // Recipients
            $mailer->setFrom($this->from_email, $this->from_name);
            foreach ($this->to as $recipient) {
                $mailer->addAddress($recipient);
            }
            $mailer->addReplyTo($this->from_email);

            // Content
            $mailer->isHTML(false);
            $mailer->Subject = $this->subject;
            $mailer->Body = $this->body;

            $result = $mailer->send();
            if ($result) {
                return "Email sent successfully.";
            } else {
                return "Error: Failed to send email.";
            }
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}
