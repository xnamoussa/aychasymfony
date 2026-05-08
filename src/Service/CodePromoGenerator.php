<?php

namespace App\Service;

use App\Entity\CodePromo;
use App\Repository\CodePromoRepository;
use Doctrine\ORM\EntityManagerInterface;

class CodePromoGenerator
{
    public function __construct(
        private EntityManagerInterface $em,
        private CodePromoRepository $repo
    ) {}

    /**
     * Generates a code based on a name, with 15% discount by default.
     */
    public function generateForMeal(string $mealName): CodePromo
    {
        $codeStr = 'AUTO-' . strtoupper(preg_replace('/[^A-Z]/i', '', $mealName));
        $codeStr = substr($codeStr, 0, 15);

        // Check if code already exists
        $existing = $this->repo->findOneByCodeUppercase($codeStr);
        if ($existing) {
            return $existing;
        }

        $code = new CodePromo();
        $code->setCode($codeStr);
        $code->setDiscountPercentage(15);
        $code->setUsageLimit(50);
        $code->setExpirationDate((new \DateTime())->modify('+1 week'));
        
        $this->em->persist($code);
        $this->em->flush();

        return $code;
    }

    /**
     * Validates if a code string is usable.
     */
    public function validateCode(string $codeStr): ?CodePromo
    {
        $code = $this->repo->findOneByCodeUppercase($codeStr);
        if ($code && $code->canBeUsed()) {
            return $code;
        }
        return null;
    }

    /**
     * Applies a code and increments its usage.
     */
    public function useCode(string $codeStr): bool
    {
        $code = $this->validateCode($codeStr);
        if ($code) {
            $code->use();
            $this->em->flush();
            return true;
        }
        return false;
    }
}
