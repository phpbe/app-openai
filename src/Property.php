<?php

namespace Be\App\Openai;


class Property extends \Be\App\Property
{

    protected string $label = 'OpenAi';
    protected string $icon = 'bi-vinyl';
    protected string $description = 'OpenAi 应用 ChatGPT';

    public function __construct() {
        parent::__construct(__FILE__);
    }

}
