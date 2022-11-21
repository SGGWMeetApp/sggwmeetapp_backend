<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

date_default_timezone_set('UTC');

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
