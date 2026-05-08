<?php
// src/Security/LoginFormAuthenticator.php

namespace App\Security;

use App\Entity\Users;
use App\Entity\LoginAttempts;
use App\Entity\Email;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Doctrine\ORM\EntityManagerInterface;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $em
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username', '');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        $loginAttempt = $this->em->getRepository(LoginAttempts::class)->find($email);
        if ($loginAttempt) {
            $now = new \DateTime();

            if ($loginAttempt->getBannedUntil() !== null) {
                throw new CustomUserMessageAuthenticationException('Your account has been permanently banned.');
            }

            if ($loginAttempt->getCooldownUntil() !== null && $loginAttempt->getCooldownUntil() > $now) {
                // Store timestamp in session for the frontend live timer
                $request->getSession()->set('cooldown_until_timestamp', $loginAttempt->getCooldownUntil()->getTimestamp());

                if ($loginAttempt->getAttemptCount() === 3) {
                    throw new CustomUserMessageAuthenticationException('Too many failed attempts. Try again in 30 seconds.');
                } else {
                    throw new CustomUserMessageAuthenticationException('You are temporarily banned.');
                }
            }
        }

        return new Passport(
            new UserBadge($email, function (string $identifier) {
                // Load user manually so we can check ban status HERE
                // before the password is even checked
                $user = $this->em->getRepository(Users::class)
                    ->findOneBy(['email.value' => $identifier]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException(
                        'No account found with this email address.'
                    );
                }

                // ── BANNED check happens at authentication time ────────────────
                // This throws a custom exception before any session is created,
                // which means no 403 error — just a clean login error message.
                if ($user->getRole() === 'BANNED') {
                    throw new CustomUserMessageAuthenticationException('BANNED');
                }

                return $user;
            }),
            new PasswordCredentials($request->request->get('_password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var Users $user */
        $user = $token->getUser();

        // Reset failed login attempts on success
        $loginAttempt = $this->em->getRepository(LoginAttempts::class)->find($user->getEmail()->getValue());
        if ($loginAttempt) {
            $loginAttempt->setAttemptCount(0);
            $loginAttempt->setCooldownUntil(null);
            $loginAttempt->setBannedUntil(null);
            $this->em->flush();
        }

        // Respect saved target path (e.g. user tried to visit /users before logging in)
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Admin → dashboard
        if ($user->getRole() === 'ADMIN') {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        }

        // Regular USER → front office (events)
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): Response
    {
        // Track failed login attempt
        $isBruteForceMessage = $exception instanceof CustomUserMessageAuthenticationException &&
            in_array($exception->getMessageKey(), [
                'BANNED',
                'Your account has been permanently banned.',
                'Too many failed attempts. Try again in 30 seconds.',
                'You are temporarily banned.'
            ]);

        if (!$isBruteForceMessage) {
            $email = $request->request->get('_username', '');
            if ($email) {
                $loginAttempt = $this->em->getRepository(LoginAttempts::class)->find($email);
                if (!$loginAttempt) {
                    $loginAttempt = new LoginAttempts();
                    $loginAttempt->setEmail($email);
                    $this->em->persist($loginAttempt);
                }

                $loginAttempt->setAttemptCount($loginAttempt->getAttemptCount() + 1);
                $loginAttempt->setLastAttemptTime(new \DateTimeImmutable());
                $count = $loginAttempt->getAttemptCount();

                if ($count >= 8) {
                    $loginAttempt->setBannedUntil(new \DateTimeImmutable()); // Permanent ban
                } elseif ($count === 7) {
                    $loginAttempt->setCooldownUntil(new \DateTimeImmutable('+15 minutes'));
                    $request->getSession()->set('cooldown_until_timestamp', $loginAttempt->getCooldownUntil()->getTimestamp());
                } elseif ($count === 5) {
                    $loginAttempt->setCooldownUntil(new \DateTimeImmutable('+2 minutes'));
                    $request->getSession()->set('cooldown_until_timestamp', $loginAttempt->getCooldownUntil()->getTimestamp());
                } elseif ($count === 3) {
                    $loginAttempt->setCooldownUntil(new \DateTimeImmutable('+30 seconds'));
                    $request->getSession()->set('cooldown_until_timestamp', $loginAttempt->getCooldownUntil()->getTimestamp());
                }

                $this->em->flush();
            }
        }

        // If the failure reason is a ban, redirect to the dedicated banned page
        if ($exception instanceof CustomUserMessageAuthenticationException
            && $exception->getMessageKey() === 'BANNED'
        ) {
            return new RedirectResponse($this->urlGenerator->generate('app_banned'));
        }

        // Otherwise store the error and redirect back to login
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}