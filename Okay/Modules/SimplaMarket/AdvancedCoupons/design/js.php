<?php

use Okay\Core\TemplateConfig\Js;

return [
    (new Js('jquery.time-to.min.js'))->setPosition('head'),
    (new Js('sale.js'))->setPosition('footer'),
];
