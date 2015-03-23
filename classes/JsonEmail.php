<?php

class JsonEmail extends JsonString
{

    public function validate(array $postData)
    {
        if (isset($this->attributes['multiple']) && $this->attributes['multiple'] == 'true') {
            $emails = str_getcsv($postData['emails']); // TODO: could be a dynamic select/insert box on front end and passed through as an array later.

            foreach ($emails as $email) {
                if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL))
                    return false;
            }

            return true;
        }
        elseif ($this->isRequiredCheck($postData)) {
            return filter_var($postData[$this->name], FILTER_VALIDATE_EMAIL);
        }

        return false;
    }
}