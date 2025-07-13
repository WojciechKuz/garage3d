<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditUserForm;
use App\Form\RegistrationForm;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form,
            ]);
        }
        // when form submitted and valid:

        /** @var string $plainPassword */
        $plainPassword = $form->get('plainPassword')->getData();

        // encode the plain password
        $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

        $user->setAbout("");
        $user->setRoles(["ROLE_USER"]);

        $entityManager->persist($user);
        $entityManager->flush();

        // do anything else you need here, like send an email

        return $security->login($user, 'form_login', 'main');
    }

    /**
     * @throws Exception
     */
    #[Route('/changepswd', name: 'change_password')]
    public function changePassword(
        Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        throw new Exception('Changing password is not implemented yet.'); // There are some issues. Checking old password doesn't work.

        $user = $this->getUser();
        if($user == null) { $user = new User(); }
        $form = $this->createForm(EditUserForm::class, $user);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render('registration/changepswd.html.twig', [
                'status' => '',
                'changepswdForm' => $form,
            ]);
        }
        // when form submitted and valid:

        /** @var string $oldPlainPassword */
        $oldPlainPassword = $form->get('oldPlainPassword')->getData();
        /** @var string $newPlainPassword */
        $newPlainPassword = $form->get('newPlainPassword')->getData();

        $oldht = $userPasswordHasher->hashPassword($user, $oldPlainPassword);
        if ($userPasswordHasher->hashPassword($user, $oldPlainPassword) != $user->getPassword()) {
            return $this->render('registration/changepswd.html.twig', [
                'status' => 'Incorrect old password OLDHT: "'.$oldht.'" real: "'.$user->getPassword().'"!',
                'changepswdForm' => $form,
            ]);
        }
        // update password only when old password matched:

        $user->setPassword($userPasswordHasher->hashPassword($user, $newPlainPassword));
        $entityManager->persist($user);
        $entityManager->flush();

        // do anything else you need here, like send an email

        return $security->login($user, 'form_login', 'main');
    }
}
