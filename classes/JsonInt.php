<?php

class JsonInt extends JsonParam
{

    public function validate(array $postData)
    {
        if ($this->isRequiredCheck($postData)) {
            return is_numeric($postData[$this->name]); // TODO: better type checking / should we cast?
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