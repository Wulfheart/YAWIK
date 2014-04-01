<?php
/**
 * YAWIK
 * 
 * @filesource
 * @copyright (c) 2013 Cross Solution (http://cross-solution.de)
 * @license   GPLv3
 */

/** Applications controller */
namespace Applications\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Session\Container as Session;
use Auth\Exception\UnauthorizedAccessException;
use Applications\Entity\StatusInterface as Status;
use Applications\Entity\Comment;
use Applications\Entity\Rating;

/**
 * Action Controller for managing applications.
 */
class ManageController extends AbstractActionController
{
    
    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        $action     = $this->params()->fromQuery('action');
        
        if ($routeMatch && $action) { 
            $routeMatch->setParam('action', $action);
        }

        return parent::onDispatch($e);
    }
    
    /**
     * List applications
     */
    public function indexAction()
    { 
        $params = $this->getRequest()->getQuery();
        $jsonFormat = 'json' == $params->get('format');
        
        if (!$jsonFormat) {
            $session = new Session('Applications\Index');
            if ($session->params) {
                foreach ($session->params as $key => $value) {
                    $params->set($key, $params->get($key, $value));
                }
            }
            $session->params = $params->toArray();
        }
        
        $v = new ViewModel(array(
            'by' => $params->get('by', 'me'),
            'hasJobs' => (bool) $this->getServiceLocator()
                                     ->get('repositories')
                                     ->get('Jobs/Job')
                                     ->countByUser($this->auth('id')),
             'newApplications' => $this->getServiceLocator()
                                     ->get('repositories')
                                     ->get('Applications/Application')
                                     ->countBy($this->auth('id'),true)
        ));
        $v->setTemplate('applications/sidebar/manage');
        $this->layout()->addChild($v, 'sidebar_applicationsFilter');

        //default sorting
        if (!isset($params['sort'])) {
            $params['sort']="-date";
        }
        
        $paginator = $this->paginator('Applications/Application',$params);
                
        if ($jsonFormat) {
            $viewModel = new JsonModel();
            //$items = iterator_to_array($paginator);
            
            $viewModel->setVariables(array(
                'items' => $this->getServiceLocator()->get('builders')->get('JsonApplication')
                                ->unbuildCollection($paginator->getCurrentItems()),
                'count' => $paginator->getTotalItemCount()
            ));
            return $viewModel;
            
        } 
        
        return array(
            'applications' => $paginator,
            'byJobs' => 'jobs' == $params->get('by', 'me'),
            'sort' => $params->get('sort', 'none'),
        );
        
        
    }
    
    /**
     * detail view of an application
     * 
     * @return Ambigous <\Zend\View\Model\JsonModel, multitype:boolean unknown >
     */
    public function detailAction(){

        if ('refresh-rating' == $this->params()->fromQuery('do')) {
            return $this->refreshRatingAction();
        }
        
        $nav = $this->getServiceLocator()->get('main_navigation');
        $page = $nav->findByRoute('lang/applications');
        $page->setActive();
        
        $repository = $this->getServiceLocator()->get('repositories')->get('Applications/Application');
        $application = $repository->find($this->params('id'));
    	
    	$this->acl($application, 'read');
    	
    	$applicationIsUnread = false;
    	if ($application->isUnreadBy($this->auth('id'))) {
    	    $application->addReadBy($this->auth('id'));
    	    $repository->save($application, /*$resetModifiedDate*/ false);
    	    $applicationIsUnread = true;
    	}
    	
        $format=$this->params()->fromQuery('format');

        $return = array('application'=> $application, 'isUnread' => $applicationIsUnread);
        switch ($format) {
            case 'json':
                        $viewModel = new JsonModel();
                        $viewModel->setVariables(/*array(
                    'application' => */$this->getServiceLocator()
                                              ->get('builders')
                                              ->get('JsonApplication')
                                              ->unbuild($application)
                        );
                        $viewModel->setVariable('isUnread', $applicationIsUnread);
                $return = $viewModel;
            case 'pdf':
                $pdf = $this->getServiceLocator()->get('Core/html2pdf');
           
                break;
            default:
                $contentCollector = $this->getPluginManager()->get('Core/ContentCollector'); 
                $contentCollector->setTemplate('applications/manage/details/action-buttons');
                $actionButtons = $contentCollector->trigger('application.detail.actionbuttons', $application);
                
                $return = new ViewModel($return);
                $return->addChild($actionButtons, 'externActionButtons');
                break;
        }
        
        return $return;
    }
    
    public function refreshRatingAction()
    {
        $model = new ViewModel();
        $model->setTemplate('applications/manage/_rating');
        
        $application = $this->getServiceLocator()->get('repositories')->get('Applications/Application')
                        ->find($this->params('id', 0));
        
        if (!$application) {
            throw new \DomainException('Invalid application id.');
        }
        
        $model->setVariable('application', $application);
        return $model;
    }

    /**
     * change status of an application
     * 
     * @return unknown|multitype:string |multitype:string unknown |multitype:unknown
     */
    public function statusAction()
    {
        $applicationId = $this->params('id');
        $repository    = $this->getServiceLocator()->get('repositories')->get('Applications/Application');
        $application   = $repository->find($applicationId);
        
        $this->acl($application, 'change');
        
        $jsonFormat    = 'json' == $this->params()->fromQuery('format');
        $status        = $this->params('status', Status::CONFIRMED);
        $settings = $this->settings();
        
        if (in_array($status, array(Status::INCOMING))) {
            $application->changeStatus($status);
            $repository->save($application);
            if ($this->request->isXmlHttpRequest()) {
                $response = $this->getResponse();
                $response->setContent('ok');
                return $response;
            }
            if ($jsonFormat) {
                return array(
                    'status' => 'success',
                );
            }
            return $this->redirect()->toRoute('lang/applications/detail', array(), true);
        }
       $mailService = $this->getServiceLocator()->get('Core/MailService');
       $mail = $mailService->get('Applications/StatusChange');
       $mail->setApplication($application);
       if ($this->request->isPost()) {
           $mail->setSubject($this->params()->fromPost('mailSubject'));
           $mail->setBody($this->params()->fromPost('mailText'));
           if ($from = $application->job->contactEmail) {
                $mail->setFrom($from, $application->job->company);
           }
           $mailService->send($mail);
           
            $application->changeStatus($status, sprintf('Mail was sent to %s' , $application->contact->email));
            $repository->save($application);
            
            if ($jsonFormat) {
                return array(
                    'status' => 'success', 
                );
            }
            return $this->redirect()->toRoute('lang/applications/detail', array(), true);
        }
        
        $translator = $this->getServiceLocator()->get('translator');
        switch ($status) {
            default:
            case Status::CONFIRMED: $key = 'mailConfirmationText'; break;
            case Status::INVITED  : $key = 'mailInvitationText'; break;
            case Status::REJECTED : $key = 'mailRejectionText'; break;
        }
        $mailText      = $settings->$key ? $settings->$key : '';
        $mail->setBody($mailText);
        $mailText = $mail->getBodyText();
        $mailSubject   = sprintf(
            $translator->translate('Your application dated %s'),
            strftime('%x', $application->dateCreated->getTimestamp())
        );
        
        $params = array(
                'applicationId' => $applicationId,
                'status'        => $status,
                'mailSubject'   => $mailSubject,
                'mailText'      => $mailText        
            ); 
        if ($jsonFormat) {
            return $params;
        }
        
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Applications/Mail');
        $form->populateValues($params);
        
        
        return array(
            'form' => $form
        );
          
    } 
    
    /**
     * forward an application via Email
     * 
     * @throws \InvalidArgumentException
     * @return \Zend\View\Model\JsonModel
     */
    public function forwardAction()
    {
        $services     = $this->getServiceLocator();
        $emailAddress = $this->params()->fromQuery('email');
        $application  = $services->get('repositories')->get('Applications/Application')
                                 ->find($this->params('id'));
        
        $this->acl($application, 'forward');
        
        $translator   = $services->get('translator');
         
        if (!$emailAddress) {
            throw new \InvalidArgumentException('An email address must be supplied.');
        }
        
        $params = array(
            'ok' => true,
            'text' => sprintf($translator->translate('Forwarded application to %s'), $emailAddress)
        );
        
        try {
            $userName    = $this->auth('info')->displayName;
            $fromAddress = $application->job->contactEmail;
            $mailOptions = array(
                'application' => $application,
                'to'          => $emailAddress,
                'from'        => array($fromAddress => $userName)
            );
            $this->mailer('Applications/Forward', $mailOptions, true);
        } catch (\Exception $ex) {
            $params = array(
                'ok' => false,
                'text' => sprintf($translator->translate('Forward application to %s failed.'), $emailAddress)
            );
        }
        $application->changeStatus($application->status,$params['text']);
        return new JsonModel($params);
    }
    
    /**
     * delete an application
     * 
     * @throws \DomainException
     * @return multitype:string
     */
    public function deleteAction()
    {
        $id          = $this->params('id');
        $services    = $this->getServiceLocator();
        $repository  = $services->get('repositories')->get('Applications/Application');
        $application = $repository->find($id);
        
        if (!$application) {
            throw new \DomainException('Application not found.');
        }
        
        $this->acl($application, 'delete');
        
        $repository->delete($application);
        
        if ('json' == $this->params()->fromQuery('format')) {
            return array(
                'status' => 'success'
            );
        }
        
        $this->redirect()->toRoute('lang/applications', array(), true);
    }
    
}
