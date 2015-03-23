<?php

class JsonSelect extends JsonParam
{


    public function validate(array $postData)
    {
        if (!empty($this->values) && is_array($this->values))
            return true;

        return false;
    }

    public function insertElements(\DOMDocument &$dom, \DOMElement &$form)
    {
        $input = $form->appendChild(new \DOMElement('select'));
        $input->setAttribute('name', $this->name);
        $this->attachAttributes($input);

        foreach ($this->values as $value) {
            $option = $input->appendChild(new \DOMElement('option'));
            $option->appendChild($dom->createTextNode($value));
            $option->setAttribute('value', $value);
        }
    }
}