<?php

class JsonState extends JsonSelect
{

    public function validate(array $postData)
    {
        $states = statesList();

        if (in_array($postData[$this->type], $states))
            return true;

        return false;
    }
}