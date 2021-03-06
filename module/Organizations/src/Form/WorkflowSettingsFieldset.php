<?php
/**
 * YAWIK
 *
 * @filesource
 * @license    MIT
 * @copyright https://yawik.org/COPYRIGHT.php
 */

/** */
namespace Organizations\Form;

use Core\Form\ViewPartialProviderInterface;
use Organizations\Entity\EmployeeInterface;
use Laminas\Form\Fieldset;
use Organizations\Entity\EmployeePermissionsInterface as Perms;

/**
 * Workflow Settings.
 *
 * @author Carsten Bleek <bleek@cross-solution.de>
 * @since  0.25
 */
class WorkflowSettingsFieldset extends Fieldset implements ViewPartialProviderInterface
{
    protected $partial = 'organizations/form/workflow-fieldset';

    public function setViewPartial($partial)
    {
        $this->partial = (string) $partial;

        return $this;
    }

    public function getViewPartial()
    {
        return $this->partial;
    }

    public function init()
    {
        $this->setName('Workflow');

        $this->add(
            array(
                'type'    => 'checkbox',
                'name'    => 'acceptApplicationByDepartmentManager',
                'label'   => 'accept',
                'options' => [
                    'label' => /* @translate */ 'accept Applications by Department Managers',
                    'long_label' => /* @translate */ 'if checked, department managers are informed about new applications first.',
                    'description' => /* @translate */ 'Department managers are notified of incoming applications and must accept this. Only then the recruiter can start his work with the application'
                ],
            )
        );

        $this->add(
            array(
                'type'    => 'checkbox',
                'name'    => 'acceptApplicationByRecruiters',
                'label'   => 'accept',
                'options' => [
                    'label' => /* @translate */ 'accept Applications by recruiters',
                    'long_label' => /* @translate */ 'if checked, all recruiters of the organization are informed about new applications first.',
                    'description' => /* @translate */ 'Recruiters are notified of incoming applications and must accept/reject them. Accepted applications are forwarded to the department manager assigned to the job.'
                ],
            )
        );

        $this->add(
            array(
                'type'    => 'checkbox',
                'name'    => 'assignDepartmentManagersToJobs',
                'label'   => 'assign',
                'options' => [
                    'label' => /* @translate */ 'assign department managers to jobs',
                    'long_label' => /* @translate */ 'if checked, department managers have to be assigned to job postings.',
                    'description' => /* @translate */ 'if you have more them one department managers, you can assign them to a job posting. If nobody is assigned, all department managers will be informed about new applications',
                ],
            )
        );
    }
}
