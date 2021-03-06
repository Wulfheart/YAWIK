<?php
/**
 * YAWIK
 *
 * @filesource
 * @copyright https://yawik.org/COPYRIGHT.php
 * @license   MIT
 */

/** ListFilter.php */
namespace Jobs\Form;

/**
 * Creates search formular for job openings
 *
 * @package Jobs\Form
 */
class ListFilterAdmin extends ListFilter
{

    /**
     * Base fieldset to use
     */
    protected $fieldset = 'Jobs/ListFilterAdminFieldset';
}
