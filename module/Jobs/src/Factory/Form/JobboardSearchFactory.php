<?php
/**
 * YAWIK
 *
 * @filesource
 * @license MIT
 * @copyright https://yawik.org/COPYRIGHT.php
 */

/** */
namespace Jobs\Factory\Form;

use Core\Factory\Form\AbstractCustomizableFieldsetFactory;
use Jobs\Form\JobboardSearch;

/**
 * Factory for the ListFilterLocation (Job Title and Location)
 *
 * @author Carsten Bleek <bleek@cross-solution.de>
 */
class JobboardSearchFactory extends AbstractCustomizableFieldsetFactory
{
    const OPTIONS_NAME = 'Jobs/JobboardSearchOptions';

    const CLASS_NAME = JobboardSearch::class;
}
