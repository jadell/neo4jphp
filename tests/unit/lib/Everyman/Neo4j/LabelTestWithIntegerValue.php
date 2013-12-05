<?php
namespace Everyman\Neo4j;

class LabelTestWithIntegerValue extends \PHPUnit_Framework_TestCase
{
        protected $client = null;

        public function setUp()
        {
                $this->client = $this->getMock('Everyman\Neo4j\Client');
        }

        public function testGetNodes_PropertyWithIntegerValueGiven_CallsClientMethod()
        {
                $labelName = 'FOOBAR';
                $propertyName = 'baz';
                $propertyValue = 1;
                $label = new Label($this->client, $labelName);

                $returnData = array(
                        array(
                                "self" => "http://localhost:7474/db/data/relationship/56",
                                "data" => array($propertyName => $propertyValue),
                        ),
                );

                $this->transport->expects($this->once())
                        ->method('get')
                        ->with("/label/{$labelName}/nodes?{$propertyName}={$propertyValue}")
                        ->will($this->returnValue(array('code'=>200,'data'=>$returnData)));

                $nodes = $this->client->getNodesForLabel($label, $propertyName, $propertyValue);
                self::assertInstanceOf('Everyman\Neo4j\Query\Row', $nodes);
                self::assertEquals(1, count($nodes));

                self::assertInstanceOf('Everyman\Neo4j\Node', $nodes[0]);
                self::assertEquals(56,  $nodes[0]->getId());
        }
}
