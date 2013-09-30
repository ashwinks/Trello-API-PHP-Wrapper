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
	    
	    
	}