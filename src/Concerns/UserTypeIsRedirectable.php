<?php

namespace Delgont\Armor\Concerns;

trait UserTypeIsRedirectable
{
    public function route()
    {
        return (property_exists($this, 'redirect')) ? $this->redirect : null;
    }
}