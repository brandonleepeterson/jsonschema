<?php

class JsonCheckbox extends JsonParam
{

    public function validate(array $postData)
    {
        foreach ($this->values as $name) {
            if (isset($postData[$name]) && $postData[$name] != 'on')
                return false;
        }

        return true;
    }

    public function insertElements(\DOMDocument &$dom, \DOMElement &$form)
    {
        if (!empty($this->values)) {
            foreach ($this->values as $value) {
                $input = $form->appendChild(new \DOMElement('input', $value));
                $this->attachAttributes($input);
                $input->setAttribute('type', 'checkbox');
                $input->setAttribute('name', $value);
                $span = $form->appendChild(new \DOMElement('span'));
                $span->appendChild($dom->createTextNode($value));
            }
        } else {
            $input = $form->appendChild(new \DOMElement('input'));
            $this->attachAttributes($input);
            $input->setAttribute('type', 'checkbox');
            $input->setAttribute('name', $this->name);
            $span = $form->appendChild(new \DOMElement('span'));
            $span->appendChild($dom->createTextNode($this->name));
        }
    }
}