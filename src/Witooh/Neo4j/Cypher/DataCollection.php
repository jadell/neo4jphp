<?php
namespace Witooh\Neo4j\Cypher;

class DataCollection implements \Iterator, \Countable, \ArrayAccess {
    /**
     * @var array
     */
    protected $data;
    /**
     * @var array
     */
    protected $columns;
    /**
     * @var int
     */
    protected $pos = 0;

    public function __construct(array $columns, array $data)
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->rows = [];
        $this->columnName = null;
    }

    /**
     * @return mixed|void
     */
    public function current()
    {
        return $this[$this->pos];
    }


    public function next()
    {
        ++$this->pos;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->pos;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->data[$this->pos]);
    }

    public function rewind()
    {
        $this->pos = 0;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (!isset($this->rows[$offset])) {
            $this->rows[$offset] = new Data($this->columns, $this->data[$offset]);
        }
        return $this->rows[$offset];
    }



    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException("You cannot modify a query result.");
    }

    /**
     * @param mixed $offset
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException("You cannot modify a query result.");
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }


} 