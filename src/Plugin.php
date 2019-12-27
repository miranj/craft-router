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
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use miranj\router\models\Settings;
use miranj\router\services\Router;
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
        
        // Set services as components
        $this->set('router', Router::class);
        
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            [$this, 'registerUrlRules']
        );
        
        // Attach services to the Craft variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
            $variable = $e->sender;
            $variable->set('router', $this->get('router'));
        });
        
        Craft::info(
            Craft::t(
                'router',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }
    
    public function registerUrlRules(RegisterUrlRulesEvent $event)
    {
        $event->rules = array_merge($event->rules, $this->getSettings()->normalizedRoutes);
    }
    
    
    
    // Protected Methods
    // =========================================================================
    
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }
}
