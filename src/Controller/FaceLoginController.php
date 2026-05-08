<?php

namespace App\Controller;

use App\Entity\FaceData;
use App\Entity\Users;
use App\Entity\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class FaceLoginController extends AbstractController
{
    #[Route('/face/enroll', name: 'api_face_enroll', methods: ['POST'])]
    public function enrollFace(Request $request, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['_csrf_token']) || !$csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $data['_csrf_token']))) {
            return $this->json(['error' => 'Invalid CSRF token.'], 403);
        }

        if (!isset($data['email']) || !isset($data['descriptor'])) {
            return $this->json(['error' => 'Email and face descriptor are required.'], 400);
        }

        $email = $data['email'];
        $descriptor = $data['descriptor'];

        $user = $em->getRepository(Users::class)->findOneBy(['email.value' => $email]);
        if (!$user) {
            return $this->json(['error' => 'No account found with this email. Please register first.'], 400);
        }

        $faceData = $em->getRepository(FaceData::class)->findOneBy(['email.value' => $email]);
        if ($faceData) {
            return $this->json(['error' => 'A face is already enrolled for this email.'], 400);
        }

        $faceData = new FaceData();
        $faceData->setEmail(new Email($email));
        $faceData->setFaceDescriptor($descriptor);

        $em->persist($faceData);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/face/login', name: 'api_face_login', methods: ['POST'])]
    public function loginFace(Request $request, EntityManagerInterface $em, TokenStorageInterface $tokenStorage, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['_csrf_token']) || !$csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $data['_csrf_token']))) {
            return $this->json(['error' => 'Invalid CSRF token.'], 403);
        }

        if (!isset($data['descriptor'])) {
            return $this->json(['error' => 'Face descriptor is required.'], 400);
        }

        $incomingDescriptor = $data['descriptor'];
        
        $allFaceData = $em->getRepository(FaceData::class)->findAll();
        $bestMatch = null;
        $lowestDistance = 0.50; // Threshold. 0.6 is lib default, 0.5 is stricter

        foreach ($allFaceData as $row) {
            $dbDescriptor = $row->getFaceDescriptor();
            $distance = $this->euclideanDistance($incomingDescriptor, $dbDescriptor);
            if ($distance < $lowestDistance) {
                $lowestDistance = $distance;
                $bestMatch = $row;
            }
        }

        if (!$bestMatch) {
            return $this->json(['error' => 'Face not recognized. Retry or enroll your face.']);
        }

        $email = $bestMatch->getEmail();
        $user = $em->getRepository(Users::class)->findOneBy(['email.value' => (string)$email]);

        if (!$user) {
            return $this->json(['error' => 'User account not found.']);
        }

        if (strtoupper($user->getRole()) === 'BANNED') {
            return $this->json(['error' => 'BANNED', 'redirect' => $this->generateUrl('app_banned')]);
        }

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));

        $target = $this->generateUrl('app_profile');
        if (strtoupper($user->getRole()) === 'ADMIN' || in_array('ROLE_ADMIN', $user->getRoles())) {
            $target = $this->generateUrl('app_users_index');
        }

        return $this->json(['success' => true, 'redirect' => $target]);
    }

    /**
     * @param float[] $desc1
     * @param float[] $desc2
     */
    private function euclideanDistance(array $desc1, array $desc2): float
    {
        if (count($desc1) !== count($desc2)) return 999.0;
        $sum = 0.0;
        for ($i = 0; $i < count($desc1); $i++) {
            $diff = (float)$desc1[$i] - (float)$desc2[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }
}
