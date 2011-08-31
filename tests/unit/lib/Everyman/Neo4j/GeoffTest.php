<?php
namespace Everyman\Neo4j;

class GeoffTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $geoff = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array(), array(), '', false);
		$this->geoff = new Geoff($this->client);

	}

	public function testLoad_IgnoreEmptyLines_ReturnsBatch()
	{
		$geoffString = "\n \n\t\n   	\n	\n";

		$batch = $this->geoff->loadString($geoffString);
		self::assertEquals(0, count($batch->getOperations()));
	}

	public function testLoad_IgnoreCommentLines_ReturnsBatch()
	{
		$geoffString = "#this is a comment\n"
					 . "	#so is this\n"
					 . "# this too   	\n";

		$batch = $this->geoff->loadString($geoffString);
		self::assertEquals(0, count($batch->getOperations()));
	}

	public function testLoad_LoadNodeLines_ReturnsBatch()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Phil)	{"name": "Philip", "title": "Duke of Edinburgh", "birth.date": "1921-06-21"}'.PHP_EOL
					 . '(Chaz)';

		$batch = $this->geoff->loadString($geoffString);
		$ops = $batch->getOperations();
		self::assertEquals(3, count($ops));

		self::assertInstanceOf('Everyman\Neo4j\Batch\Save', $ops[0]);
		self::assertInstanceOf('Everyman\Neo4j\Node', $ops[0]->getEntity());
		self::assertFalse($ops[0]->getEntity()->hasId());
		self::assertEquals('Elizabeth', $ops[0]->getEntity()->getProperty('name'));
		self::assertEquals('Queen of the Commonwealth Realms', $ops[0]->getEntity()->getProperty('title'));
		self::assertEquals('1926-04-21', $ops[0]->getEntity()->getProperty('birth.date'));

		self::assertInstanceOf('Everyman\Neo4j\Batch\Save', $ops[1]);
		self::assertInstanceOf('Everyman\Neo4j\Node', $ops[1]->getEntity());
		self::assertFalse($ops[1]->getEntity()->hasId());
		self::assertEquals('Philip', $ops[1]->getEntity()->getProperty('name'));
		self::assertEquals('Duke of Edinburgh', $ops[1]->getEntity()->getProperty('title'));
		self::assertEquals('1921-06-21', $ops[1]->getEntity()->getProperty('birth.date'));

		self::assertInstanceOf('Everyman\Neo4j\Batch\Save', $ops[2]);
		self::assertInstanceOf('Everyman\Neo4j\Node', $ops[2]->getEntity());
		self::assertFalse($ops[2]->getEntity()->hasId());
		self::assertEquals(array(), $ops[2]->getEntity()->getProperties());
	}

	public function testLoad_DuplicateNodeLines_ThrowsException()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL;

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->loadString($geoffString);
	}

	public function testLoad_RelationshipEndpointsDefined_ReturnsBatch()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Phil)	{"name": "Philip", "title": "Duke of Edinburgh", "birth.date": "1921-06-21"}'.PHP_EOL
					 . '(Chaz)	{"name": "Charles", "title": "Prince of Wales", "birth.date": "1948-11-14"}'.PHP_EOL
					 . '(Liz)-[:MARRIED]->(Phil)    {"marriage.place": "Westminster Abbey", "marriage.date": "1947-11-20"}'.PHP_EOL
					 . '(Phil)-[:FATHER_OF]->(Chaz)';
		
		$batch = $this->geoff->loadString($geoffString);
		$ops = $batch->getOperations();
		self::assertEquals(5, count($ops));

		$op = $ops[3];
		self::assertInstanceOf('Everyman\Neo4j\Batch\Save', $op);
		self::assertInstanceOf('Everyman\Neo4j\Relationship', $op->getEntity());
		self::assertFalse($op->getEntity()->hasId());
		self::assertSame($ops[0]->getEntity(), $op->getEntity()->getStartNode());
		self::assertSame($ops[1]->getEntity(), $op->getEntity()->getEndNode());
		self::assertEquals('MARRIED', $op->getEntity()->getType());
		self::assertEquals('Westminster Abbey', $op->getEntity()->getProperty('marriage.place'));
		self::assertEquals('1947-11-20', $op->getEntity()->getProperty('marriage.date'));

		$op = $ops[4];
		self::assertInstanceOf('Everyman\Neo4j\Batch\Save', $op);
		self::assertInstanceOf('Everyman\Neo4j\Relationship', $op->getEntity());
		self::assertFalse($op->getEntity()->hasId());
		self::assertSame($ops[1]->getEntity(), $op->getEntity()->getStartNode());
		self::assertSame($ops[2]->getEntity(), $op->getEntity()->getEndNode());
		self::assertEquals('FATHER_OF', $op->getEntity()->getType());
		self::assertEquals(array(), $op->getEntity()->getProperties());
	}

	public function testLoad_RelationshipUndefinedStart_ThrowsException()
	{
		$geoffString = '(Chaz)	{"name": "Charles", "title": "Prince of Wales", "birth.date": "1948-11-14"}'.PHP_EOL
					 . '(Phil)-[:FATHER_OF]->(Chaz)';
		
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->loadString($geoffString);
	}

	public function testLoad_RelationshipUndefinedEnd_ThrowsException()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Liz)-[:MARRIED]->(Phil)    {"marriage.place": "Westminster Abbey", "marriage.date": "1947-11-20"}';
		
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->loadString($geoffString);
	}
}

