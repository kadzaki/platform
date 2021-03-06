<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Datagrid\EntityFieldsDatagridManager;
use Oro\Bundle\EntityConfigBundle\Datagrid\ConfigDatagridManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * EntityConfig controller.
 * @Route("/entity/config")
 * TODO: Discuss ACL impl., currently management of configurable entities can be on or off only
 * @Acl(
 *      id="oro_entityconfig_manage",
 *      label="Manage configurable entities",
 *      type="action",
 *      group_name=""
 * )
 */
class ConfigController extends Controller
{

    /**
     * Lists all Flexible entities.
     * @Route("/", name="oro_entityconfig_index")
     * Acl(
     *      id="oro_entityconfig",
     *      label="View configurable entities",
     *      type="action",
     *      group_name=""
     * )
     * @Template()
     */
    public function indexAction(Request $request)
    {
        /** @var  ConfigDatagridManager $datagrid */
        $datagridManager = $this->get('oro_entity_config.datagrid.manager');
        $datagrid        = $datagridManager->getDatagrid();

        /**
         * Set 50 records per page by default for DataGrid
         */
        $datagrid->getPager()->setMaxPerPage(50);
        $datagrid->getPager()->init();

        $view            = 'json' == $request->getRequestFormat()
            ? 'OroGridBundle:Datagrid:list.json.php'
            : 'OroEntityConfigBundle:Config:index.html.twig';

        return $this->render(
            $view,
            array(
                'buttonConfig' => $datagridManager->getLayoutActions(),
                'require_js'   => $datagridManager->getRequireJsModules(),
                'datagrid'     => $datagrid->createView(),
            )
        );
    }

    /**
     * @Route("/update/{id}", name="oro_entityconfig_update")
     * Acl(
     *      id="oro_entityconfig_update",
     *      label="Update configurable entity",
     *      type="action",
     *      group_name=""
     * )
     * @Template()
     */
    public function updateAction($id)
    {
        $entity  = $this->getDoctrine()->getRepository(EntityConfigModel::ENTITY_NAME)->find($id);
        $request = $this->getRequest();

        $form = $this->createForm(
            'oro_entity_config_type',
            null,
            array(
                'config_model' => $entity,
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                //persist data inside the form
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity_config.controller.config_entity.message.saved')
                );

                return $this->get('oro_ui.router')->actionRedirect(
                    array(
                        'route'      => 'oro_entityconfig_update',
                        'parameters' => array('id' => $id),
                    ),
                    array(
                        'route'      => 'oro_entityconfig_view',
                        'parameters' => array('id' => $id)
                    )
                );
            }
        }

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        return array(
            'entity'        => $entity,
            'entity_config' => $entityConfigProvider->getConfig($entity->getClassName()),
            'form'          => $form->createView(),
        );
    }

    /**
     * View Entity
     * @Route("/view/{id}", name="oro_entityconfig_view")
     * Acl(
     *      id="oro_entityconfig_view",
     *      label="View configurable entity",
     *      type="action",
     *      group_name=""
     * )
     * @Template()
     */
    public function viewAction(EntityConfigModel $entity)
    {
        /** @var  EntityFieldsDatagridManager $datagridManager */
        $datagridManager = $this->get('oro_entity_config.entityfieldsdatagrid.manager');
        $datagridManager->setEntityId($entity->getId());
        $datagridManager->getRouteGenerator()->setRouteParameters(
            array(
                'id' => $entity->getId()
            )
        );

        $datagrid = $datagridManager->getDatagrid();

        /**
         * define Entity module and name
         */
        $entityName = $moduleName = '';
        $className  = explode('\\', $entity->getClassName());
        if (count($className) > 1) {
            foreach ($className as $i => $name) {
                if (count($className) - 1 == $i) {
                    $entityName = $name;
                } elseif (!in_array($name, array('Bundle', 'Entity'))) {
                    $moduleName .= $name;
                }
            }
        } else {
            $entityName = $className[0];
            $moduleName = 'Custom';
        }

        /** @var \Oro\Bundle\EntityConfigBundle\Config\ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');

        // generate link for Entity grid
        $link = '';
        /** @var EntityMetadata $metadata */
        if (class_exists($entity->getClassName())) {
            $metadata = $configManager->getEntityMetadata($entity->getClassName());

            if ($metadata && $metadata->routeName) {
                $link = $this->generateUrl($metadata->routeName);
            }
        }

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $this->get('oro_entity_config.provider.extend');
        $extendConfig         = $extendConfigProvider->getConfig($entity->getClassName());

        /** @var ConfigProvider $ownershipConfigProvider */
        $ownershipConfigProvider = $this->get('oro_entity_config.provider.ownership');


        if (class_exists($entity->getClassName())) {
            /** @var QueryBuilder $qb */
            $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
            $qb->select('count(entity)');
            $qb->from($entity->getClassName(), 'entity');
            $entityCount = $qb->getQuery()->getSingleScalarResult();
        } else {
            $entityCount = 0;
        }

        /**
         * Set 50 records per page by default for DataGrid
         */
        $datagrid->getPager()->setMaxPerPage(50);
        $datagrid->getPager()->init();

        return array(
            'entity'           => $entity,
            'entity_config'    => $entityConfigProvider->getConfig($entity->getClassName()),
            'entity_extend'    => $extendConfig,
            'entity_count'     => $entityCount,
            'entity_fields'    => $datagrid->createView(),
            'entity_ownership' => $ownershipConfigProvider->getConfig($entity->getClassName()),
            'unique_key'       => $extendConfig->get('unique_key'),
            'link'             => $link,
            'entity_name'      => $entityName,
            'module_name'      => $moduleName,
            'button_config'    => $datagridManager->getLayoutActions($entity),
            'require_js'       => $datagridManager->getRequireJsModules(),
        );
    }

    /**
     * Lists Entity fields
     * @Route("/fields/{id}", name="oro_entityconfig_fields", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template()
     */
    public function fieldsAction($id, Request $request)
    {
        $entity = $this->getDoctrine()->getRepository(EntityConfigModel::ENTITY_NAME)->find($id);

        /** @var  EntityFieldsDatagridManager $datagridManager */
        $datagridManager = $this->get('oro_entity_config.entityfieldsdatagrid.manager');
        $datagridManager->setEntityId($id);

        $datagrid = $datagridManager->getDatagrid();

        $datagridManager->getRouteGenerator()->setRouteParameters(
            array(
                'id' => $id
            )
        );

        $view = 'json' == $request->getRequestFormat()
            ? 'OroGridBundle:Datagrid:list.json.php'
            : 'OroEntityConfigBundle:Config:fields.html.twig';

        return $this->render(
            $view,
            array(
                'buttonConfig' => $datagridManager->getLayoutActions($entity),
                'datagrid'     => $datagrid->createView(),
                'entity_id'    => $id,
                'entity_name'  => $entity->getClassName(),
                'require_js'   => $datagridManager->getRequireJsModules(),
            )
        );
    }

    /**
     * @Route("/field/update/{id}", name="oro_entityconfig_field_update")
     * Acl(
     *      id="oro_entityconfig_field_update",
     *      label="Update configurable entity field",
     *      type="action",
     *      group_name=""
     * )
     * @Template()
     */
    public function fieldUpdateAction($id)
    {
        /** @var FieldConfigModel $field */
        $field   = $this->getDoctrine()->getRepository(FieldConfigModel::ENTITY_NAME)->find($id);
        $request = $this->getRequest();

        $form = $this->createForm(
            'oro_entity_config_type',
            null,
            array(
                'config_model' => $field,
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                //persist data inside the form
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity_config.controller.config_field.message.saved')
                );

                return $this->get('oro_ui.router')->actionRedirect(
                    array(
                        'route'      => 'oro_entityconfig_field_update',
                        'parameters' => array('id' => $id),
                    ),
                    array(
                        'route'      => 'oro_entityconfig_view',
                        'parameters' => array('id' => $field->getEntity()->getId())
                    )
                );
            }
        }

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        /** @var ConfigProvider $entityExtendProvider */
        $entityExtendProvider = $this->get('oro_entity_config.provider.extend');

        $entityConfig         = $entityConfigProvider->getConfig($field->getEntity()->getClassName());
        $fieldConfig          = $entityConfigProvider->getConfig(
            $field->getEntity()->getClassName(),
            $field->getFieldName()
        );

        return array(
            'entity_config' => $entityConfig,
            'field_config'  => $fieldConfig,
            'field'         => $field,
            'form'          => $form->createView(),
            'formAction'    => $this->generateUrl('oro_entityconfig_field_update', array('id' => $field->getId())),
            'require_js'    => $entityExtendProvider->getPropertyConfig()->getRequireJsModules()
        );
    }

    /**
     * @Route("/field/search/{id}", name="oro_entityconfig_field_search", defaults={"id"=0})
     * Acl(
     *      id="oro_entityconfig_field_search",
     *      label="Return varchar type field(s) in given entity",
     *      type="action",
     *      group_name=""
     * )
     */
    public function fieldSearchAction($id)
    {
        $fields = array();
        if ($id) {
            $id = str_replace('_', '\\', $id);

            /** @var EntityConfigModel $entity */
            $entity = $this->getDoctrine()->getRepository(EntityConfigModel::ENTITY_NAME)
                ->findOneBy(array('className' => $id));

            if ($entity) {
                /** @var ConfigProvider $entityConfigProvider */
                $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

                /** @var FieldConfigModel $fields */
                $entityFields = $this->getDoctrine()->getRepository(FieldConfigModel::ENTITY_NAME)
                    ->findBy(
                        array(
                            'entity' => $entity->getId(),
                            'type'   => 'string'
                        ),
                        array('fieldName' => 'ASC')
                    );

                foreach ($entityFields as $field) {
                    $label = $entityConfigProvider->getConfig($id, $field->getFieldName())->get('label');
                    if (!$label) {
                        $label = $field->getFieldName();
                    }

                    $fields[$field->getFieldName()] = $label;
                }
            }
        }

        return new Response(json_encode($fields));
    }
}
