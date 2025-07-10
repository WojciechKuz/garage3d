<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Item;
use App\Entity\Photo;
use App\Form\ItemForm;
use App\Repository\FileRepository;
use App\Repository\ItemRepository;
use App\Repository\PhotoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        private ItemRepository $itemRepository,
        private FileRepository $fileRepository,
        private PhotoRepository $photoRepository,
        private EntityManagerInterface $entityManager,
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

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/additem', name: 'add_item')]
    public function addItem(
        Request $request, SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads/stl')] string $stlDirectory,
        #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imageDirectory
    ): Response {
        $item = new Item();
        $form = $this->createForm(ItemForm::class, $item);
        $form->handleRequest($request);

        //$item->setItemName($form->get('itemName')->getData());
        //$item->setDescription($form->get('description')->getData());

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
                    $safeFilename = $slugger->slug($originalFilename);
                    //$newFilename = $safeFilename .'-'.uniqid().'.'. $stlFile->guessExtension(); // It can't guess extension of stl
                    $newFilename = $safeFilename .'-'.uniqid().'.'. 'stl';
                    //try {
                        $stlFile->move($stlDirectory, $newFilename); // written to folder
                    //} catch (FileException $e) {}
                    $file->setFilename($originalFilename);
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
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename .'-'.uniqid().'.'. $imgFile->guessExtension();
                    //try {
                        $imgFile->move($imageDirectory, $newFilename); // write to folder
                    //} catch (FileException $e) {}
                    $photo->setPhotoname($originalFilename);
                    $photo->setServerPhotoname($newFilename);

                    $this->entityManager->persist($photo); // write to DB
                    $this->entityManager->flush();
                }
            }
            return $this->redirectToRoute('item_list', ['offset' => 0]); // TODO change later to redirect to user's item list
        }

        return new Response($this->twig->render('main/newitem.html.twig', [
            'newItemForm' => $form->createView(),
        ]));
    }
}
