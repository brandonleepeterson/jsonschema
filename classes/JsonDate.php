<?php

class JsonDate extends JsonParam
{

    public function validate(array $postData)
    {
        if ($this->isRequiredCheck($postData)) {
            return (bool)strtotime($postData[$this->name]);
        }

        return false;
    }

    public function insertElements(\DOMDocument &$dom, \DOMElement &$form)
    {
        $input = $form->appendChild(new \DOMElement('input'));
        $this->attachAttributes($input);
        $input->setAttribute('type', 'date');
        $input->setAttribute('name', $this->name);
        $input->setAttribute('placeholder', $this->name);
    }
}