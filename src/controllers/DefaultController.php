<?php
/**
* Controller for plucking and applying filters
* on a set of entries
*/

namespace miranj\router\controllers;

use Craft;
use craft\elements\Entry;
use craft\fields\BaseOptionsField;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\web\Controller;
use miranj\router\Plugin;
use yii\db\Expression;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends Controller
{
    protected $routerService = null;
    
    public function init(): void
    {
        $this->allowAnonymous = true;
        $this->routerService = Plugin::getInstance()->router;
        parent::init();
    }
    
    
    
    // Public Methods
    // =========================================================================
    
    public function bindActionParams($action, $params): array
    {
        // remove the request path string passed to Craft
        unset($params[Craft::$app->config->general->pathParam]);
        
        $template = $params['template'];
        unset($params['template']);
        
        $criteria = $params['criteria'] ?? [];
        unset($params['criteria']);
        
        // remove route name param, if set
        if (isset($params['name'])) {
            $this->routerService->setRouteName($params['name']);
            unset($params['name']);
        }
        
        $newParams = [
            $template,
            $params,
            $criteria,
        ];
        
        return $newParams;
    }
    
    public function actionIndex(string $template, array $variables = [], array $filters = []): Response
    {
        $shorthand_mappings = [
            'entry' => 'section',
            'entries' => 'section',
            'category' => 'group',
            'categories' => 'group',
            'tag' => 'group',
            'tags' => 'group',
            'field' => 'handle',
            'year' => 'field',
            'date' => 'field',
            'month' => 'field',
            'section' => 'value',
            'type' => 'value',
        ];
        
        $criteria = Entry::find();
        
        // Apply the filters
        foreach ($filters as $trigger => $filter) {
            if (is_string($filter)) {
                $filter_args = explode(':', $filter);
                $filter = [ 'type' => $filter_args[0] ];
                if (count($filter_args) > 1) {
                    $second_param_key = isset($shorthand_mappings[$filter['type']])
                        ? $shorthand_mappings[$filter['type']]
                        : 'group';
                    $filter[$second_param_key] = $filter_args[1];
                }
            }
            
            // Look for the filter key in the URL vars
            if (isset($variables[$trigger]) and $variables[$trigger] !== '') {
                $value = $variables[$trigger];
                
                switch ($filter['type']) {
                    case 'year': // legacy year-only filter
                    case 'date': // year [/ month [/ date]] filter
                        
                        $date = explode('/', $value);
                        $date_keys = ['year', 'month', 'day'];
                        $full_date = array_pad($date, 3, '01');
                        
                        // Do not proceed if the date is invalid
                        if (checkdate($full_date[1], $full_date[2], $full_date[0]) === false) {
                            throw new NotFoundHttpException();
                        }
                        
                        // Build query limits
                        $after_date = \DateTime::createFromFormat('Y-m-d', implode('-', $full_date));
                        $before_date = clone $after_date;
                        $before_date->modify('next '.$date_keys[count($date)-1]);
                        
                        // date filter is applied on postDate by default
                        if (!isset($filter['field'])) {
                            $filter['field'] = 'postDate';
                        }
                        
                        // apply the filter
                        $criteria->{$filter['field']}([
                            'and',
                            ">= ".$after_date->format('Y-m-d'),
                            '< '.$before_date->format('Y-m-d'),
                        ]);
                        
                        // Update filter value for 'date' filter
                        // Do not modify filter value for legacy 'year' filter
                        if ($filter['type'] === 'date') {
                            $value = array_combine(array_slice($date_keys, 0, count($date)), $date);
                        }
                        break;
                    
                    
                    
                    case 'month': // month-only filter
                        
                        $month = (int)$value;
                        
                        // Do not proceed if the date is invalid
                        if ($month < 1 || $month > 12) {
                            throw new NotFoundHttpException();
                        }
                        
                        // date filter is applied on postDate by default
                        if (!isset($filter['field'])) {
                            $filter['field'] = 'postDate';
                        }
                        
                        // if the field is a custom field, get its full column name
                        $custom_field = Craft::$app->fields->getFieldByHandle($filter['field']);
                        if ($custom_field) {
                            $column = '`content`.`'
                                . ElementHelper::fieldColumnFromField($custom_field)
                                . '`';
                        } else {
                            $column = '`entries`.`'.$filter['field'].'`';
                        }
                        
                        // normalize to UTC
                        $timezone_offset = DateTimeHelper::timeZoneOffset(Craft::$app->getTimeZone());
                        $column = "$column + INTERVAL '$timezone_offset' HOUR_MINUTE";
                        
                        // apply the filter
                        $criteria->andWhere(new Expression("EXTRACT(MONTH FROM $column) = $month"));
                        
                        $value = [ 'month' => $month ];
                        break;
                    
                    
                    
                    case 'field':
                        
                        // complain if field handle hasn't been set
                        if (!isset($filter['handle'])) {
                            throw new Exception(
                                'Field filters ("type" => "field") need '
                                .'the field\'s handle ("handle" => "fieldHandle") to be declared.'
                            );
                        }
                        
                        $field = $this->fetchSingle($filter['handle'], 'field');
                        
                        // abort if no such field exists
                        if ($field === false) {
                            throw new NotFoundHttpException();
                        }
                        
                        // Look for a matching option if the field has them
                        if ($field instanceof BaseOptionsField) {
                            $options = $field->options;
                            foreach ($options as $option) {
                                if ($option['value'] === $value) {
                                    $value = $option;
                                }
                            }
                            
                            // abort if no such field option exists
                            if (!is_array($value)) {
                                throw new NotFoundHttpException();
                            }
                        } 
                        
                        $criteria->{$field['handle']}($value);
                        break;
                    
                    
                    
                    case 'search':
                        $criteria->search(isset($filter['value']) ? $filter['value'] : $value);
                        break;
                    
                    
                    
                    case 'section':
                        
                        // pre-set value (when present) overrides the URL value
                        $value = isset($filter['value']) ? $filter['value'] : $value;
                        
                        // look for the section object
                        $value = $this->fetchSingle($value, $filter['type']);
                        
                        // abort if no such section exists
                        if ($value === false || $value === null) {
                            throw new NotFoundHttpException();
                        }
                        
                        $criteria->section($value);
                        break;
                    
                    
                    
                    case 'type':
                        
                        // pre-set value (when present) overrides the URL value
                        $value = isset($filter['value']) ? $filter['value'] : $value;
                        
                        // abort if no value or no valid EntryType exists
                        if ($value === false || $value === null
                        || empty(Craft::$app->sections->getEntryTypesByHandle($value))) {
                            throw new NotFoundHttpException();
                        }
                        
                        $criteria->type($value);
                        break;
                    
                    
                    
                    case 'category':
                    case 'categories':
                    case 'entry':
                    case 'entries':
                    case 'tag':
                    case 'tags':
                    case 'uri':
                    case 'uris':
                        
                        $isSingular = in_array($filter['type'], ['uri', 'category', 'entry', 'tag']);
                        
                        // look for the filter object's section (for entries)
                        // or group (for categories etc.)
                        $filter_parent = isset($filter['section'])
                            ? $filter['section']
                            : (isset($filter['group'])
                                ? $filter['group']
                                : false);
                        
                        $includeDescendants = !isset($filter['includeDescendants']) || $filter['includeDescendants'];
                        
                        // look for the object
                        $value = $this->fetchMultiple($value, $filter['type'], $filter_parent, $includeDescendants);
                        
                        // abort if no such filter object exists
                        if ($value === false || $value === null || $value === []) {
                            throw new NotFoundHttpException();
                        }
                        
                        $relatedTo = [ 'element' => $value ];
                        
                        // include descendants for categories and structures
                        // unless explicitly told not to
                        if ($includeDescendants) {
                            $relatedTo['element'] = array_filter(array_merge(
                                $relatedTo['element'],
                                ...array_column($relatedTo['element'], 'descendants')
                            ));
                        }
                        
                        // flatten 'element' array if possible and $isSingular
                        if ($isSingular && count($relatedTo['element']) === 1) {
                            $relatedTo['element'] = array_shift($relatedTo['element']);
                        }
                        
                        // specify a via relation if a field has been mentioned
                        if (isset($filter['field'])) {
                            $relatedTo['field'] = $filter['field'];
                        }
                        
                        // apply the filter
                        if (!$criteria->relatedTo) {
                            $criteria->relatedTo(['and']);
                        }
                        // push current filter to the end of the list of
                        // existing relatedTo conditions
                        $current_relations = $criteria->relatedTo;
                        $current_relations[] = $relatedTo;
                        $criteria->relatedTo($current_relations);
                        
                        // if singular, make sure $value is not an array
                        if ($isSingular) {
                            $value = array_shift($value);
                        } else {
                            $variables[$trigger] = explode(',', $variables[$trigger]);
                        }
                        break;
                }
                
                // Update template variable with the new value
                $this->routerService->setParam($trigger, $variables[$trigger], $value);
                $variables[$trigger] = $value;
                
            } else {
                
                // Remove filters that aren't active in the current request
                unset($variables[$trigger]);
            }
        }
        
        $variables['entries'] = $criteria;
        
        // All done, render the template if it exists
        if (!$this->getView()->doesTemplateExist($template)) {
            throw new NotFoundHttpException('Template not found: ' . $template);
        }
        return $this->renderTemplate($template, $variables);
    }
    
    
    
    // Private Methods
    // =========================================================================
    
    /*
     * Find the entry/category/section/tag object by slug
     * 
     * @param         $slug     the slug/handle of the single object
     * @param         $type     one of entry, section, category, tag, field, uri
     * @param         $parent restrict search of the single object within
     *                                        the parent's scope, where parent is a slug for
     *                                        section in the case of type:entry,
     *                                        category group in the case of type:category,
     *                                        and tag group in the case of type:tag.
    **/
    private function fetchSingle($slug, $type = 'entry', $parent = false)
    {
        $items = $this->fetchMultiple($slug, $type, $parent);
        return is_array($items)
            ? array_shift($items)
            : $items;
    }
    
    private function fetchMultiple($slugs, $type = 'entry', $parent = false, $includeDescendants = false)
    {
        // normalise to array
        $slugs = explode(',', $slugs);
        
        // singularise types
        $type = [
            'categories' => 'category',
            'entries' => 'entry',
            'tags' => 'tag',
        ][$type] ?? $type;
        
        // 
        // Handle Non-Elements
        // 
        switch ($type) {
            case 'section':
            case 'sections':
                return array_map(function ($slug) {
                    return Craft::$app->sections->getSectionByHandle($slug);
                }, $slugs);
            
            case 'field':
            case 'fields':
                return array_map(function ($slug) {
                    return Craft::$app->fields->getFieldByHandle($slug);
                }, $slugs);
            
            case 'uri':
            case 'uris':
                return array_map(function ($slug) {
                    return Craft::$app->elements->getElementByUri($slug, null, true);
                }, $slugs);
        }
        
        
        // 
        // Handle Elements
        // 
        
        // Look for the ElementType class
        $elementType = '\\craft\\elements\\'.ucfirst($type);
        if (!class_exists($elementType)) {
            return false;
        }
        
        $criteria = $elementType::find();
        $criteria->slug($slugs);
        $criteria->site('*')->unique();
        if ($parent) {
            $criteria->{ $type == 'entry' ? 'section' : 'group' }($parent);
        }
        if ($includeDescendants) {
            $criteria->with([ 'descendants' ]);
        }
        $result = $criteria->all();
        
        if ($result !== []) {
            return $result;
        }
        
        return false;
    }
}
