<?php
namespace Onatera\PayumDalenysPlugin\Payum;

use Symfony\Component\HttpFoundation\RequestStack;

trait RequestStackAwareTrait
{
    protected $requestStack;

    /**
     * {@inheritDoc}
     */
    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
}
