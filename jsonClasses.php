<?php namespace orecrush\json;

class jsonSpecException extends \Exception {}

interface jsonSpecInterface
{
    public function __construct(array $specData);
    public function validate(array $postData);
    public function toForm();
}

interface jsonParamInterface
{
    public function __construct(array $param);
    public function validate(array $postData);
    public function insertElements(\DOMDocument &$dom, \DOMElement &$form);
}

class JsonSpec implements jsonSpecInterface
{

    private $params;
    private $title;
    private $desc;
    public $invalidParams = [];

    /**
     * @param array $specData
     * @throws jsonSpecException
     */
    public function __construct(array $specData)
    {
        // Validate schema and assign title / description.
        if (empty($specData['title'])) throw new jsonSpecException('Spec must have title.');
        if (empty($specData['desc'])) throw new jsonSpecException('Spec must have description.');
        if (empty($specData['params']) || !is_array($specData['params'])) throw new jsonSpecException('Spec must have valid parameters.');
        $this->title = $specData['title'];
        $this->desc = $specData['desc'];

        // Loop through parameters, creating jsonParam objects internally.
        foreach ($specData['params'] as $key => $param) {
            $jsonParam = new JsonParam($param);
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
            $param->insertElements($dom, $form); // TODO: figure out how to properly attach elements to form to get around this hack.
        }

        // Create submit button
        $submit = $form->appendChild(new \DOMElement('input'));
        $submit->setAttribute('type', 'submit');
        $submit->setAttribute('value', 'Submit');

        return $dom->saveHTML();
    }
}

class JsonParam implements jsonParamInterface
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
     * @throws jsonSpecException
     */
    public function __construct(array $param)
    {
        // Validate parameters and assign name / type.
        if (empty($param['name'])) throw new jsonSpecException('Parameter must have a name.');
        if (empty($param['type'])) throw new jsonSpecException('Parameter must have a type.');
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
     * @param array $postData
     * @return bool
     */
    public function validate(array $postData)
    {
        if (!$this->validateAttributes($postData))
            return false;

        switch ($this->type) {
            case 'email':
                if ($this->isRequiredCheck($postData)) {
                    return filter_var($postData[$this->name], FILTER_VALIDATE_EMAIL);
                }
                break;
            case 'text':
            case 'textarea':
            case 'string':
                if ($this->isRequiredCheck($postData)) {
                    return is_string($postData[$this->name]);
                }
                break;
            case 'int': // TODO: int or decimal?
                if ($this->isRequiredCheck($postData)) {
                    return is_numeric($postData[$this->name]); // TODO: better type checking / should we cast?
                }
                break;
            case 'checkbox':
                foreach ($this->values as $name) {
                    if (isset($postData[$name]) && $postData[$name] != 'on')
                        return false;
                }
                return true;
                break;
            case 'date':
                if ($this->isRequiredCheck($postData)) {
                    return (bool)strtotime($postData[$this->name]);
                }
                break;
            case 'state':
                $states = statesList();
                if (in_array($postData[$this->type], $states))
                    return true;
                break;
            default:
                return isset($postData[$this->name]);
        }

        return false;
    }

    /**
     * @param \DOMDocument $dom
     * @param \DOMElement $form
     * @throws jsonSpecException
     */
    public function insertElements(\DOMDocument &$dom, \DOMElement &$form)
    {
        // Create an optional label.
        if (isset($this->label)) {
            $label = $form->appendChild(new \DOMElement('label'));
            $label->appendChild($dom->createTextNode($this->label));
        }

        // TODO: refactor the HTML building. Too much duplication.
        switch ($this->type) {
            case 'checkbox':
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
                break;

            case 'state':
            case 'select':
                if (empty($this->values)) throw new jsonSpecException("select's must have values");
                $input = $form->appendChild(new \DOMElement('select'));
                $input->setAttribute('name', $this->name);
                $this->attachAttributes($input);

                foreach ($this->values as $value) {
                    $option = $input->appendChild(new \DOMElement('option'));
                    $option->appendChild($dom->createTextNode($value));
                    $option->setAttribute('value', $value);
                }
                break;

            case 'string':
            case 'text':
            case 'email':
            case 'int':
                $input = $form->appendChild(new \DOMElement('input'));
                $this->attachAttributes($input);
                $input->setAttribute('type', 'text');
                $input->setAttribute('name', $this->name);
                $input->setAttribute('placeholder', $this->name);
                break;
            case 'textarea':
                $input = $form->appendChild(new \DOMElement('textarea'));
                $this->attachAttributes($input);
                $input->setAttribute('name', $this->name);
                break;
            case 'date':
                $input = $form->appendChild(new \DOMElement('input'));
                $this->attachAttributes($input);
                $input->setAttribute('type', 'date');
                $input->setAttribute('name', $this->name);
                $input->setAttribute('placeholder', $this->name);
                break;
        }
    }

    /**
     * Validate certain html attributes server side.
     *
     * @param array $postData
     * @return bool
     */
    private function validateAttributes(array $postData)
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
    private function isRequiredCheck(array $postData)
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
    private function attachAttributes(\DOMElement $element)
    {
        if (!empty($this->attributes)) {
            foreach ($this->attributes as $attribute => $value) {
                $element->setAttribute($attribute, $value);
            }
        }
    }
}

function statesList() { // TODO: from db
    $states = [
        'AL'=>"Alabama",
        'AK'=>"Alaska",
        'AZ'=>"Arizona",
        'AR'=>"Arkansas",
        'CA'=>"California",
        'CO'=>"Colorado",
        'CT'=>"Connecticut",
        'DE'=>"Delaware",
        'DC'=>"District Of Columbia",
        'FL'=>"Florida",
        'GA'=>"Georgia",
        'HI'=>"Hawaii",
        'ID'=>"Idaho",
        'IL'=>"Illinois",
        'IN'=>"Indiana",
        'IA'=>"Iowa",
        'KS'=>"Kansas",
        'KY'=>"Kentucky",
        'LA'=>"Louisiana",
        'ME'=>"Maine",
        'MD'=>"Maryland",
        'MA'=>"Massachusetts",
        'MI'=>"Michigan",
        'MN'=>"Minnesota",
        'MS'=>"Mississippi",
        'MO'=>"Missouri",
        'MT'=>"Montana",
        'NE'=>"Nebraska",
        'NV'=>"Nevada",
        'NH'=>"New Hampshire",
        'NJ'=>"New Jersey",
        'NM'=>"New Mexico",
        'NY'=>"New York",
        'NC'=>"North Carolina",
        'ND'=>"North Dakota",
        'OH'=>"Ohio",
        'OK'=>"Oklahoma",
        'OR'=>"Oregon",
        'PA'=>"Pennsylvania",
        'RI'=>"Rhode Island",
        'SC'=>"South Carolina",
        'SD'=>"South Dakota",
        'TN'=>"Tennessee",
        'TX'=>"Texas",
        'UT'=>"Utah",
        'VT'=>"Vermont",
        'VA'=>"Virginia",
        'WA'=>"Washington",
        'WV'=>"West Virginia",
        'WI'=>"Wisconsin",
        'WY'=>"Wyoming"
    ];

    return $states;
}