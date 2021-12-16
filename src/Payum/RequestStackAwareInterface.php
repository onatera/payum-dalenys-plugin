<?php
namespace Onatera\PayumDalenysPlugin\Payum;

use Symfony\Component\HttpFoundation\RequestStack;

interface RequestStackAwareInterface
{
    public function setRequestStack(RequestStack $requestStack);
}
