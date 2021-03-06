<?php
/**
 * YAWIK
 *
 * @filesource
 * @license MIT
 * @copyright https://yawik.org/COPYRIGHT.php
 */

/** */
namespace Jobs\Factory\View\Helper;

use Interop\Container\ContainerInterface;
use Jobs\View\Helper\AdminEditLink;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Factory for AdminEditLink view helper
 *
 * @author Mathias Gelhausen <gelhausen@cross-solution.de>
 * @since 0.29
 */
class AdminEditLinkFactory implements FactoryInterface
{

    /**
     * Create service
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array              $options
     *
     * @return AdminEditLink
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $request = $container->get('Request');
        $urlHelper = $container->get('ViewHelperManager')->get('url');
        $returnUrl = $urlHelper(null, [], ['query' => $request->getQuery()->toArray()], true);

        return new AdminEditLink($urlHelper, $returnUrl);
    }
}
