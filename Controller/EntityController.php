<?php

namespace Hn\EntityBundle\Controller;

use Doctrine\Common\Util\Debug;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class EntityController extends Controller
{
    /**
     * @Route("delete/{class}/{id}")
     */
    public function deleteAction($class, $id, Request $request)
    {
        // not all variables can be put into the url
        $redirect = $request->get('redirect', null);
        $token = $request->get('token');

        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = $this->get('security.csrf.token_manager');
        $token = new CsrfToken("$class:$id:$redirect", $token);
        if (!$csrf->isTokenValid($token)) {
            throw new AccessDeniedException("csrf tokens is not valid, got '$token'");
        }

        $entity = $this->getManager()->find($class, $id);
        $this->get('hn_entity.dependency')->safeRemove($entity);

        $miniClassName = $this->get('hn_entity.entity')->getMinimalisticClassName($entity);
        $this->addFlashMessage('success', "removed.entity.$miniClassName");

        if ($redirect !== null) {
            return new RedirectResponse($redirect);
        } else {
            return new Response('', 204);
        }
    }

    /**
     * Creates the edit form and extracts the partial specified by $propertyPath.
     * Then binds the request to it partial and renders its form view.
     *
     * @Route("form/update_partial/{formType}/{propertyPath}/{entityClass}/{entityId}", name="hn_entity_entity_updatepartial")
     * @Template("HnEntityBundle:Entity:partialForm.html.twig")
     */
    public function updatePartialAction(Request $request, $formType, $propertyPath, $entityClass, $entityId = null)
    {
        if ($entityId) {
            $entity = $this->getRepository($entityClass)->find($entityId);
        } else {

            $entity = new $entityClass();
        }

        $form = $this->createForm($formType, $entity);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $propertyPath = preg_replace('/^'.$form->getName().'/', '', $propertyPath);
        $propertyPath = preg_replace('/\[\]$/', '', $propertyPath);
        /** @var Form $partialForm */
        $partialForm = $propertyAccessor->getValue($form, $propertyPath);

        if ($request->query->has($partialForm->getName())) {
            $partialForm->submit($request);
        }


        return array(
            'partialForm' => $partialForm->createView()
        );
    }

    /**
     * Creates the form specified by $formType and submits the request to it.
     * Then extracts the partial specified by $propertyPath and renders its form view.
     *
     * @Route("form/submit_form_update_partial/{formType}/{propertyPath}", name="hn_entity_entity_submitformupdatepartial")
     * @Template("HnEntityBundle:Entity:partialForm.html.twig")
     */
    public function submitFormUpdatePartialAction(Request $request, $formType, $propertyPath)
    {

        $form = $this->createForm($formType);
        $form->submit($request);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyPath = preg_replace('/^'.$form->getName().'/', '', $propertyPath);
        $propertyPath = preg_replace('/\[\]$/', '', $propertyPath);
        /** @var Form $partialForm */
        $partialForm = $propertyAccessor->getValue($form, $propertyPath);

        return array(
            'partialForm' => $partialForm->createView()
        );
    }

    /**
     * This method will be used from x-editable to update a field
     *
     * @Route("update_property/{formTypeClass}/{formBaseName}/{entityClass}/{entityId}")
     */
    public function updatePropertyAction(Request $request, $formTypeClass, $formBaseName, $entityClass, $entityId = null)
    {
        if ($entityId !== null) {
            $entity = $this->getRepository($entityClass)->find($entityId);
        } else {
            $entity = new $entityClass();
        }

        /** @var FormFactoryInterface $formFactory */
        $formFactory = $this->get('form.factory');

        if (class_exists($formTypeClass)) {
            $formType = new $formTypeClass();
        } else {
            $formType = $formTypeClass;
        }

        $form = $formFactory->createNamed($formBaseName, $formType, $entity, array(
            'csrf_protection' => false
        ));
        $form->submit($request, false);

        if ($form->isValid()) {
            $this->getManager()->persist($entity);
            $this->getManager()->flush();
            return new Response('', 200);
        } else {
            $errors = array();
            /** @var FormError $error */
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new Response(implode("\n", $errors), 422);
        }
    }



    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * @param $classname
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository($classname)
    {
        return $this->getManager()->getRepository($classname);
    }

    protected function addFlashMessage($type, $message)
    {
        /** @var Session $session */
        $session = $this->get('session');
        $session->getFlashBag()->add($type, $message);
    }
}