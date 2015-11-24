<?php
namespace Craft;

class RouterPlugin extends BasePlugin
{
  function getName()
  {
       return Craft::t('Router');
  }
  
  function getVersion()
  {
      return '0.2';
  }
  
  function getDeveloper()
  {
      return 'Miranj';
  }
  
  function getDeveloperUrl()
  {
      return 'http://miranj.in';
  }
  
  function getDocumentationUrl()
  {
      return 'https://github.com/miranj/craft-router/blob/master/README.md#usage';
  }
  
  function getDescription()
  {
      return 'Route URLs with a preloaded ElementCriteriaModel object.';
  }
}



