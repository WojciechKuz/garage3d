<?php

namespace App\Form;

use App\Entity\Item;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ItemForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('itemName')
            ->add('files', FileType::class, [
                'label' => 'Add stl files that are part of this item project',
                'mapped' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '16M',
                                'maxSizeMessage' => 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}',
                                'mimeTypes' => 'application/octet-stream',
                                'extensions' => 'stl',
                                'extensionsMessage' => 'Upload stl files only.',
                            ]),
                        ],
                    ]),
                ],
                'required' => false,
            ])
            ->add('description', TextareaType::class)
            ->add('images', FileType::class, [
                'label' => 'Add images of your item',
                'mapped' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        'constraints' => [
                            new Image([
                                'maxSize' => '16M',
                                'maxSizeMessage' => 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}',
                                'extensions' => ['jpg', 'jpeg', 'png'],
                            ])
                        ]
                    ])
                ],
                'required' => false,
            ])
            // TODO add private/public toggle
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
        ]);
    }
}
