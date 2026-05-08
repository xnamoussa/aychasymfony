<?php
// src/Service/SessionManager.php
// Centralized session utility — inject this in any controller or service.
// Your colleagues use this to access session data and current user info
// without duplicating code across modules.
//
// Usage:
//   public function myAction(SessionManager $session): Response
//   {
//       $session->set('key', 'value');
//       $id   = $session->getCurrentUserId();
//       $role = $session->getCurrentUserRole();
//       $session->setForModule('camping', 'site_id', 42);
//   }

namespace App\Service;

use App\Entity\Users;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class SessionManager
{
    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
    ) {}

    // ── Raw session ───────────────────────────────────────────────────────────
    public function getSession(): SessionInterface { return $this->requestStack->getSession(); }
    public function set(string $key, mixed $value): void { $this->getSession()->set($key, $value); }
    public function get(string $key, mixed $default = null): mixed { return $this->getSession()->get($key, $default); }
    public function has(string $key): bool { return $this->getSession()->has($key); }
    public function remove(string $key): void { $this->getSession()->remove($key); }
    public function invalidate(): void { $this->getSession()->invalidate(); }
    public function flash(string $type, string $message): void {
        $session = $this->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }

    // ── Current user ──────────────────────────────────────────────────────────
    public function getCurrentUser(): ?Users { $u = $this->security->getUser(); return ($u instanceof Users) ? $u : null; }
    public function getCurrentUserId(): ?int { return $this->getCurrentUser()?->getId(); }
    public function getCurrentUserName(): ?string { return $this->getCurrentUser()?->getName(); }
    public function getCurrentUserEmail(): ?string { return $this->getCurrentUser()?->getEmail(); }
    public function getCurrentUserRole(): ?string { return $this->getCurrentUser()?->getRole(); }
    public function getCurrentUserImage(): ?string { return $this->getCurrentUser()?->getImage(); }
    public function isLoggedIn(): bool { return $this->getCurrentUser() !== null; }
    public function isAdmin(): bool { return $this->getCurrentUserRole() === 'ADMIN'; }
    public function isUser(): bool { return $this->getCurrentUserRole() === 'USER'; }

    // ── Namespaced module storage (prevents key collisions between modules) ────
    public function setForModule(string $module, string $key, mixed $value): void { $this->set("mod_{$module}_{$key}", $value); }
    public function getForModule(string $module, string $key, mixed $default = null): mixed { return $this->get("mod_{$module}_{$key}", $default); }
    public function removeForModule(string $module, string $key): void { $this->remove("mod_{$module}_{$key}"); }
}