<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Item;
use App\Entity\Likes;
use App\Entity\Photo;
use App\Entity\User;
use App\Form\EditAboutForm;
use App\Form\ItemForm;
use App\Repository\FileRepository;
use App\Repository\ItemRepository;
use App\Repository\LikesRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
        private LikesRepository $likesRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads/stl')] private string $stlDirectory,
        #[Autowire('%kernel.project_dir%/public/uploads/images')] private string $imageDirectory
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
     * @throws Exception
     */
    #[Route('/like/{item_id}', name: 'like')]
    public function like(int $item_id): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $loggedInUser = $this->getUser();
        if ($loggedInUser == null) {
            throw new Exception('Logged in user is null!');
        }
        if($this->likesRepository->checkIsLiked($loggedInUser->getId(), $item_id) == 0) {
            // like
            $like = new Likes();
            $like->setWhoLikes($loggedInUser);
            $like->setLikedItem($this->itemRepository->find($item_id));
            $this->entityManager->persist($like);
            $this->entityManager->flush();
        } else {
            // remove like
            $like = $this->likesRepository->findLike($loggedInUser->getId(), $item_id);
            if ($like == null) {
                return $this->redirectToRoute('item_page', ['item_id' => $item_id]);
                //throw new Exception('Like not found, can\'t remove it.');
            }
            $this->entityManager->remove($like);
            $this->entityManager->flush();
        }
        return $this->redirectToRoute('item_page', ['item_id' => $item_id]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/item/{item_id}', name: 'item_page')]
    public function item(Request $request, int $item_id): Response {

        $selectedItem = $this->itemRepository->find($item_id);
        $form = $this->handleForm($request, $selectedItem);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('item_page', ['item_id' => $item_id]);
        }
        $loggedInUser = $this->getUser();
        $isLiked = 0;
        if($loggedInUser != null) {
            $isLiked = $this->likesRepository->checkIsLiked($loggedInUser->getId(), $selectedItem->getId());
        }

        return new Response(
            $this->twig->render('main/item.html.twig', [
                'item' => $selectedItem,
                'editForm' => $form->createView(),
                'likeCount' => $this->likesRepository->countLikesForItem($selectedItem->getId()),
                'isLiked' => $isLiked,
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
            return $this->redirectToRoute('item_page', ['item_id' => $item->getId()]);
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
        return $this->redirectToRoute('item_page', ['item_id' => $back_to]);
    }

    #[Route('/deletephoto/{photo_id}/{back_to}', name: 'delete_photo')]
    public function deletePhoto(int $photo_id, int $back_to): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $photo = $this->photoRepository->find($photo_id);
        $this->entityManager->remove($photo);
        $this->entityManager->flush();
        return $this->redirectToRoute('item_page', ['item_id' => $back_to]);
    }

    /** For passed Item (it may be new) creates a form, submits data to database, adds files */
    private function handleForm(
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
