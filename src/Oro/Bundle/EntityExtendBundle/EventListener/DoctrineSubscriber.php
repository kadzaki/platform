<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class DoctrineSubscriber implements EventSubscriber
{
    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata'
        );
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        /** @var OroEntityManager $em */
        $em = $event->getEntityManager();

        $configProvider = $em->getExtendManager()->getConfigProvider();
        $className      = $event->getClassMetadata()->getName();

        if ($configProvider->hasConfig($className)) {
            $config = $configProvider->getConfig($className);
            if ($config->is('is_extend') && $config->is('index')) {
                $index = isset($event->getClassMetadata()->table['indexes'])
                    ? $event->getClassMetadata()->table['indexes']
                    : array();

                foreach ($config->get('index') as $columnName => $enabled) {
                    $fieldConfig = $configProvider->getConfig($className, $columnName);

                    if ($enabled && $fieldConfig->is('state', ExtendManager::STATE_ACTIVE)) {
                        $indexName = 'oro_idx_' . $columnName;

                        $index[$indexName] = array('columns' => array(ExtendConfigDumper::PREFIX . $columnName));
                    }
                }

                $event->getClassMetadata()->table['indexes'] = $index;
            }
        }
    }
}
