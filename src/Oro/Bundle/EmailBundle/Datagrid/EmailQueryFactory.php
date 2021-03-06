<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use \Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Oro\Bundle\GridBundle\Datagrid\ORM\QueryFactory\EntityQueryFactory;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EmailQueryFactory extends EntityQueryFactory
{
    /**
     * A list of field names of all email owners
     *
     * @var string[]
     */
    protected $emailOwnerFieldNames = array();

    /**
     * @var string
     */
    protected $fromEmailExpression;

    /**
     * @param RegistryInterface $registry
     * @param string $className
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param UserConfigManager $userConfigManager
     */
    public function __construct(
        RegistryInterface $registry,
        $className,
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        UserConfigManager $userConfigManager
    ) {
        parent::__construct($registry, $className);
        for ($i = 1; $i <= count($emailOwnerProviderStorage->getProviders()); $i++) {
            $this->emailOwnerFieldNames[] = sprintf('owner%d', $i);
        }

        $firstNames = array();
        $lastNames = array();
        foreach ($this->emailOwnerFieldNames as $fieldName) {
            $firstNames[] = sprintf('%s.firstName', $fieldName);
            $lastNames[] = sprintf('%s.lastName', $fieldName);
        }
        $firstName = sprintf('COALESCE(%s, \'\')', implode(', ', $firstNames));
        $lastName = sprintf('COALESCE(%s, \'\')', implode(', ', $lastNames));
        $nameFormat = $userConfigManager->get('oro_locale.name_format');
        $this->fromEmailExpression = $this->buildFromEmailExpression($nameFormat, $firstName, $lastName);
    }

    protected function prepareQuery(QueryBuilder $qb)
    {
        $firstNames = array();
        $lastNames = array();
        foreach ($this->emailOwnerFieldNames as $fieldName) {
            $qb->leftJoin('a.' . $fieldName, $fieldName);
            $firstNames[] = sprintf('%s.firstName', $fieldName);
            $lastNames[] = sprintf('%s.lastName', $fieldName);
        }
    }

    /**
     * @return string
     */
    public function getFromEmailExpression()
    {
        return $this->fromEmailExpression;
    }

    /**
     * @param string $nameFormat
     * @param string $firstNameExpr
     * @param string $lastNameExpr
     * @return string
     */
    protected function buildFromEmailExpression($nameFormat, $firstNameExpr, $lastNameExpr)
    {
        $parts = array();
        $isFirst = true;
        $lastPos = -1;
        $pos = strpos($nameFormat, '%');
        while ($pos !== false) {
            if ($isFirst && $pos > $lastPos + 1) {
                $val = substr($nameFormat, $lastPos + 1, $pos - $lastPos - 1);
                $parts[] = sprintf('\'%s\'', str_replace('\'', '\\\'', $val));
            }
            $lastPos = $pos;
            $pos = strpos($nameFormat, '%', $pos + 1);
            if ($pos !== false) {
                if ($isFirst) {
                    $name = substr($nameFormat, $lastPos + 1, $pos - $lastPos - 1);
                    if ($name === 'first') {
                        $parts[] = $firstNameExpr;
                    } elseif ($name === 'last') {
                        $parts[] = $lastNameExpr;
                    }
                }
                $isFirst = !$isFirst;
            } elseif ($lastPos + 1 < strlen($nameFormat)) {
                $val = substr($nameFormat, $lastPos + 1);
                $parts[] = sprintf('\'%s\'', str_replace('\'', '\\\'', $val));
            }
        }

        $stack = array();
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            if (count($stack) === 0) {
                array_push($stack, $parts[$i]);
            } else {
                array_push($stack, sprintf('CONCAT(%s, %s)', $parts[$i], array_pop($stack)));
            }
        }

        if (empty($stack)) {
            return '';
        }

        return array_pop($stack);
    }
}
