<?php

namespace miranj\router\services;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use miranj\router\Plugin;
use yii\base\Component;



/**
* Router
*/
class Router extends Component
{
    protected $_activeParams = [];
    protected $_activeParamsRaw = [];
    protected $_activeRouteName = null;
    protected $settings = null;
    
    public function setParam(string $trigger, string $rawValue, $value)
    {
        $this->_activeParamsRaw[$trigger] = $rawValue;
        $this->_activeParams[$trigger] = $value;
    }
    
    public function setRouteName(string $name)
    {
        $this->_activeRouteName = $name;
    }
    
    public function getRouteName()
    {
        return $this->_activeRouteName;
    }
    
    protected function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = Plugin::getInstance()->settings;
        }
        return $this->settings;
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
    
    
    
    /**
     * Build a full Url using $params on the UrlRule
     * with the name or base segment = $name
     *
     * @return string
     */
    public function getUrl($name, array $params = [])
    {
        // if no $name is passed, use the currently active route
        if (is_array($name) && empty($params) && $this->_activeRouteName !== null) {
            $params = $name;
            $name = $this->getRouteName();
        }
        
        // look for a route with the requested name
        $rule = ArrayHelper::where($this->getSettings()->getRoutes(), 'name', $name);
        if (empty($rule)) {
            return false;
        }
        
        // unset empty url params
        $params = ArrayHelper::filterEmptyStringsFromArray($params);
        
        // loop over each segment
        $segments = ArrayHelper::firstValue($rule)['segments'] ?? [];
        array_unshift($segments, ArrayHelper::firstKey($rule));
        $segments = array_map(function($segment) use ($params) {
            
            // if no variables, pass over
            if (strpos($segment, '<') === false) {
                return $segment;
            }
            
            $variables = preg_match_all('/<([\w._-]+):(?:[^>]+)>/', $segment, $matches, PREG_SET_ORDER);
            
            // if no variables, pass over
            if (empty($matches)) {
                return $segment;
            }
            
            // if no matching variables, exclude segment
            foreach ($matches as $variable) {
                if (isset($params[$variable[1]])) {
                    $segment = str_replace($variable[0], $params[$variable[1]], $segment);
                } else {
                    return '';
                }
            }
            
            return $segment;
        }, $segments);
        
        $segments = ArrayHelper::filterEmptyStringsFromArray($segments);
        
        if (empty($segments)) {
            return false;
        }
        
        $url = implode('/', $segments);
        $url = UrlHelper::url($url);
        
        return $url;
    }
    
    /**
     * Build a full Url using current params merged
     * with $extraParams on the current UrlRule
     *
     * @return string
     */
    public function getUrlMerge(array $extraParams = [])
    {
        $params = array_merge($this->getRawParams(), $extraParams);
        return $this->getUrl($params);
    }
}
