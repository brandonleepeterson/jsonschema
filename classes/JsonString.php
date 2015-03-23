<?php

class JsonString extends JsonParam
{

    public function validate(array $postData)
    {
        if ($this->isRequiredCheck($postData)) {
            return is_string($postData[$this->name]);
        }

        return false;
    }

    public function insertElements(\DOMDocument &$dom, \DOMElement &$form)
    {
        $input = $form->appendChild(new \DOMElement('input'));
        $this->attachAttributes($input);
        $input->setAttribute('type', 'text');
        $input->setAttribute('name', $this->name);
        $input->setAttribute('placeholder', $this->name);
    }
}