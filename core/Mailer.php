<?php

namespace Desinova\Aero;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    protected PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // Configuration du serveur SMTP
        // En production, ces valeurs devront aller dans ton fichier .env
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com'; // Exemple avec Mailtrap
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = 'frjuscko@gmail.com'; 
        $this->mail->Password   = 'nidr widm xfkb lizo'; 
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;
        $this->mail->CharSet    = 'UTF-8';

        // Expéditeur par défaut
        $this->mail->setFrom('noreply@aero.com', 'Aero');
    }

    /**
     * Envoyer un email
     */
    public function send(string $to, string $subject, string $htmlContent, string $textContent = ''): bool
    {
        try {
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $htmlContent;
            
            if ($textContent) {
                $this->mail->AltBody = $textContent;
            }

            return $this->mail->send();
        } catch (Exception $e) {
            // En cas d'erreur, on peut logguer le message $this->mail->ErrorInfo
            return false;
        }
    }
}