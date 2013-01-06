<?php
namespace Lsw\DefaultRoutingBundle\Templating;

use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
* The DefaultTemplate class makes the empty Template() annotation unnecessary
*
* @author Maurits van der Schee <m.vanderschee@leaseweb.com>
*/
class DefaultTemplate
{
  // holds the twig template reference
  protected $twig;

  /**
   * Constructor
   *
   * @param TwigEngine $twig Twig engine
   */
  public function __construct(TwigEngine $twig)
  {
    // store the twig template reference in this object
    $this->twig = $twig;
  }

  /**
   * If the event has no response yet, add it by rendering the default template based on the action return values
   *
   * @param GetResponseForControllerResultEvent $event Event
   *
   * @throws RouteNotFoundException
   * @return mixed
   */
  public function onKernelView(GetResponseForControllerResultEvent $event)
  {
    // if a template is already loaded return
    if ($event->hasResponse()) {
        return;
    }
    // get the result form the action
    $result = $event->getControllerResult();
    // if the result is empty (no return statement), assume an empty array as result;
    if ($result===null) {
        $result = array();
    }
    // get the format from the request
    $format = $event->getRequest()->attributes->get('_format');
    // if format is set fallback to html (should not happen: default route has default format value)
    if (!$format) {
        $format = 'html';
    }
    // get the route
    $route = $event->getRequest()->attributes->get('_route');
    // if the route is internal skip it
    if ($route=='_internal') {
      // assume controller parameter has action path
      $controller = $event->getRequest()->attributes->get('_controller');
      // parse controller to check whether it is well-formed or not
      if (!preg_match('/([^:]+)Bundle:([^:]+):([^:]+)/', $controller)) {
        // controller cannot be parsed to determine template name
        throw new RouteNotFoundException(sprintf('Cannot parse current controller "%s" for determining template', $controller));
      }
      // set template file
      $templateFile = $controller.'.'.$format.'.twig';
    } else {
      // match route to get bundle, controller and action names
      if (!preg_match('/([^\.]+)\.([^\.]+)\.([^\.]+)/', $route, $name)) {
        throw new RouteNotFoundException("Cannot determine default template for route '$route', name should follow 'bundle.controller.action' naming convention");
      }
      // set template file
      $templateFile = implode(':', array(Inflector::classify($name[1]).'Bundle', Inflector::classify($name[2]), Inflector::tableize($name[3]).'.'.$format.'.twig'));
    }
    // render the template including any layouts etc
    $response = $this->twig->renderResponse($templateFile, $result);
    // store the rendered template in the event
    $event->setResponse($response);
  }
}
