<?php

namespace miranj\router\models;

use craft\base\Model;

/**
* Router Settings Model
*/
class Settings extends Model
{
    // Public Properties
    // =========================================================================
    
    /**
     * @var array The URL rules.
     */
    public $rules = [];
    
    // Public Methods
    // =========================================================================
    
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
        
        foreach ($this->routes as $basePattern => $ruleConfig) {
            $ruleSegments = $ruleConfig['segments'] ?? [];
            $combineSegments = $ruleConfig['combineSegments'] ?? true;
            unset($ruleConfig['segments']);
            unset($ruleConfig['combineSegments']);
            
            $baseRule = [
                'pattern' => $basePattern,
                'route' => 'router/default/index',
                'params' => $ruleConfig,
            ];
            
            // Add all possible sub-rules using the same base config
            $segmentCombinations = self::generator($basePattern, $ruleSegments, $combineSegments);
            foreach ($segmentCombinations as $segment) {
                $subRule = $baseRule;
                $subRule['pattern'] = $segment;
                $rules[] = $subRule;
            }
        }
        
        return $rules;
    }
    
    // Private Methods
    // =========================================================================
    
    /**
     * Recursively build a sequential combination of all sub-urls,
     * treating each segment as optional. Used by self::getNormalizedRoutes().
     */
    protected static function generator(
        string $base,
        array $segments,
        bool $combineSegments = true
    ): array {
        $list = [$base];
        foreach ($segments as $index => $segment) {
            $list = array_merge($list, self::generator(
                $base.'/'.$segment,
                $combineSegments
                    ? array_slice($segments, $index + 1)
                    : []
            ));
        }
        return $list;
    }
}
