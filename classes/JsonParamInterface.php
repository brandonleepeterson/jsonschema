<?php

interface JsonParamInterface
{
    public function __construct(array $param);
    public function _validate(array $postData);
    public function validate(array $postData);
    public function insertElements(\DOMDocument &$dom, \DOMElement &$form);
}