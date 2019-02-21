<?php
/**
 * Router plugin for Craft CMS 3.x
 *
 * Define URL rules to pages with a filtered, pre-loaded list of entries.
 *
 * @link      https://miranj.in/
 * @copyright Copyright (c) 2019 Miranj
 */

namespace miranj\router;

use Craft;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    
    // Public Methods
    // =========================================================================
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            [$this, 'registerUrlRules']
        );
    }
    
    public function registerUrlRules(RegisterUrlRulesEvent $event)
    {
        $event->rules = array_merge($event->rules, $this->buildUrlRules());
    }
    
    public function buildUrlRules(): array
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
        
        foreach ($this->getSettings()->rules as $basePattern => $ruleConfig) {
            $ruleSegments = $ruleConfig['segments'] ?? [];
            unset($ruleConfig['segments']);
            
            $baseRule = [
                'pattern' => $basePattern,
                'route' => 'router/default/index',
                'params' => $ruleConfig,
            ];
            $rules[] = $baseRule;
            
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
    
    
    
    // Protected Methods
    // =========================================================================
    
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
