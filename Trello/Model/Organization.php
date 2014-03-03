<?php

namespace Trello\Model;

class Organization extends Object {

    protected $_model = 'organizations';

    public function getBoards(array $params = array()){

        $data = $this->getPath('boards', $params);

        $tmp = array();
        foreach ($data as $item){
            array_push($tmp, new \Trello\Model\Board($this->getClient(), $item));
        }

        return $tmp;

    }

}