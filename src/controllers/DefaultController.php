<?php
/**
* Controller for plucking and applying filters
* on a set of entries
*/

namespace miranj\router\controllers;

use Craft;
use craft\elements\Entry;
use craft\fields\BaseOptionsField;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends Controller
{
    protected $allowAnonymous = true;
    
    
    
    // Public Methods
    // =========================================================================
    
    public function bindActionParams($action, $params)
    {
        // remove the request path string passed to Craft
        unset($params[Craft::$app->config->general->pathParam]);
        
        $template = $params['template'];
        unset($params['template']);
        
        $criteria = $params['criteria'] ?? [];
        unset($params['criteria']);
        
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
            'category' => 'group',
            'field' => 'handle',
            'section' => 'value',
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
                    case 'year':
                        
                        // year filter is applied on postDate by default
                        if (!isset($filter['field'])) {
                            $filter['field'] = 'postDate';
                        }
                        
                        // apply the filter
                        if ($filter['field'] == 'postDate') {
                            $criteria->after($value . '-01-01');
                            $criteria->before(($value + 1) . '-01-01');
                        } else {
                            $criteria->{$filter['field']}([
                                'and',
                                ">= $value-01-01",
                                '< '.($value+1).'-01-01',
                            ]);
                        }
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
                    
                    
                    
                    case 'uri':
                    case 'category':
                    case 'entry':
                        
                        // look for the filter object's section (for entries)
                        // or group (for categories etc.)
                        $filter_parent = isset($filter['section'])
                            ? $filter['section']
                            : false;
                        $filter_parent = $filter_parent === false && isset($filter['group'])
                            ? $filter['group']
                            : false;
                        
                        // look for the object
                        $value = $this->fetchSingle($value, $filter['type'], $filter_parent);
                        
                        // abort if no such filter object exists
                        if ($value === false || $value === null) {
                            throw new NotFoundHttpException();
                        }
                        
                        $relatedTo = [ 'element' => $value ];
                        
                        // include descendants for categories and structures
                        // unless explicitly told not to
                        if (
                            (!isset($filter['includeDescendants']) || $filter['includeDescendants'])
                            && $value->getHasDescendants()
                        ) {
                            $relatedTo['element'] = [
                                $relatedTo['element'],
                                $value->getDescendants()
                            ];
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
                        break;
                }
                
                // Update template variable with the new value
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
     * Find the entry/category/section object by slug
     * 
     * @param         $slug     the slug/handle of the single object
     * @param         $type     one of entry, section, category, field, uri
     * @param         $parent restrict search of the single object within
     *                                        the parent's scope, where parent is a slug for
     *                                        section in the case of type:entry, and
     *                                        category group in the case of type:category
    **/
    private function fetchSingle($slug, $type = 'entry', $parent = false)
    {
        // 
        // Handle Non-Elements
        // 
        switch ($type) {
            case 'section':
                return Craft::$app->sections->getSectionByHandle($slug);
            
            case 'field':
                return Craft::$app->fields->getFieldByHandle($slug);
            
            case 'uri':
                return Craft::$app->elements->getElementByUri($slug, null, true);
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
        $criteria->slug($slug);
        if ($parent) {
            $criteria->{ $type == 'entry' ? 'section' : 'group' }($parent);
        }
        $result = $criteria->one();
        
        if ($result !== null) {
            return $result;
        }
        
        return false;
    }
}