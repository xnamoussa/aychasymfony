<?php

namespace App\Controller;

use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;

class ForgotPasswordController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    #[Route('/forgot-password/request', name: 'app_forgot_password_request', methods: ['POST'])]
    public function request(Request $request, UsersRepository $usersRepository, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        if (!$email) {
            return new JsonResponse(['error' => 'Email is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $usersRepository->findOneBy(['email.value' => $email]);

        if (!$user) {
            // For security reasons, don't reveal if user exists. 
            // But for a verification code flow, we might need to be honest or just send a dummy email?
            // Usually, if we send a code, we only send it if user exists.
            return new JsonResponse(['error' => 'No account found with this email.'], Response::HTTP_NOT_FOUND);
        }

        $code = (string)random_int(1000, 9999);
        
        $session = $this->requestStack->getSession();
        $session->set('reset_password_code', $code);
        $session->set('reset_password_email', $email);
        $session->set('reset_password_verified', false);

        try {
            $emailMessage = (new Email())
                ->from('no-reply@yourdomain.com') // This will be overridden by Gmail/Google Mailer if configured
                ->to($email)
                ->subject('Your Password Reset Code')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px;'>
                        <h2 style='color: #2ecfb8;'>Password Reset</h2>
                        <p>Hello,</p>
                        <p>We received a request to reset your password. Use the following 4-digit code to proceed:</p>
                        <div style='background-color: #f4f4f4; padding: 15px; text-align: center; border-radius: 5px; margin: 20px 0;'>
                            <span style='font-size: 32px; font-weight: bold; letter-spacing: 10px; color: #333;'>$code</span>
                        </div>
                        <p>If you didn't request this, you can safely ignore this email.</p>
                        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #888;'>This is an automated message. Please do not reply.</p>
                    </div>
                ");

            $mailer->send($emailMessage);

            return new JsonResponse(['message' => 'Code sent successfully.']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to send email. ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/forgot-password/verify', name: 'app_forgot_password_verify', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';
        
        $session = $this->requestStack->getSession();
        $storedCode = $session->get('reset_password_code');

        if ($code && $storedCode && $code === $storedCode) {
            $session->set('reset_password_verified', true);
            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['error' => 'Invalid or expired verification code.'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/forgot-password/reset', name: 'app_forgot_password_reset', methods: ['POST'])]
    public function reset(
        Request $request, 
        UsersRepository $usersRepository, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $session = $this->requestStack->getSession();
        
        if (!$session->get('reset_password_verified')) {
            return new JsonResponse(['error' => 'Session not verified.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $newPassword = $data['password'] ?? '';
        $email = $session->get('reset_password_email');

        if (!$newPassword || strlen($newPassword) < 6) {
            return new JsonResponse(['error' => 'Password must be at least 6 characters.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $usersRepository->findOneBy(['email.value' => $email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User no longer exists.'], Response::HTTP_NOT_FOUND);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        $entityManager->flush();

        // Clear session after success
        $session->remove('reset_password_code');
        $session->remove('reset_password_email');
        $session->remove('reset_password_verified');

        return new JsonResponse(['success' => true]);
    }
}
