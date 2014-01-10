<?php

namespace Trello\Model;

class Board extends Object {

    protected $_model = 'boards';

    public function getCards(array $params = array()){

        $data = $this->getPath('cards', $params);

        $tmp = array();
        foreach ($data as $item){
            array_push($tmp, new \Trello\Model\Card($this->getClient(), $item));
        }

        return $tmp;

    }

    public function getCard($card_id, array $params = array()){

        $data = $this->getPath("cards/{$card_id}", $params);

        return new \Trello\Model\Card($this->getClient(), $data);

    }

    public function getActions(array $params = array()){

        $data = $this->getPath('actions', $params);

        $tmp = array();
        foreach ($data as $item){
            array_push($tmp, new \Trello\Model\Action($this->getClient(), $item));
        }

        return $tmp;

    }

    public function getLists(array $params = array()){

        $data = $this->getPath('lists', $params);

        $tmp = array();
        foreach ($data as $item){
            array_push($tmp, new \Trello\Model\Lane($this->getClient(), $item));
        }

        return $tmp;

    }

    public function copy($new_name = null, array $copy_fields = array()){

        if ($this->getId()){

            $tmp = new self($this->getClient());
            if (!$new_name){
                $tmp->name = $this->name . ' Copy';
            }else{
                $tmp->name = $new_name;
            }
            $tmp->idBoardSource = $this->getId();

            if (!empty($copy_fields)){
                $tmp->keepFromSource = implode(',', $copy_fields);
            }

            return $tmp->save();

        }

        return false;

    }

}