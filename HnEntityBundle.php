<?php

namespace Hn\EntityBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HnEntityBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }

}
