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

/**
 * Class that loads the default routes into the routing table
 *
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 */
class DefaultRouteLoader implements LoaderInterface
{
    private $kernel;

    /**
     * Constructor
     *
     * @param KernelInterface $kernel Kernel of the running application
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Find the controllers in a specific directory
     *
     * @param string $directory Directory to search in
     *
     * @return array Array of controller names
     */
    private function findControllersInFolder($directory)
    {
        $controllers = array();

        if (is_dir($directory)) {
            $finder = new Finder();
            foreach ($finder->files()->followLinks()->in($directory) as $file) {
                $controller = $file->getRelativePathname();
                if (substr($controller, -strlen('Controller.php')) == 'Controller.php') {
                    $controller = substr($controller, 0, -strlen('Controller.php'));
                    $controllers[] = $controller;
                }
            }
        }

        return $controllers;
    }


    /**
     * Load route
     *
     * @param resource $resource Resource
     * @param string   $type     Type
     *
     * @see \Symfony\Component\Config\Loader\LoaderInterface::load()
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();
        $resource = ltrim($resource, '@');
        $bundleObj = $this->kernel->getBundle($resource);
        if (!$bundleObj) {
                throw new ResourceNotFoundException("Cannot load bundle resource '$resource'");
        }
        $controllers = $this->findControllersInFolder($bundleObj->getPath().'/Controller');
        $bundle = $resource;
        $bundleName = substr($bundle, 0, -strlen('Bundle'));

        foreach ($controllers as $controllerName) {
            $controller = str_replace('/', '\\', $controllerName.'Controller');

            $controllerClass = new \ReflectionClass($bundleObj->getNameSpace().'\\Controller\\'.$controller);
            if ($controllerClass->isAbstract()) continue;

            $methods = $controllerClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (substr($method->getName(), -strlen('Action'))=='Action') {
                    $action = $method->getName();
                    $actionName = substr($action, 0, -strlen('Action'));
                    // create route name and follow 'bundle.controller.action' convention
                    $name = Inflector::tableize($bundleName).
                            '.'.Inflector::tableize($controllerName).
                            '.'.Inflector::tableize($actionName);
                    $name = str_replace('/', '.', $name);
                    // create the URL
                    $pattern = '/'.Inflector::tableize($controllerName).
                                         '/'.Inflector::tableize($actionName);
                    // set the controller that should be called
                    $defaults = array('_controller' => $bundle.':'.$controllerName.':'.$actionName);
                    // get the arguments (called parameters) from the action function definition
                    $parameters = $method->getParameters();
                    foreach ($parameters as $parameter) {
                        // add each argument as a parameter to the route
                        $pattern.='/{'.$parameter->getName().'}';
                        // optional arguments have a default value
                        if ($parameter->isOptional()) {
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

    /**
     * Method to checks whether the type is supported or not
     *
     * @param resource $resource resource
     * @param string   $type     type
     *
     * @see \Symfony\Component\Config\Loader\LoaderInterface::supports()
     * @return boolean
     */
    public function supports($resource, $type = null)
    {
        return 'default' === $type;
    }

    /**
     * Get resolver
     *
     * @see \Symfony\Component\Config\Loader\LoaderInterface::getResolver()
     */
    public function getResolver()
    {
    }

    /**
     * Set resolver
     *
     * @param RouteCollection $resolver resolver
     *
     * @see \Symfony\Component\Config\Loader\LoaderInterface::setResolver()
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
    }
}
