<?php

namespace Oro\Bundle\OrganizationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Datagrid\BusinessUnitUpdateUserDatagridManager;
use Oro\Bundle\OrganizationBundle\Datagrid\BusinessUnitViewUserDatagridManager;
use Oro\Bundle\OrganizationBundle\Datagrid\BusinessUnitDatagridManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/business_unit")
 */
class BusinessUnitController extends Controller
{
    /**
     * Create business_unit form
     *
     * @Route("/create", name="oro_business_unit_create")
     * @Template("OroOrganizationBundle:BusinessUnit:update.html.twig")
     * @Acl(
     *      id="oro_business_unit_create",
     *      type="entity",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="CREATE"
     * )
     */
    public function createAction()
    {
        return $this->update(new BusinessUnit());
    }

    /**
     * @Route("/view/{id}", name="oro_business_unit_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_business_unit_view",
     *      type="entity",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="VIEW"
     * )
     */
    public function viewAction(BusinessUnit $entity)
    {
        return array(
            'datagrid' => $this->getBusinessUnitDatagridManager($entity, 'view')->getDatagrid()->createView(),
            'entity' => $entity,
        );
    }

    /**
     * Edit business_unit form
     *
     * @Route("/update/{id}", name="oro_business_unit_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     * @Acl(
     *      id="oro_business_unit_update",
     *      type="entity",
     *      class="OroOrganizationBundle:BusinessUnit",
     *      permission="EDIT"
     * )
     */
    public function updateAction(BusinessUnit $entity)
    {
        return $this->update($entity);
    }
    
    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_business_unit_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @AclAncestor("oro_business_unit_view")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        /** @var BusinessUnitDatagridManager $gridManager */
        $gridManager = $this->get('oro_organization.business_unit_datagrid_manager');
        $datagridView = $gridManager->getDatagrid()->createView();

        if ('json' == $this->getRequest()->getRequestFormat()) {
            return $this->get('oro_grid.renderer')->renderResultsJsonResponse($datagridView);
        }

        return array('datagrid' => $datagridView);
    }

    /**
     * Get grid users data
     *
     * @Route(
     *      "/update_grid/{id}",
     *      name="oro_business_update_unit_user_grid",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0, "_format"="json"}
     * )
     * @AclAncestor("oro_user_user_view")
     */
    public function updateGridDataAction(BusinessUnit $entity = null)
    {
        if (!$entity) {
            $entity = new BusinessUnit();
        }
        $datagridView = $this->getBusinessUnitDatagridManager($entity, 'update')
            ->getDatagrid()->createView();

        return $this->get('oro_grid.renderer')->renderResultsJsonResponse($datagridView);
    }

    /**
     * Get grid users data
     *
     * @Route(
     *      "/view_grid/{id}",
     *      name="oro_business_view_unit_user_grid",
     *      requirements={"id"="\d+"},
     *      defaults={"_format"="json"}
     * )
     * @AclAncestor("oro_user_user_view")
     */
    public function viewGridDataAction(BusinessUnit $entity)
    {
        $datagridView = $this->getBusinessUnitDatagridManager($entity, 'view')
            ->getDatagrid()->createView();

        return $this->get('oro_grid.renderer')->renderResultsJsonResponse($datagridView);
    }

    /**
     * @param  BusinessUnit $businessUnit
     * @param  string       $action
     * @return BusinessUnitUpdateUserDatagridManager
     */
    protected function getBusinessUnitDatagridManager(BusinessUnit $businessUnit, $action)
    {
        /** @var $result BusinessUnitUpdateUserDatagridManager */
        $result = $this->get('oro_organization.business_unit_' . $action . '_user_datagrid_manager');
        $result->setBusinessUnit($businessUnit);
        $result->getRouteGenerator()->setRouteParameters(array('id' => $businessUnit->getId()));

        return $result;
    }

    /**
     * @param BusinessUnit $entity
     * @return array
     */
    protected function update(BusinessUnit $entity)
    {
        if ($this->get('oro_organization.form.handler.business_unit')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.business_unit.controller.message.saved')
            );

            return $this->get('oro_ui.router')->actionRedirect(
                array(
                    'route' => 'oro_business_unit_update',
                    'parameters' => array('id' => $entity->getId()),
                ),
                array(
                    'route' => 'oro_business_unit_view',
                    'parameters' => array('id' => $entity->getId())
                )
            );
        }

        return array(
            'datagrid' => $this->getBusinessUnitDatagridManager($entity, 'update')->getDatagrid()->createView(),
            'form'     => $this->get('oro_organization.form.business_unit')->createView(),
        );
    }
}
