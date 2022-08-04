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
    
    public function setParam(string $trigger, $rawValue, $value)
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
        
        // can multiple segments be combined?
        $combineSegments = ArrayHelper::firstValue($rule)['additive'] ?? true;
        
        // convert array params into csvs
        $params = array_map(function ($value) {
            return is_array($value) ? implode(',', $value) : $value;
        }, $params);
        
        // unset empty url params
        $params = ArrayHelper::filterEmptyStringsFromArray($params);
        
        // loop over each segment
        $segments = ArrayHelper::firstValue($rule)['segments'] ?? [];
        
        // if segments cannot be combined, reduce segment list to 1 item only
        // go through entire list and use the segment with the most number of
        // variable:param matches
        if (!$combineSegments && !empty($segments)) {
            $segmentsWithMatchCounts = array_map(function($segment) use ($params) {
                $variables = array_column($this->getSegmentVariables($segment), '1');
                $paramMatches = array_intersect($variables, array_keys($params));
                return [
                    'segment' => $segment,
                    'paramMatches' => count($paramMatches),
                ];
            }, $segments);
            usort($segmentsWithMatchCounts, function ($a, $b) {
                return $b['paramMatches'] <=> $a['paramMatches'];
            });
            $segments = [array_shift($segmentsWithMatchCounts)['segment']];
        }
        
        // add mandatory segment as well
        array_unshift($segments, ArrayHelper::firstKey($rule));
        
        // replace segment variables with param values
        $segments = array_map(function($segment) use ($params) {
            
            $variables = $this->getSegmentVariables($segment);
            
            // if no variables, pass over
            if (empty($variables)) {
                return $segment;
            }
            
            // if no matching variables, exclude segment
            foreach ($variables as $variable) {
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
    
    /**
     * Get an array of regex URL variables in
     * the given URL segment
     *
     * @return array
     */
    protected function getSegmentVariables($segment)
    {
        // if no variables, pass over
        if (strpos($segment, '<') === false) {
            return [];
        }
        
        preg_match_all('/<([\w._-]+):(?:[^>]+)>/', $segment, $variables, PREG_SET_ORDER);
        
        return $variables;
    }
}
