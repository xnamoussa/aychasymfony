<?php

namespace App\Service;

use App\Entity\Sponsor;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SponsorMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router
    ) {}

    public function sendVerificationEmail(Sponsor $sponsor): void
    {
        $verificationUrl = $this->router->generate(
            'sponsor_verify_email',
            ['token' => $sponsor->getVerificationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('lamma.service@gmail.com')
            ->to($sponsor->getEmail())
            ->subject('✅ Vérifiez votre adresse email - LAMMA Events')
            ->html("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: #1a1a2e; padding: 30px; text-align: center;'>
                        <h1 style='color: #e8c74a; margin: 0;'>LAMMA Events</h1>
                    </div>
                    <div style='background: #f9f9f9; padding: 40px;'>
                        <!-- Nouveau message de bienvenue avec infos du sponsor -->
                        <div style='background: #e8f4fd; border-left: 4px solid #e8c74a; padding: 20px; margin-bottom: 30px;'>
                            <h3 style='color: #2c3e50; margin-top: 0; margin-bottom: 10px;'>
                                🎉 Bienvenue {$sponsor->getNom()} !
                            </h3>
                            <p style='margin: 0; color: #34495e; font-size: 14px;'>
                                <strong>Email enregistré :</strong> {$sponsor->getEmail()}<br>
                                <strong>Instructions :</strong> Pour finaliser votre inscription en tant que sponsor, 
                                veuillez confirmer votre adresse email en cliquant sur le lien de vérification ci-dessous.
                            </p>
                        </div>

                        <h2 style='color: #333;'>Bonjour {$sponsor->getNom()},</h2>
                        <p style='color: #666; font-size: 16px;'>
                            Votre compte sponsor a été créé sur LAMMA Events. 
                            Pour activer votre compte, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous.
                        </p>
                        <div style='text-align: center; margin: 40px 0;'>
                            <a href='{$verificationUrl}' 
                               style='background: #e8c74a; color: #000; padding: 15px 40px; 
                                      text-decoration: none; border-radius: 5px; 
                                      font-weight: bold; font-size: 16px;'>
                                ✅ Vérifier mon adresse email
                            </a>
                        </div>
                        <p style='color: #999; font-size: 13px;'>
                            Si vous n'avez pas demandé ce compte, ignorez cet email.<br>
                            Ce lien expire dans 24 heures.
                        </p>
                    </div>
                </div>
            ");

        $this->mailer->send($email);
    }
}