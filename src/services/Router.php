<?php

namespace miranj\router\services;

use Craft;
use miranj\router\Plugin;
use yii\base\Component;



/**
* Router
*/
class Router extends Component
{
    protected $_activeParams = [];
    protected $_activeParamsRaw = [];
    
    public function setParam(string $trigger, string $rawValue, $value)
    {
        $this->_activeParamsRaw[$trigger] = $rawValue;
        $this->_activeParams[$trigger] = $value;
    }
    
    
    
    /**
     * Fetch all named parameters in the current UrlRule
     * and their query values
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->_activeParams;
    }
    
    /**
     * Fetch all named parameters & raw string values
     *
     * @return array
     */
    public function getRawParams(): array
    {
        return $this->_activeParamsRaw;
    }
}
