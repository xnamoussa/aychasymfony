<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class DummyAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        // Authenticate every request
        return true; 
    }

    public function authenticate(Request $request): Passport
    {
        $session = $request->getSession();
        $role = $session->get('current_role', 'user'); // Default to 'user'
        
        $identifier = ($role === 'admin') ? 'admin_user' : 'standard_user';

        return new SelfValidatingPassport(new UserBadge($identifier));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // On success, do nothing, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
