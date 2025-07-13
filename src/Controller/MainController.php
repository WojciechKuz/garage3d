<?php

namespace App\Controller;

use App\Form\EditAboutForm;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class MainController extends AbstractController
{
    public function __construct(
        private Environment $twig,
        private UserRepository $userRepository,
        private ItemRepository $itemRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        return $this->redirectToRoute('item_list', ['offset' => 0]);
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
    public function items(int $offset): Response
    {
        $paginatedItems = $this->itemRepository->getItemPaginator($offset);
        $itemCount = $this->itemRepository->count(); // all elements

        return new Response(
            $this->twig->render('main/list.html.twig', [
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

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/user/{user_id}', name: 'user_page')]
    public function user(Request $request, int $user_id): Response {

        $selectedUser = $this->userRepository->find($user_id);

        $form = $this->createForm(EditAboutForm::class, $selectedUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($selectedUser);
            $this->entityManager->flush();
            return $this->redirectToRoute('user_page', ['user_id' => $user_id]);
        }
        return new Response(
            $this->twig->render('main/user.html.twig', [
                'user' => $selectedUser,
                'editAboutForm' => $form->createView(),
            ])
        );
    }
}
