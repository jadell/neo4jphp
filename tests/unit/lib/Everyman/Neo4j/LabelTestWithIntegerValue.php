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
                $label = new Label($this->client, 'foobar');
                $property = 'baz';
                $value = 1;

                $this->client->expects($this->once())
                        ->method('getNodesForLabel')
                        ->with($label, $property, $value);

                $label->getNodes($property, $value);
        }
}
