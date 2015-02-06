<?php
namespace Craft;

/**
* Controller for plucking and applying filters
* on a set of entries
*/
class Router_ListsController extends BaseController
{
  protected $allowAnonymous = true;
  
  public function actionApplyFilters($list, array $filters = array(), $template, array $variables = array())
  {
    $shorthand_mappings = array(
      'entry' => 'section',
      'category' => 'group',
      'field' => 'handle',
    );
    
    // First try and fetch the base section
    $section = $this->fetchSingle($list, 'section');
    
    if ($section) {
      $criteria = craft()->elements->getCriteria(ElementType::Entry);
      $criteria->section = $section;
      
      // Apply the filters
      foreach ($filters as $trigger => $filter) {
        if (is_string($filter)) {
          $filter_args = explode(':', $filter);
          $filter = array( 'type' => $filter_args[0] );
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
                $criteria->after = $value;
                $criteria->before = $value+1;
              } else {
                $criteria->{$filter['field']} = array(
                  'and',
                  ">= $value-01-01",
                  '< '.($value+1).'-01-01',
                );
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
                throw new HttpException(404);
              }
              
              // Look for a matching option if the field has them
              if ($field->fieldType instanceof BaseOptionsFieldType) {
                $options = $field->fieldType->options;
                foreach ($options as $option) {
                  if ($option['value'] === $value) {
                    $value = $option;
                  }
                }
                
                // abort if no such field option exists
                if (!is_array($value)) {
                  throw new HttpException(404);
                }
              } 
              
              $criteria->{$field['handle']} = $value;
              break;
            
            
            
            case 'search':
              $criteria->search = isset($filter['value']) ? $filter['value'] : $value;
              break;
            
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
              if ($value === false) {
                throw new HttpException(404);
              }
              
              $relatedTo = array(
                'element' => $value,
              );
              
              // include descendants for categories and structures
              // unless explicitly told not to
              if (
                (!isset($filter['includeDescendants']) || $filter['includeDescendants'])
                && $value->hasDescendants()
              ) {
                $relatedTo['element'] = array($relatedTo['element'], $value->getDescendants());
              }
              
              // specify a via relation if a field has been mentioned
              if (isset($filter['field'])) {
                $relatedTo['field'] = $filter['field'];
              }
              
              // apply the filter
              if (!$criteria->relatedTo) {
                $criteria->relatedTo = array('and');
              }
              // push current filter to the end of the list of
              // existing relatedTo conditions
              $current_relations = $criteria->relatedTo;
              $current_relations[] = $relatedTo;
              $criteria->relatedTo = $current_relations;
              break;
          }
          
          // Update template variable with the new value
          $variables[$trigger] = $value;
          
        } else {
          
          // Remove filters that aren't active in the current request
          unset($variables[$trigger]);
        }
      }
      
      $variables['section'] = $section;
      $variables['entries'] = $criteria;
    }
    
    
    // All done, render the template
    if (craft()->templates->doesTemplateExist($template)) {
      $this->renderTemplate($template, $variables);
    } else {
      throw new HttpException(404);
    }
  }
  
  
  
  /*
   * Find the entry/category/section object by slug
   * 
   * @param     $slug   the slug/handle of the single object
   * @param     $type   one of entry, section, category, field
   * @param     $parent restrict search of the single object within
   *                    the parent's scope, where parent is a slug for
   *                    section in the case of type:entry, and
   *                    category group in the case of type:category
  **/
  private function fetchSingle($slug, $type = 'entry', $parent = false) {
    
    // 
    // Handle Non-Elements
    // 
    switch ($type) {
      case 'section':
        return craft()->sections->getSectionByHandle($slug);
      
      case 'field':
        return craft()->fields->getFieldByHandle($slug);
    }
    
    // 
    // Handle Elements
    // 
    
    // Look for the ElementType class
    $elementType = 'Craft\ElementType::'.ucfirst($type);
    if (!defined($elementType)) {
      return false;
    }
    
    $elementType = constant($elementType);
    $criteria = craft()->elements->getCriteria($elementType);
    $criteria->slug = $slug;
    if ($parent) {
      $criteria->{ $type == 'entry' ? 'section' : 'group' } = $parent;
    }
    $criteria->find();
    
    if (count($criteria)) {
      return $criteria->first();
    }
    
    return false;
  }
}



