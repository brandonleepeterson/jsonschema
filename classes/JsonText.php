<?php

class JsonText extends JsonString
{

    public function insertElements(\DOMDocument &$dom, \DOMElement &$form)
    {
        $input = $form->appendChild(new \DOMElement('textarea'));
        $this->attachAttributes($input);
        $input->setAttribute('name', $this->name);
    }
}