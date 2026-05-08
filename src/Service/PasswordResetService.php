<?php
// src/Service/PasswordResetService.php

namespace App\Service;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordResetService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface        $mailer,
        private readonly string                 $mailerFrom,
    ) {}

    // ── Generate a fresh 4-digit code, save it, and email it ─────────────────
    public function sendCode(Users $user): void
    {
        $code      = (string) random_int(1000, 9999);
        $expiresAt = new \DateTimeImmutable('+10 minutes');

        $user->setResetCode($code);
        $user->setResetCodeExpiresAt($expiresAt);
        $this->em->flush();

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($user->getEmail())
            ->subject('LAMMA — Your password reset code')
            ->html($this->buildEmailHtml($user->getName(), $code));

        $this->mailer->send($email);
    }

    // ── Verify that the supplied code matches and has not expired ─────────────
    public function verifyCode(Users $user, string $code): bool
    {
        if ($user->getResetCode() === null || $user->getResetCodeExpiresAt() === null) {
            return false;
        }

        $notExpired = $user->getResetCodeExpiresAt() > new \DateTimeImmutable();
        $matches    = hash_equals($user->getResetCode(), trim($code));

        return $notExpired && $matches;
    }

    // ── Clear the stored code after a successful reset ────────────────────────
    public function clearCode(Users $user): void
    {
        $user->setResetCode(null);
        $user->setResetCodeExpiresAt(null);
        $this->em->flush();
    }

    // ── Branded HTML email body ───────────────────────────────────────────────
    private function buildEmailHtml(string $name, string $code): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>LAMMA — Password Reset</title>
</head>
<body style="margin:0;padding:0;background:#080809;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#080809;min-height:100vh;">
    <tr>
      <td align="center" style="padding:48px 16px;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:480px;background:#141416;border-radius:16px;border:1px solid rgba(255,255,255,0.07);overflow:hidden;">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,rgba(232,57,44,0.4) 0%,rgba(46,207,184,0.15) 100%);padding:36px 40px;text-align:center;">
              <div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:2rem;font-weight:900;letter-spacing:0.12em;color:#ffffff;">
                LAMMA<span style="color:#e8392c;">.</span>
              </div>
              <p style="margin:8px 0 0;font-size:0.75rem;letter-spacing:0.2em;text-transform:uppercase;color:rgba(255,255,255,0.5);">Password Reset</p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:40px 40px 32px;">
              <p style="margin:0 0 8px;font-size:0.8rem;letter-spacing:0.12em;text-transform:uppercase;color:rgba(255,255,255,0.4);">Hello, {$name}</p>
              <h1 style="margin:0 0 20px;font-size:1.5rem;color:#ffffff;font-weight:700;">Your verification code</h1>
              <p style="margin:0 0 32px;font-size:0.9rem;color:rgba(255,255,255,0.6);line-height:1.7;">
                We received a request to reset the password for your LAMMA account.
                Enter the code below in the app. It expires in <strong style="color:#ffffff;">10 minutes</strong>.
              </p>

              <!-- Code box -->
              <div style="background:#0f0f11;border:1px solid rgba(46,207,184,0.3);border-radius:12px;padding:28px;text-align:center;margin-bottom:32px;">
                <span style="font-size:3rem;font-weight:900;letter-spacing:0.35em;color:#2ecfb8;font-family:'Courier New',monospace;">{$code}</span>
              </div>

              <p style="margin:0;font-size:0.8rem;color:rgba(255,255,255,0.35);line-height:1.7;">
                If you didn't request a password reset, you can safely ignore this email.
                Your password will remain unchanged.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 40px 32px;border-top:1px solid rgba(255,255,255,0.06);">
              <p style="margin:0;font-size:0.72rem;color:rgba(255,255,255,0.25);text-align:center;">
                © LAMMA · Sent automatically, please do not reply
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}
