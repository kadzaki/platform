<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\FlexibleEntityBundle\AttributeType\AbstractAttributeType;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;

use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Attribute;

class ObjectMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Oro\Bundle\SearchBundle\Engine\ObjectMapper
     */
    private $mapper;
    private $mappingConfig
        = array(
            'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Manufacturer' => array(
                'fields' => array(
                    array(
                        'name'            => 'products',
                        'relation_type'   => 'one-to-many',
                        'relation_fields' => array(
                            array(
                                'name'        => 'name',
                                'target_type' => 'text',
                            )
                        )
                    ),
                    array(
                        'name'            => 'parent',
                        'relation_type'   => 'one-to-many',
                        'relation_fields' => array(
                            array()
                        )
                    )
                )
            ),
            'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product'      => array(
                'alias'            => 'test_product',
                'label'            => 'test product',
                'title_fields'     => array('name'),
                'route'            => array(
                    'name'       => 'test_route',
                    'parameters' => array(
                        'id' => 'id'
                    )
                ),
                'fields'           => array(
                    array(
                        'name'          => 'name',
                        'target_type'   => 'text',
                        'target_fields' => array(
                            'name',
                            'all_data'
                        )
                    ),
                    array(
                        'name'          => 'description',
                        'target_type'   => 'text',
                        'target_fields' => array(
                            'description',
                            'all_data'
                        )
                    ),
                    array(
                        'name'          => 'price',
                        'target_type'   => 'decimal',
                        'target_fields' => array('price')
                    ),
                    array(
                        'name'        => 'count',
                        'target_type' => 'integer',
                    ),
                    array(
                        'name'            => 'manufacturer',
                        'relation_type'   => 'one-to-one',
                        'relation_fields' => array(
                            array(
                                'name'          => 'name',
                                'target_type'   => 'text',
                                'target_fields' => array(
                                    'manufacturer',
                                    'all_data'
                                )
                            )
                        )
                    ),
                ),
                'flexible_manager' => 'test_manager'
            )
        );

    public function setUp()
    {
        $this->container = $this->getMockForAbstractClass('Symfony\Component\DependencyInjection\ContainerInterface');

        $manufacturer = new Manufacturer();
        $manufacturer->setName('adidas');

        $this->product = new Product();
        $this->product->setName('test product')
            ->setCount(10)
            ->setPrice(150)
            ->setManufacturer($manufacturer)
            ->setDescription('description')
            ->setCreateDate(new \DateTime());

        $this->flexibleManager = $this
            ->getMockBuilder('Oro\Bundle\FlexibleEntityBundle\Manager\FlexibleManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->flexibleManager->expects($this->any())
            ->method('getAttributeRepository')
            ->will($this->returnValue($this->attributeRepository));

        $this->route = $this
            ->getMockBuilder('Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->route->expects($this->any())
            ->method('generate')
            ->will($this->returnValue('http://example.com'));
        $params = array(
            'test_manager' => $this->flexibleManager,
            'router'       => $this->route,
        );
        $this->container->expects($this->any())
            ->method('get')
            ->with(
                $this->logicalOr(
                    $this->equalTo('test_manager'),
                    $this->equalTo('router')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($param) use (&$params) {
                        return $params[$param];
                    }
                )
            );

        $this->mapper = new ObjectMapper($this->container, $this->mappingConfig);
    }

    public function testMapObject()
    {
        $testTextAttribute = new Attribute();
        $testTextAttribute->setCode('text_attribute')
            ->setBackendType(AbstractAttributeType::BACKEND_TYPE_TEXT);

        $testIntegerAttribute = new Attribute();
        $testIntegerAttribute->setCode('integer_attribute')
            ->setBackendType(AbstractAttributeType::BACKEND_TYPE_INTEGER);

        $testDatetimeAttribute = new Attribute();
        $testDatetimeAttribute->setCode('datetime_attribute')
            ->setBackendType(AbstractAttributeType::BACKEND_TYPE_DATETIME);

        $this->attributeRepository->expects($this->once())
            ->method('findBy')
            ->will(
                $this->returnValue(
                    array(
                         $testTextAttribute,
                         $testIntegerAttribute,
                         $testDatetimeAttribute
                    )
                )
            );

        $mapping = $this->mapper->mapObject($this->product);

        $this->assertEquals('test product ', $mapping['text']['name']);
        $this->assertEquals(150, $mapping['decimal']['price']);
        $this->assertEquals(10, $mapping['integer']['count']);
        $this->assertEquals(' text_attribute', $mapping['text']['text_attribute']);

        $manufacturer = new Manufacturer();
        $manufacturer->setName('reebok');
        $manufacturer->addProduct($this->product);
        $this->mapper->mapObject($manufacturer);
    }

    public function testGetEntitiesLabels()
    {
        $data = $this->mapper->getEntitiesLabels();

        $this->assertEquals('test_product', $data[1]['alias']);
        $this->assertEquals('Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product', $data[1]['class']);
    }

    public function testGetMappingConfig()
    {
        $mapping = $this->mappingConfig;

        $this->assertEquals($mapping, $this->mapper->getMappingConfig());
    }

    public function testGetEntityMapParameter()
    {
        $this->assertEquals(
            'test_product',
            $this->mapper->getEntityMapParameter(
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product',
                'alias'
            )
        );

        $this->assertEquals(
            false,
            $this->mapper->getEntityMapParameter(
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product',
                'non exists parameter'
            )
        );
    }

    public function testGetEntities()
    {
        $entities = $this->mapper->getEntities();
        $this->assertEquals('Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product', $entities[1]);
    }

    public function testNonExistsConfig()
    {
        $this->assertEquals(false, $this->mapper->getEntityConfig('non exists entity'));
    }
}
