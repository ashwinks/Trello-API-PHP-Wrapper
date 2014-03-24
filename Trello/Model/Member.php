<?php

namespace Trello\Model;

class Member extends Object {

    protected $_model = 'members';

    public function getBoards(){

        $data = $this->getPath('boards');

        $tmp = array();
        foreach ($data as $item){
            array_push($tmp, new \Trello\Model\Board($this->getClient(), $item));
        }

        return $tmp;

    }

    public function getOrganizations(){

        $data = $this->getPath('organizations');

        $tmp = array();
        foreach ($data as $item){
            array_push($tmp, new \Trello\Model\Organization($this->getClient(), $item));
        }

        return $tmp;

    }

}