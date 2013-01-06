<?php
namespace Lsw\DefaultRoutingBundle\Routing;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\RequestContext;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * The DefaultRouter class allows use of relative (shortenend) route names
 *
 * @author Maurits van der Schee <m.vanderschee@leaseweb.com>
 */
class DefaultRouter extends Router
{
    // holds the container reference to be able to get route from request
    private $container;

    /**
     * Constructor to disable caching in development mode
     *
     * @param ContainerInterface $container A ContainerInterface instance
     * @param mixed              $resource  The main resource to load
     * @param array              $options   An array of options
     * @param RequestContext     $context   The context
     */
    public function __construct(ContainerInterface $container, $resource, array $options = array(), RequestContext $context = null)
    { // store the container reference in this object
        $this->container = $container;
        // if we are in development mode, disable the routing cache
        if ($container->getParameter('kernel.environment')=='dev') {
                $options['matcher_cache_class']=null;
                $options['generator_cache_class']=null;
        }
        // call the old constructor
        parent::__construct($container, $resource, $options, $context);
    }

    /**
     * Generate URL by Route
     *
     * @param string  $name       Route name
     * @param string  $parameters Parameters for the route
     * @param boolean $absolute   Absolute/Relative URL
     *
     * @return mixed
     */
    public function generate($name, $parameters = array(), $absolute = false)
    { // make sure the route collection is available
        if (!$this->collection) {
                $this->getRouteCollection();
        }
        // if the route does not exist
        if (!$this->collection->get($name)) {
            // get the current request
            $request = $this->container->get('request');
            // relate the name to the current route
            $name = $this->relate($request, $name);
        } // generate the url from the name

        return parent::generate($name, $parameters, $absolute);
    }

    /**
     * Make a relative route into a absolute route by relating it to the current request
     *
     * @param Request $request The request to relate to
     * @param string  $name    The relative route name
     *
     * @return string          The absolute route name
     *
     * @throws RouteNotFoundException
     */
    private function relate($request,$name)
    { // get the current route
        $route = $request->attributes->get('_route');
        // if route is internal relate using controller
        if ($route=='_internal') {
            // get the current controller
            $controller = $request->attributes->get('_controller');
            // try to match the current route against the default routing scheme
            if (!preg_match('/([^:]+)Bundle:([^:]+):([^:]+)/', $controller, $current)) {
                // route cannot be related and was not found
                throw new RouteNotFoundException(sprintf('Cannot parse current controller "%s" for determining current route', $controller));
            }
        } else {
            // try to match the current route against the default routing scheme
            if (!preg_match('/([^\.]+)\.([^\.]+)\.([^\.]+)/', $route, $current)) {
                // route cannot be related and was not found
                throw new RouteNotFoundException(sprintf('Cannot parse current route "%s" for determining current route', $route));
            }
        }
        // if the route was found make sure the route is well formed
        $current = array(Inflector::tableize($current[1]),Inflector::tableize($current[2]),Inflector::tableize($current[3]));
        // the name is split into an array containing the new route
        $new = explode('.', $name);
        // if the new route has 1 element
        if (count($new)==1) {
            // if the new route is empty, set the current route as new route
            if ($new[0]=='') {
                $new = $current;
            } else {
                // only action is specified, copy bundle and controller from current route
                $new = array($current[0], $current[1], $new[0]);
            }
        } else if (count($new)==2) {
                // if controller and action are specified, copy only bundle from current route
                $new = array($current[0],$new[0],$new[1]);
        }

        // now everything is specified, return the new (full) route (name)
        return implode('.', $new);
    }

}
