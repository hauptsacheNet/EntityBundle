<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 19.09.14
 * Time: 16:35
 */

namespace Hn\EntityBundle\Form;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Hn\EntityBundle\Form\DataTransformer\CommaListTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class TagType extends AbstractType
{
    private $manager;

    private $propertyAccessor;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new CommaListTransformer($this->manager, $options['class'], $options['property'], $options['allow_create']);
        $builder->addViewTransformer($transformer);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('class'));
        $resolver->setDefaults(array(
            'property' => 'name',
            'minimum_input_length' => 0,
            'maximum_input_length' => 32,
            'token_separators' => array(' ', ','),
            'allow_create' => false
        ));
    }


    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var EntityRepository $tagRepository */
        $tagRepository = $this->manager->getRepository($options['class']);
        $tags = $tagRepository->findAll();
        $tagData = array();

        foreach ($tags as $tag) {
            $tagName = $this->propertyAccessor->getValue($tag, $options['property']);
            $tagData[] = array(
                "id" => $tagName,
                "text" => $tagName
            );
        }

        $view->vars["tags"] = $tagData;

        $copyOptions = array(
            'minimum_input_length',
            'maximum_input_length',
            'token_separators',
            'allow_create'
        );
        foreach ($copyOptions as $key) {
            $view->vars[$key] = $options[$key];
        }
    }

    public function getParent()
    {
        return 'text';
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'hn_entity_tag';
    }
}