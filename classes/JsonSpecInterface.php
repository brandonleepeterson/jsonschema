<?php

interface JsonSpecInterface
{
    public function __construct(array $specData);
    public function validate(array $postData);
    public function toForm();
}