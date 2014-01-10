<?php

namespace Trello\Model;

class Card extends Object {

    protected $_model = 'cards';

    /**
     * Arguments
        name (required)
        Valid Values: a string with a length from 1 to 16384

        desc (optional)
        Valid Values: a string with a length from 0 to 16384

        pos (optional)
        Default: bottom
        Valid Values: A position. top, bottom, or a positive number.

        due (required)
        Valid Values: A date, or null

        labels (optional)

        idList (required)
        Valid Values: id of the list that the card should be added to

        idMembers (optional)
        Valid Values: A comma-separated list of objectIds, 24-character hex strings

        idCardSource (optional)
        Valid Values: The id of the card to copy into a new card.

        keepFromSource (optional)
        Default: all
        Valid Values: Properties of the card to copy over from the source.
     * @see \Trello\Model\Object::save()
     */
    public function save(){

        if (empty($this->name)){
            throw new \InvalidArgumentException('Missing required field "name"');
        }

        if (empty($this->idList)){
            throw new \InvalidArgumentException('Missing required filed "idList" - id of the list that the card should be added to');
        }

        if (empty($this->pos)){
            $this->pos = 'bottom';
        }else{
            if ($this->pos !== 'top' && $this->pos !== 'bottom' && $this->pos <= 0){
                throw new \InvalidArgumentException("Invalid pos value {$this->pos}. Valid Values: A position. top, bottom, or a positive number");
            }
        }

        if (empty($this->due)){
            $this->due = null;
        }

        return parent::save();

    }

    public function copy($new_name = null, $new_list_id = null, array $copy_fields = array()){

        if ($this->getId()){

            $tmp = new self($this->getClient());
            if (!$new_name){
                $tmp->name = $this->name . ' Copy';
            }else{
                $tmp->name = $new_name;
            }

            if (!$new_list_id){
                $tmp->idList = $this->idList;
            }else{
                $tmp->idList = $new_list_id;
            }

            $tmp->idCardSource = $this->getId();

            if (!empty($copy_fields)){
                $tmp->keepFromSource = implode(',', $copy_fields);
            }

            return $tmp->save();

        }

        return false;

    }

}