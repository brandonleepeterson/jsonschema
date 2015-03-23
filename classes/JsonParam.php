<?php

abstract class JsonParam implements JsonParamInterface
{

    public $name;
    public $type;
    public $label;
    public $values = [];
    public $attributes = [];

    private $reservedKeywords = ['name', 'type', 'label', 'values'];
    private $paramKeywordToAttribute = [
        'minvalue' => 'min',
        'maxvalue' => 'max',
    ];

    /**
     * @param array $param
     * @throws JsonSpecException
     */
    public function __construct(array $param)
    {
        // Validate parameters and assign name / type.
        if (empty($param['name'])) throw new JsonSpecException('Parameter must have a name.');
        if (empty($param['type'])) throw new JsonSpecException('Parameter must have a type.');
        $this->name = $param['name'];
        $this->type = $param['type'];

        // Optional parameters and attributes.
        if (isset($param['label']))
            $this->label = $param['label'];
        if (isset($param['values']) && is_array($param['values']))
            $this->values = $param['values'];
        elseif ($this->type == 'state') // Populate state type with values from states function.
            $this->values = statesList();

        // Attributes are the keys of the array minus a few reserved keywords. Strtolower for consistency.
        $this->attributes = array_diff_key($param, array_flip($this->reservedKeywords));
        $this->attributes = array_change_key_case($this->attributes);

        // Convert certain param keywords to html attributes
        foreach ($this->attributes as $key => $value) {
            if (isset($this->paramKeywordToAttribute[$key])) {
                $this->attributes[$this->paramKeywordToAttribute[$key]] = $value;
                unset($this->attributes[$key]);
            }
        }
    }

    /**
     * @param \DOMDocument $dom
     * @param \DOMElement $form
     * @throws JsonSpecException
     */
    public function toForm(\DOMDocument &$dom, \DOMElement &$form)
    {
        // Create an optional label.
        if (isset($this->label)) {
            $label = $form->appendChild(new \DOMElement('label'));
            $label->appendChild($dom->createTextNode($this->label));
        }

        $this->insertElements($dom, $form);
    }

    /**
     * Base validate class that must be called.
     *
     * @param array $postData
     * @return bool
     */
    public function _validate(array $postData)
    {
        if (!$this->validateAttributes($postData))
            return false;

        return $this->validate($postData);
    }

    /**
     * Validate certain html attributes server side.
     *
     * @param array $postData
     * @return bool
     */
    protected function validateAttributes(array $postData)
    {
        // Empty parameters with an optional attribute will not be validated here.
        if (empty($postData[$this->name]) && isset($this->attributes['optional']) && $this->attributes['optional'] == true)
            return true;

        $attributeValidators = [
            'maxlength' => function($data, $attributeValue) {
                return strlen($data) <= $attributeValue;
            },
            'minlength' => function($data, $attributeValue) {
                return strlen($data) >= $attributeValue;
            },
            'min' => function($data, $attributeValue) {
                return (int)$data >= $attributeValue;
            },
            'max' => function($data, $attributeValue) {
                return (int)$data <= $attributeValue;
            },
        ];

        foreach ($this->attributes as $attributeName => $attributeValue) {
            if (isset($attributeValidators[strtolower($attributeName)])) {
                // TODO: would be nice to capture invalid attribute instead of returning false on first failure.
                if (!$attributeValidators[$attributeName]($postData[$this->name], $attributeValue))
                    return false;
            }
        }

        return true;
    }

    /**
     * Check if field is required and is not empty.
     *
     * @param array $postData
     * @return bool
     */
    protected function isRequiredCheck(array $postData)
    {
        if (!isset($postData[$this->name]) || empty($postData[$this->name])) {
            if (isset($this->attributes['optional']) && $this->attributes['optional'] == 'true') {
                return true;
            }

            return false;
        }


        return true;
    }

    /**
     * @param \DOMElement $element
     */
    protected function attachAttributes(\DOMElement $element)
    {
        if (!empty($this->attributes)) {
            foreach ($this->attributes as $attribute => $value) {
                $element->setAttribute($attribute, $value);
            }
        }
    }
}