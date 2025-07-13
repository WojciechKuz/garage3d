<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\File;
use App\Entity\Item;
use App\Entity\Likes;
use App\Entity\Photo;
use App\Form\CommentForm;
use App\Form\ItemForm;
use App\Repository\CommentRepository;
use App\Repository\FileRepository;
use App\Repository\ItemRepository;
use App\Repository\LikesRepository;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Item controller controls parsing of item.html.twig and newitem.html.twig.
 * Also, handles ItemForm (adding and editing of item), commentForm.
 * Has actions to like item and remove like from item, delete stl file, photo and whole item.
 */
final class ItemController extends AbstractController
{
    public function __construct(
        private Environment $twig,
        private ItemRepository $itemRepository,
        private FileRepository $fileRepository,
        private PhotoRepository $photoRepository,
        private LikesRepository $likesRepository,
        private CommentRepository $commentRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads/stl')] private string $stlDirectory,
        #[Autowire('%kernel.project_dir%/public/uploads/images')] private string $imageDirectory
    ) {}

    /**
     * Like or remove like from item.
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
     * Show Item with photo gallery, likes, comments and files.
     * Handles ItemForm (editing) and CommentForm.
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/item/{item_id}', name: 'item_page')]
    public function item(Request $request, int $item_id): Response {
        $selectedItem = $this->itemRepository->find($item_id);

        $loggedInUser = $this->getUser();
        $isLiked = 0;
        if($loggedInUser != null) {
            $isLiked = $this->likesRepository->checkIsLiked($loggedInUser->getId(), $selectedItem->getId());
        }

        // edit form
        $editForm = $this->handleItemForm($request, $selectedItem);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            return $this->redirectToRoute('item_page', ['item_id' => $item_id]);
        }

        // comment form
        $comment = new Comment();
        $commentForm = $this->createForm(CommentForm::class, $comment);
        $commentForm->handleRequest($request);
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setAuthor($loggedInUser);
            $comment->setItem($selectedItem);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->redirectToRoute('item_page', ['item_id' => $item_id]);
        }


        return new Response(
            $this->twig->render('item/item.html.twig', [
                'item' => $selectedItem,
                'editForm' => $editForm->createView(),
                'likeCount' => $this->likesRepository->countLikesForItem($selectedItem->getId()),
                'isLiked' => $isLiked,
                'comments' => $this->commentRepository->findByItem($item_id),
                'commentForm' => $commentForm->createView(),
            ])
        );
    }

    /**
     * Show page with form to add new item.
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/additem', name: 'add_item')]
    public function addItem(
        Request $request,
    ): Response {
        $item = new Item();
        $form = $this->handleItemForm($request, $item);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('item_page', ['item_id' => $item->getId()]);
        }

        return new Response($this->twig->render('item/newitem.html.twig', [
            'newItemForm' => $form->createView(),
        ]));
    }

    /**
     * Action of deleting whole item.
     */
    #[Route('/deleteitem/{item_id}', name: 'delete_item')]
    public function deleteItem(int $item_id): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $item = $this->itemRepository->find($item_id);
        $this->entityManager->remove($item);
        $this->entityManager->flush();
        return $this->redirectToRoute('item_list', ['offset' => 0]);
    }

    /** Delete file action */
    #[Route('/deletefile/{file_id}/{back_to}', name: 'delete_file')]
    public function deleteFile(int $file_id, int $back_to): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $file = $this->fileRepository->find($file_id);
        $this->entityManager->remove($file);
        $this->entityManager->flush();
        return $this->redirectToRoute('item_page', ['item_id' => $back_to]);
    }

    /** Delete photo action */
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
    private function handleItemForm(
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
