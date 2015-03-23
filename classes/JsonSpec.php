<?php

class JsonSpec implements JsonSpecInterface
{

    private $params;
    private $title;
    private $desc;
    public $invalidParams = [];

    /**
     * @param array $specData
     * @throws JsonSpecException
     */
    public function __construct(array $specData)
    {
        // Validate schema and assign title / description.
        if (empty($specData['title'])) throw new JsonSpecException('Spec must have title.');
        if (empty($specData['desc'])) throw new JsonSpecException('Spec must have description.');
        if (empty($specData['params']) || !is_array($specData['params'])) throw new JsonSpecException('Spec must have valid parameters.');
        $this->title = $specData['title'];
        $this->desc = $specData['desc'];

        // Loop through parameters, creating jsonParam objects internally.
        foreach ($specData['params'] as $key => $param) {
            $class = "Json" . ucfirst($param['type']);

            $jsonParam = new $class($param);
            $this->params[] = $jsonParam;
        }
    }

    /**
     * @param array $postData
     * @return bool
     */
    public function validate(array $postData)
    {
        $valid = true;
        foreach ($this->params as $param) {
            if (!$param->validate($postData)) {
                $valid = false;
                $this->invalidParams[] = $param->name;
            }
        }

        return $valid;
    }

    /**
     * @return string
     */
    public function toForm()
    {
        $dom = new \DOMDocument('1.0');

        // Create form header title.
        $h2 = $dom->createElement('h2');
        $h2->appendChild($dom->createTextNode($this->title));
        $dom->appendChild($h2);

        // Create form description.
        $description = $dom->createElement('p');
        $description->appendChild($dom->createTextNode($this->desc));
        $dom->appendChild($description);

        // Create form and attributes.
        $form = $dom->createElement('form');
        $form->setAttribute('id', 'form-json');
        $form->setAttribute('action', 'jsonPost.php');
        $form->setAttribute('method', 'POST');
        $dom->appendChild($form);

        // For each parameter, add the new elements to the dom object.
        foreach ($this->params as $param) {
            $param->toForm($dom, $form); // TODO: figure out how to properly attach elements to form to get around this hack.
        }

        // Create submit button
        $submit = $form->appendChild(new \DOMElement('input'));
        $submit->setAttribute('type', 'submit');
        $submit->setAttribute('value', 'Submit');

        return $dom->saveHTML();
    }
}