<?php

namespace Trello\Model;

class Lane extends Object {

    protected $_model = 'lists';

    public function save(){

        if (empty($this->name)){
            throw new \InvalidArgumentException('Missing required field "name"');
        }

        if (empty($this->idBoard)){
            throw new \InvalidArgumentException('Missing required filed "idBoard" - id of the board that the list should be added to');
        }

        if (empty($this->pos)){
            $this->pos = 'bottom';
        }else{
            if ($this->pos !== 'top' && $this->pos !== 'bototm' && $this->pos <= 0){
                throw new \InvalidArgumentException("Invalid pos value {$this->pos}. Valid Values: A position. top, bottom, or a positive number");
            }
        }

        return parent::save();

    }

    public function getCards(array $params = array()){

        $data = $this->getPath('cards', $params);

        $tmp = array();
        foreach ($data as $item){
            array_push($tmp, new \Trello\Model\Card($this->getClient(), $item));
        }

        return $tmp;

    }

}