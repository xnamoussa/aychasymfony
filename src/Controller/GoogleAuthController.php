<?php
// src/Controller/GoogleAuthController.php
//
// Handles Google OAuth2 login using KnpU OAuth2 Client Bundle.
//
// SETUP STEPS (run once):
//   composer require knpuniversity/oauth2-client-bundle league/oauth2-google
//
// Then add to config/packages/knpu_oauth2_client.yaml:
//
//   knpu_oauth2_client:
//     clients:
//       google:
//         type: google
//         client_id: '%env(GOOGLE_CLIENT_ID)%'
//         client_secret: '%env(GOOGLE_CLIENT_SECRET)%'
//         redirect_route: connect_google_check
//         redirect_params: {}
//
// And add to your .env file:
//   GOOGLE_CLIENT_ID=your_client_id_from_google_console
//   GOOGLE_CLIENT_SECRET=your_client_secret_from_google_console
//
// In Google Cloud Console:
//   1. Create a project → APIs & Services → Credentials → OAuth 2.0 Client ID
//   2. Authorized redirect URI: http://127.0.0.1:8000/connect/google/check
//   3. Copy Client ID and Secret into your .env

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Email;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;

class GoogleAuthController extends AbstractController
{
    // ── Step 1: Redirect user to Google ──────────────────────────────────────
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    // ── Step 2: Google redirects back here with a code ───────────────────────
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(
        Request $request,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $authenticator
    ): Response {
        $client = $clientRegistry->getClient('google');

        try {
            /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
            $googleUser = $client->fetchUser();

            $email = $googleUser->getEmail();
            $name  = $googleUser->getName() ?: $googleUser->getFirstName() ?: 'User';

            // Try to find existing user by email
            $user = $em->getRepository(Users::class)->findOneBy(['email.value' => $email]);

            if (!$user) {
                // First-time Google login → auto-register
                $user = new Users();
                $user->setEmail(new Email($email));
                $user->setName($name);
                $user->setRole('USER');

                // Set a random unusable password (user will never type this)
                $user->setPassword($hasher->hashPassword($user, bin2hex(random_bytes(16))));

                // Use Google profile photo if available
                $avatarUrl = $googleUser->getAvatar();
                if ($avatarUrl) {
                    // Download and save the avatar
                    $imageData = @file_get_contents($avatarUrl);
                    if ($imageData !== false) {
                        $filename = 'google_' . uniqid() . '.jpg';
                        file_put_contents(
                            $this->getParameter('images_directory') . '/' . $filename,
                            $imageData
                        );
                        $user->setImage($filename);
                    }
                }

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Welcome to LAMMA, ' . $name . '! Your account has been created.');
            } else {
                // Existing user — check if banned
                if ($user->getRole() === 'BANNED') {
                    return $this->redirectToRoute('app_banned');
                }
            }

            // Authenticate the user programmatically (creates the session)
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );

        } catch (IdentityProviderException $e) {
            $this->addFlash('error', 'Google authentication failed. Please try again.');
            return $this->redirectToRoute('app_login');
        }
    }
}