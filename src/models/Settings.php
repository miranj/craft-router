<?php

namespace miranj\router\models;

use craft\base\Model;

class Settings extends Model
{
    public $rules = [];
    
    public function getRoutes(): array
    {
        $routes = $this->rules;
        
        foreach ($routes as $baseSegment => $config) {
            if (!isset($config['name'])) {
                $routes[$baseSegment]['name'] = $baseSegment;
            }
        }
        
        return $routes;
    }
    
    /**
     * Transforms router config into Yii compatible UrlRules
     * https://www.yiiframework.com/doc/guide/2.0/en/runtime-routing#url-rules
     * 
     * Creates a unique UrlRule for each possible combination
     * of segments (per rule). So for eg:
     * 
     *     'events' => [
     *         'segments' => [
     *             'at:<location:{slug}>',
     *             '<type:{slug}>'
     *         ],
     *         ...
     *     ]
     * 
     * would generate the following UrlRules
     * - 'events'
     * - 'events/at:<location:{slug}>'
     * - 'events/at:<location:{slug}>/<type:{slug}>'
     * - 'events/<type:{slug}>'
     * 
     * @return array
     */
    public function getNormalizedRoutes(): array
    {
        $rules = [];
        
        // Build a sequential combination of all sub-urls
        // treating each segment as optional
        function generator(string $base, array $segments) {
            $list = [$base];
            foreach ($segments as $index => $segment) {
                $list = array_merge($list, generator(
                    $base.'/'.$segment,
                    array_slice($segments, $index + 1)
                ));
            }
            return $list;
        }
        
        foreach ($this->routes as $basePattern => $ruleConfig) {
            $ruleSegments = $ruleConfig['segments'] ?? [];
            unset($ruleConfig['segments']);
            
            $baseRule = [
                'pattern' => $basePattern,
                'route' => 'router/default/index',
                'params' => $ruleConfig,
            ];
            
            // Add all possible sub-rules using the same base config
            $segmentCombinations = generator($basePattern, $ruleSegments);
            foreach ($segmentCombinations as $segment) {
                $subRule = $baseRule;
                $subRule['pattern'] = $segment;
                $rules[] = $subRule;
            }
        }
        
        return $rules;
    }
}
