<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private LoggerInterface $logger,
        private MailerInterface $mailer
    ) {}

    /**
     * Sends a verification email to the user
     *
     * @param string $to Recipient email address
     * @param string $token Verification token
     * @param string $userName User's full name
     * @return bool Success status
     */
    public function sendVerificationEmail(string $to, string $token, string $userName): bool
    {
        try {
            // Generate verification URL
            $verificationUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
            $verificationUrl .= "/verify-email?token={$token}";

            // Email content
            $subject = 'Vérifiez votre adresse email - Mini Uber';
            $body = $this->getVerificationEmailTemplate($userName, $verificationUrl);

            // Get sender email from environment or use default
            $fromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@mini-uber.com';
            $fromName = $_ENV['MAILER_FROM_NAME'] ?? 'Mini Uber';

            // Create and send email
            $email = (new Email())
                ->from($fromEmail)
                ->to($to)
                ->subject($subject)
                ->html($body);

            $this->mailer->send($email);

            // Log for debugging
            $this->logger->info('Email de vérification envoyé', [
                'to' => $to,
                'subject' => $subject,
                'verification_url' => $verificationUrl,
                'token' => $token
            ]);

            return true;
        } catch (\Exception $e) {
            // Log error
            $this->logger->error('Erreur lors de l\'envoi de l\'email de vérification', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Generates the HTML template for verification email
     */
    private function getVerificationEmailTemplate(string $userName, string $verificationUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 30px; }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mini Uber</h1>
        </div>
        <div class="content">
            <h2>Bonjour {$userName},</h2>
            <p>Merci de vous être inscrit sur Mini Uber ! Pour activer votre compte, veuillez vérifier votre adresse email en cliquant sur le bouton ci-dessous :</p>
            <p style="text-align: center;">
                <a href="{$verificationUrl}" class="button">Vérifier mon email</a>
            </p>
            <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
            <p style="word-break: break-all; background-color: #eee; padding: 10px;">{$verificationUrl}</p>
            <p><strong>Ce lien expirera dans 24 heures.</strong></p>
            <p>Si vous n'avez pas créé de compte, vous pouvez ignorer cet email.</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 Mini Uber. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
