<?php

namespace Lsw\DefaultRoutingBundle\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class DefaultRouteLoader implements LoaderInterface
{
  private $kernel;
    
  public function __construct(KernelInterface $kernel)
  {
    $this->kernel = $kernel;
  }
    
  private function findControllersInFolder($dir)
  {
    $controllers = array();
    
    if (is_dir($dir))
    {
    	$finder = new Finder();
      foreach ($finder->files()->followLinks()->in($dir) as $file)
      {
        $controller = $file->getRelativePathname();
        if (substr($controller, -strlen('Controller.php')) == 'Controller.php')
        {
        	$controller = substr($controller, 0, -strlen('Controller.php'));
          $controllers[] = $controller;
        }
      }
    }
    
    return $controllers;
  }
    
    
  public function load($resource, $type = null)
  {
    $routes = new RouteCollection();
    $resource = ltrim($resource,'@');
    $bundleObj = $this->kernel->getBundle($resource);
    if (!$bundleObj)
    { throw new ResourceNotFoundException("Cannot load bundle resource '$resource'");
    }
    $controllers = $this->findControllersInFolder($bundleObj->getPath().'/Controller');
    $bundle = $resource;
    $bundleName = substr($bundle, 0, -strlen('Bundle'));
    
    foreach ($controllers as $controllerName)
    {   
      $controller = $controllerName.'Controller';
        
      $controllerClass = new \ReflectionClass($bundleObj->getNameSpace().'\\Controller\\'.$controller);
      $methods = $controllerClass->getMethods(\ReflectionMethod::IS_PUBLIC);
      foreach ($methods as $method)
      { 
        if (substr($method->getName(), -strlen('Action'))=='Action') 
        {    
          $action = $method->getName();
          $actionName = substr($action, 0, -strlen('Action'));
          // create route name and follow 'bundle.controller.action' convention
          $name = Inflector::tableize($bundleName).
              '.'.Inflector::tableize($controllerName).
              '.'.Inflector::tableize($actionName);
          // create the URL
          $pattern = '/'.Inflector::tableize($controllerName).
                     '/'.Inflector::tableize($actionName);
          // set the controller that should be called 
          $defaults = array('_controller' => $bundle.':'.$controllerName.':'.$actionName);
          // get the arguments (called parameters) from the action function definition
          $parameters = $method->getParameters();
          foreach ($parameters as $parameter)
          {  
            // add each argument as a parameter to the route
            $pattern.='/{'.$parameter->getName().'}';
            // optional arguments have a default value
            if ($parameter->isOptional())
            {
              $defaults[$parameter->getName()]=$parameter->getDefaultValue();
            }
          }
          // allow setting the format using extensions like .html or .json
          $pattern.='.{_format}';
          // default format is html
          $defaults['_format']='html';
          // add route to collection
          $routes->add($name, new Route($pattern, $defaults));
        }
      }
    }
    return $routes;
  }
    
  public function supports($resource, $type = null)
  {
    return 'default' === $type;
  }
  
  public function getResolver()
  {
  }
  
  public function setResolver(LoaderResolverInterface $resolver)
  {
  }
}
