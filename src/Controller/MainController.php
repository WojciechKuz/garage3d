<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Item;
use App\Entity\Photo;
use App\Form\ItemForm;
use App\Repository\FileRepository;
use App\Repository\ItemRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\Image;
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
        private UserRepository $userRepository,
        private ItemRepository $itemRepository,
        private FileRepository $fileRepository,
        private PhotoRepository $photoRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads/stl')] private string $stlDirectory,
        #[Autowire('%kernel.project_dir%/public/uploads/images')] private string $imageDirectory
    ) {}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        $itemCount = $this->itemRepository->count(); // repository.count() instead of php's default count(array), which counts recursively

        // "main" site will soon be removed
        return new Response(
            $this->twig->render('main/index.html.twig', [
                'itemCount' => $itemCount,
            ])
        );
        //return $this->redirectToRoute('item_list', ['offset' => 0]);
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
    #[Route('/item/{item_id}', name: 'item_page')] // TODO replace id in route parameter with slug
    public function item(Request $request, int $item_id): Response {

        $selectedItem = $this->itemRepository->find($item_id);
        $form = $this->handleForm($request, $selectedItem);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('item', ['item_id' => $item_id]);
        }

        return new Response(
            $this->twig->render('main/item.html.twig', [
                'item' => $selectedItem,
                'editForm' => $form->createView(),
            ])
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/user/{user_id}', name: 'user_page')] // TODO replace id in route parameter with slug
    public function user(int $user_id): Response {

        $selectedUser = $this->userRepository->find($user_id);
        return new Response(
            $this->twig->render('main/user.html.twig', [
                'user' => $selectedUser,
            ])
        );
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/additem', name: 'add_item')]
    public function addItem(
        Request $request,
    ): Response {
        $item = new Item();
        $form = $this->handleForm($request, $item);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('item_list', ['offset' => 0]); // TODO change later to redirect to this item
        }

        return new Response($this->twig->render('main/newitem.html.twig', [
            'newItemForm' => $form->createView(),
        ]));
    }

    #[Route('/deleteitem/{item_id}', name: 'delete_item')]
    public function deleteItem(int $item_id): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $item = $this->itemRepository->find($item_id);
        $this->entityManager->remove($item);
        $this->entityManager->flush();
        return $this->redirectToRoute('item_list', ['offset' => 0]);
    }

    #[Route('/deletefile/{file_id}/{back_to}', name: 'delete_file')]
    public function deleteFile(int $file_id, int $back_to): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $file = $this->fileRepository->find($file_id);
        $this->entityManager->remove($file);
        $this->entityManager->flush();
        return $this->redirectToRoute('item', ['item_id' => $back_to]);
    }

    #[Route('/deletephoto/{photo_id}/{back_to}', name: 'delete_photo')]
    public function deletePhoto(int $photo_id, int $back_to): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $photo = $this->photoRepository->find($photo_id);
        $this->entityManager->remove($photo);
        $this->entityManager->flush();
        return $this->redirectToRoute('item', ['item_id' => $back_to]);
    }

    /** For passed Item (it may be new) creates a form, and */
    public function handleForm(
        Request $request, Item $item
    ): FormInterface
    {
        $form = $this->createForm(ItemForm::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $item->setAuthor($this->getUser()); // itemName, description automatic, author manual
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            /** @var UploadedFile $stlFiles */
            $stlFiles = $form->get('files')->getData();
            if($stlFiles) {
                foreach ($stlFiles as $stlFile) {
                    // create file entity, write to DB, save to disc
                    $file = new File();
                    $file->setItem($item);
                    $originalFilename = (pathinfo($stlFile->getClientOriginalName(), PATHINFO_FILENAME));
                    $safeFilename = $this->slugger->slug($originalFilename);
                    //$newFilename = $safeFilename .'-'.uniqid().'.'. $stlFile->guessExtension(); // It can't guess extension of stl
                    $newFilename = $safeFilename .'-'.uniqid().'.'. 'stl';
                    //try {
                    $stlFile->move($this->stlDirectory, $newFilename); // written to folder
                    //} catch (FileException $e) {}
                    if(str_ends_with($originalFilename, '.stl')) {
                        $file->setFilename($originalFilename);
                    } else {
                        $file->setFilename($originalFilename.'.stl');
                    }
                    $file->setServerFilename($newFilename);

                    $this->entityManager->persist($file); // write to DB
                    $this->entityManager->flush();
                }
            }

            /** @var UploadedFile $imgFiles */
            $imgFiles = $form->get('images')->getData();
            if($imgFiles) {
                foreach ($imgFiles as $imgFile) {
                    // Create Photo entity, write to DB, save to disc
                    $photo = new Photo();
                    $photo->setItem($item);
                    $originalFilename = (pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME));
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename .'-'.uniqid().'.'. $imgFile->guessExtension();
                    //try {
                    $imgFile->move($this->imageDirectory, $newFilename); // write to folder
                    //} catch (FileException $e) {}
                    $photo->setPhotoname($originalFilename);
                    $photo->setServerPhotoname($newFilename);

                    $this->entityManager->persist($photo); // write to DB
                    $this->entityManager->flush();
                }
            }
        }
        return $form;
    }
}
