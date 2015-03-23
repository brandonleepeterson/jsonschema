<?php

class JsonEmail extends JsonString
{

    public function validate(array $postData)
    {
        if ($this->isRequiredCheck($postData)) {
            return filter_var($postData[$this->name], FILTER_VALIDATE_EMAIL);
        }

        return false;
    }
}