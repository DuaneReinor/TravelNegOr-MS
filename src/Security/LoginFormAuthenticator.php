<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

   public function authenticate(Request $request): Passport
{
    // ✅ Read form values from POST request
    $email = $request->request->get('email', '');
    $password = $request->request->get('password', '');
    $csrfToken = $request->request->get('_csrf_token', '');

    // ✅ Store last username for auto-fill on login error
    $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

    return new Passport(
        new UserBadge($email),
        new PasswordCredentials($password),
        [
            new CsrfTokenBadge('authenticate', $csrfToken),
            new RememberMeBadge(),
        ]
    );
}


   public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
    // If the user was trying to access a protected page, redirect them there
    if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
        return new RedirectResponse($targetPath);
    }

    $user = $token->getUser();

    // ✅ If Admin, go to dashboard
    if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
        return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
    }

    // ✅ Otherwise, redirect normal users to home page
    return new RedirectResponse($this->urlGenerator->generate('home'));
}


    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
