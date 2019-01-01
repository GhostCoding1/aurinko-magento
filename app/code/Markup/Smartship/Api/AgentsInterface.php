<?php
namespace Markup\Smartship\Api;

interface AgentsInterface
{
    /**
     * Returns Posti agents by postcode
     *
     * @api
     * @return string agents in JSON
     */
    public function agents();
}
