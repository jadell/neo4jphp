<?php
namespace Everyman\Neo4j;

class GeoffTest extends \PHPUnit_Framework_TestCase
{
	protected $client = null;
	protected $geoff = null;

	public function setUp()
	{
		$this->client = $this->getMock('Everyman\Neo4j\Client', array('runCommand'));
		$this->geoff = new Geoff($this->client);

	}

	public function testLoad_NotAStreamOrString_ThrowsException()
	{
		$geoffString = 123;

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_IgnoreEmptyLines_ReturnsBatch()
	{
		$geoffString = "\n \n\t\n   	\n	\n";

		$batch = $this->geoff->load($geoffString);
		self::assertEquals(0, count($batch->getOperations()));
	}

	public function testLoad_IgnoreCommentLines_ReturnsBatch()
	{
		$geoffString = "#this is a comment\n"
					 . "	#so is this\n"
					 . "# this too   	\n";

		$batch = $this->geoff->load($geoffString);
		self::assertEquals(0, count($batch->getOperations()));
	}

	public function testLoad_LoadNodeLines_ReturnsBatch()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Phil)	{"name": "Philip", "title": "Duke of Edinburgh", "birth.date": "1921-06-21"}'.PHP_EOL
					 . '(Chaz)';

		$batch = $this->geoff->load($geoffString);
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
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_RelationshipEndpointsDefined_ReturnsBatch()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Phil)	{"name": "Philip", "title": "Duke of Edinburgh", "birth.date": "1921-06-21"}'.PHP_EOL
					 . '(Chaz)	{"name": "Charles", "title": "Prince of Wales", "birth.date": "1948-11-14"}'.PHP_EOL
					 . '(Liz)-[:MARRIED]->(Phil)    {"marriage.place": "Westminster Abbey", "marriage.date": "1947-11-20"}'.PHP_EOL
					 . '(Phil)-[:FATHER_OF]->(Chaz)';
		
		$batch = $this->geoff->load($geoffString);
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
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_RelationshipUndefinedEnd_ThrowsException()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Liz)-[:MARRIED]->(Phil)    {"marriage.place": "Westminster Abbey", "marriage.date": "1947-11-20"}';
		
		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_DuplicateRelationshipLines_ThrowsException()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Phil)	{"name": "Philip", "title": "Duke of Edinburgh", "birth.date": "1921-06-21"}'.PHP_EOL
					 . '(Liz)-[LizNPhil:MARRIED]->(Phil)'.PHP_EOL
					 . '(Liz)-[LizNPhil:MARRIED]->(Phil)';

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_IndexLines_ReturnsBatch()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Phil)	{"name": "Philip", "title": "Duke of Edinburgh", "birth.date": "1921-06-21"}'.PHP_EOL
					 . '(Liz)-[LizNPhil:MARRIED]->(Phil)    {"marriage.place": "Westminster Abbey", "marriage.date": "1947-11-20"}'.PHP_EOL
					 . '{People}->(Liz)     {"name": "Elizabeth"}'.PHP_EOL
					 . '{People}->(Phil)    {"name": "Philip", "title":"Duke"}'.PHP_EOL
					 . '{Marriages}->[LizNPhil]    {"wife": "Elizabeth", "husband": "Philip"}';

		$batch = $this->geoff->load($geoffString);
		$ops = $batch->getOperations();
		self::assertEquals(8, count($ops));

		$op = $ops[3];
		self::assertInstanceOf('Everyman\Neo4j\Batch\AddTo', $op);
		self::assertInstanceOf('Everyman\Neo4j\Node', $op->getEntity());
		self::assertEquals('Elizabeth', $op->getEntity()->getProperty('name'));
		self::assertInstanceOf('Everyman\Neo4j\Index', $op->getIndex());
		self::assertEquals('People', $op->getIndex()->getName());
		self::assertEquals(Index::TypeNode, $op->getIndex()->getType());
		self::assertEquals('name', $op->getKey());
		self::assertEquals('Elizabeth', $op->getValue());

		$op = $ops[4];
		self::assertInstanceOf('Everyman\Neo4j\Batch\AddTo', $op);
		self::assertInstanceOf('Everyman\Neo4j\Node', $op->getEntity());
		self::assertEquals('Philip', $op->getEntity()->getProperty('name'));
		self::assertInstanceOf('Everyman\Neo4j\Index', $op->getIndex());
		self::assertEquals('People', $op->getIndex()->getName());
		self::assertEquals(Index::TypeNode, $op->getIndex()->getType());
		self::assertEquals('name', $op->getKey());
		self::assertEquals('Philip', $op->getValue());

		$op = $ops[5];
		self::assertInstanceOf('Everyman\Neo4j\Batch\AddTo', $op);
		self::assertInstanceOf('Everyman\Neo4j\Node', $op->getEntity());
		self::assertEquals('Philip', $op->getEntity()->getProperty('name'));
		self::assertInstanceOf('Everyman\Neo4j\Index', $op->getIndex());
		self::assertEquals('People', $op->getIndex()->getName());
		self::assertEquals(Index::TypeNode, $op->getIndex()->getType());
		self::assertEquals('title', $op->getKey());
		self::assertEquals('Duke', $op->getValue());

		self::assertSame($ops[4]->getEntity(), $ops[5]->getEntity());

		$op = $ops[6];
		self::assertInstanceOf('Everyman\Neo4j\Batch\AddTo', $op);
		self::assertInstanceOf('Everyman\Neo4j\Relationship', $op->getEntity());
		self::assertSame($ops[2]->getEntity(), $op->getEntity());
		self::assertInstanceOf('Everyman\Neo4j\Index', $op->getIndex());
		self::assertEquals('Marriages', $op->getIndex()->getName());
		self::assertEquals(Index::TypeRelationship, $op->getIndex()->getType());
		self::assertEquals('wife', $op->getKey());
		self::assertEquals('Elizabeth', $op->getValue());

		$op = $ops[7];
		self::assertInstanceOf('Everyman\Neo4j\Batch\AddTo', $op);
		self::assertInstanceOf('Everyman\Neo4j\Relationship', $op->getEntity());
		self::assertSame($ops[2]->getEntity(), $op->getEntity());
		self::assertInstanceOf('Everyman\Neo4j\Index', $op->getIndex());
		self::assertEquals('Marriages', $op->getIndex()->getName());
		self::assertEquals(Index::TypeRelationship, $op->getIndex()->getType());
		self::assertEquals('husband', $op->getKey());
		self::assertEquals('Philip', $op->getValue());
	}

	public function testLoad_IndexLinesInvalidNode_ThrowsException()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '{People}->(Phil)    {"name": "Philip", "title":"Duke"}';

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_IndexLinesInvalidRelationship_ThrowsException()
	{
		$geoffString = '{Marriages}->[LizNPhil]    {"wife": "Elizabeth", "husband": "Philip"}';

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_IndexBracketMismatch_ThrowsException()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Phil)	{"name": "Philip", "title": "Duke of Edinburgh", "birth.date": "1921-06-21"}'.PHP_EOL
					 . '(Liz)-[LizNPhil:MARRIED]->(Phil)    {"marriage.place": "Westminster Abbey", "marriage.date": "1947-11-20"}'.PHP_EOL
					 . '{Marriages}->[LizNPhil)    {"wife": "Elizabeth", "husband": "Philip"}';

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_InvalidLine_ThrowsException()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . 'this line is total gibberish'.PHP_EOL
					 . '{People}->(Liz)    {"name": "Elizabeth"}';

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->load($geoffString);
	}

	public function testLoad_UseTheSameBatch_ReturnsBatch()
	{
		$geoffString = '(Liz)	{"name": "Elizabeth", "title": "Queen of the Commonwealth Realms", "birth.date": "1926-04-21"}'.PHP_EOL
					 . '(Phil)	{"name": "Philip", "title": "Duke of Edinburgh", "birth.date": "1921-06-21"}'.PHP_EOL
					 . '(Chaz)';

		$initBatch = new Batch($this->client);

		$batch = $this->geoff->load($geoffString, $initBatch);
		self::assertSame($initBatch, $batch);
		$ops = $batch->getOperations();
		self::assertEquals(3, count($ops));

		$batch2 = $this->geoff->load($geoffString, $batch);
		self::assertSame($batch, $batch2);
		$ops = $batch->getOperations();
		self::assertEquals(6, count($ops));
	}

	public function testDump_PathsGiven_NoFileDescriptor_ReturnsString()
	{
		$nodeA = new Node($this->client);
		$nodeA->setId(123)->setProperties(array('foo' => 'bar','baz' => 'qux'));
		
		$nodeB = new Node($this->client);
		$nodeB->setId(456)->setProperties(array('somekey' => 'somevalue'));
		
		$nodeC = new Node($this->client);
		$nodeC->setId(789);

		$relA = new Relationship($this->client);
		$relA->setId(987)->setType('TEST')
			->setStartNode($nodeA)->setEndNode($nodeB)
			->setProperties(array('anotherkey' => 'anothervalue'));

		$relB = new Relationship($this->client);
		$relB->setId(654)->setType('TSET')
			->setStartNode($nodeB)->setEndNode($nodeC);

		$path = new Path();
		$path->appendNode($nodeA);
		$path->appendNode($nodeB);
		$path->appendNode($nodeC);
		$path->appendRelationship($relA);
		$path->appendRelationship($relB);

		$expected =<<<GEOFF
(123)	{"foo":"bar","baz":"qux"}
(456)	{"somekey":"somevalue"}
(789)
(123)-[987:TEST]->(456)	{"anotherkey":"anothervalue"}
(456)-[654:TSET]->(789)

GEOFF;

		$result = $this->geoff->dump($path);
		self::assertEquals($expected, $result);
	}

	public function testDump_PathsGiven_FileDescriptor_ReturnsDescriptor()
	{
		$nodeA = new Node($this->client);
		$nodeA->setId(123)->setProperties(array('foo' => 'bar','baz' => 'qux'));
		$nodeB = new Node($this->client);
		$nodeB->setId(456)->setProperties(array('somekey' => 'somevalue'));
		$relA = new Relationship($this->client);
		$relA->setId(987)->setType('TEST')
			->setStartNode($nodeA)->setEndNode($nodeB)
			->setProperties(array('anotherkey' => 'anothervalue'));
		$path = new Path();
		$path->appendNode($nodeA);
		$path->appendNode($nodeB);
		$path->appendRelationship($relA);

		$expected =<<<GEOFF
(123)	{"foo":"bar","baz":"qux"}
(456)	{"somekey":"somevalue"}
(123)-[987:TEST]->(456)	{"anotherkey":"anothervalue"}

GEOFF;

		$handle = fopen('data:text/plain,', 'w+');
		$resultHandle = $this->geoff->dump($path, $handle);
		self::assertSame($handle, $resultHandle);
		self::assertEquals($expected, stream_get_contents($resultHandle, -1, 0));
	}

	public function testDump_NotAStreamOrString_ThrowsException()
	{
		$handle = 123;
		$path = new Path();

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->dump($path, $handle);
	}

	public function testDump_NotAPath_ThrowsException()
	{
		$handle = "file";
		$notPath = "blah";

		$this->setExpectedException('Everyman\Neo4j\Exception');
		$batch = $this->geoff->dump($notPath);
	}
}

