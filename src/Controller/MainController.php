<?php

namespace App\Controller;

use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class MainController extends AbstractController
{
    public function __construct(
        private Environment $twig,
        private ItemRepository $itemRepository
    ) {}

    public function registration(UserPasswordHasherInterface $passwordHasher): void { // return Response
        $user = 'user from form';
        $plaintextPassword = 'password';
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
    }

    public function validatedActionExample(UserPasswordHasherInterface $passwordHasher, UserInterface $user): void
    {
        // ... e.g. get the password from a "confirm deletion" dialog
        $plaintextPassword = 'password';

        if (!$passwordHasher->isPasswordValid($user, $plaintextPassword)) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        $itemCount = $this->itemRepository->count(); // repository.count() instead of php's default count(array), which counts recursively

        return new Response(
            $this->twig->render('main/index.html.twig', [
                'itemCount' => $itemCount,
            ])
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/login/{isnew}', name: 'login')]
    public function login(int $isNewInt = 0): Response {
        $isNew = ($isNewInt === 1);
        return new Response(
            $this->twig->render('main/login.html.twig', [
                'isNew' => $isNew,
            ])
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/browse/{offset}', name: 'item_list')]
    public function items(int $offset): Response // why Request does not work?
    {
        //$offset = max(0, $request->query->getInt('offset', -1));
        $paginatedItems = $this->itemRepository->getItemPaginator($offset);
        $itemCount = $this->itemRepository->count(); // all elements
        //$itemCount = count($paginatedItems);

        return new Response(
            $this->twig->render('main/list.html.twig', [
                'controller_name' => 'MainController',
                'items' => $paginatedItems,
                'itemCount' => $itemCount,
                'previous' => $offset - ItemRepository::PAGINATION_PAGE_SIZE,
                'next' => min(
                    $itemCount, $offset + ItemRepository::PAGINATION_PAGE_SIZE
                ),
                'first' => 0,
                'last' => $itemCount - ItemRepository::PAGINATION_PAGE_SIZE,
            ])
        );
    }
}
