<?php
    
namespace Trello\Model;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use Trello\Client;

abstract class BaseObject implements ArrayAccess, Countable, Iterator
{

    protected $_client;
    protected $_model;

    private $_position = 0;
    protected $_data;

    public function __construct(Client $client, array $data = [])
    {

        $this->_client = $client;
        $this->_data = $data;

    }

    /**
     * Save an object
     *
     * @return BaseObject
     */
    public function save(){

        if ($this->getId()){
            return $this->update();
        }

        $response = $this->getClient()->post($this->getModel() . '/' . $this->getId(), $this->toArray());

        $child = get_class($this);

        return new $child($this->getClient(), $response);

    }

    /**
     * @return mixed
     */
    public function update(){

        if (!$this->getId()){
            throw new InvalidArgumentException('There is no ID set for this object - Please call setId before calling update');
        }

        $response = $this->getClient()->put($this->getModel() . '/' . $this->getId(), $this->toArray());

        $child = get_class($this);

        return new $child($this->getClient(), $response);

    }

    /**
     * Get an item by id ($this->id)
     *
     * @return BaseObject
     * @throws InvalidArgumentException
     */
    public function get(){

        if (!$this->getId()){
            throw new InvalidArgumentException('There is no ID set for this object - Please call setId before calling get');
        }

        $child = get_class($this);
        $response = $this->getClient()->get($this->getModel() . '/' . $this->getId());

        return new $child($this->getClient(), $response);

    }

    /**
     * Get relative data
     *
     * @param string $path
     * @param array $payload
     *
     * @return array
     */
    public function getPath($path, array $payload = []): array
    {

        return $this->getClient()->get($this->getModel() . '/' . $this->getId() . '/' . $path, $payload);

    }

    /**
     * @return mixed
     */
    public function getModel(){

        return $this->_model;

    }

    /**
     *
     * @return Client
     */
    public function getClient(){

        return $this->_client;

    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id){

        $this->id = $id;

        return $this;

    }

    public function getId(){

        return $this->id;

    }

    public function __get($key){

        return $this->offsetExists($key)? $this->offsetGet($key) : null;

    }

    public function __set($key, $value){

        $this->offsetSet($key, $value);

    }

    public function __isset($key){

        return $this->offsetExists($key);

    }

    public function __unset($key){

        return $this->offsetUnset($key);

    }

    public function toArray(){

        return $this->_data;

    }

    public function offsetSet($offset, $value){

        if ($offset === null) {
            $this->_data[] = $value;
        }else{
            $this->_data[$offset] = $value;
        }

    }

    public function offsetExists($offset){

        return isset($this->_data[$offset]);

    }

    public function offsetUnset($offset){

        unset($this->_data[$offset]);

    }

    public function offsetGet($offset){

        return $this->_data[$offset] ?? null;

    }

    public function count(){

        return count($this->_data);

    }

    public function rewind(){

        $this->_position = 0;

    }

    public function current(){

        return $this->_data[$this->_position];

    }

    public function key(){

        return $this->_position;

    }

    public function next(){

        ++$this->_position;

    }

    public function valid(){

        return isset($this->_data[$this->_position]);

    }

}