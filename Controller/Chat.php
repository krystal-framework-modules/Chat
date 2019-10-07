<?php

namespace Chat\Controller;

use Site\Controller\AbstractSiteController;
use Krystal\Stdlib\VirtualEntity;

final class Chat extends AbstractSiteController
{
    /**
     * Renders data grid
     * 
     * @return string
     */
    public function indexAction()
    {
        // Get receivers of current user
        $receivers = $this->getModuleService('messageService')->fetchReceivers($this->getAuthService()->getId());

        return $this->view->render('profile/chat', array(
            'receivers' => $receivers
        ));
    }

    /**
     * Sends a message
     * 
     * @return string
     */
    public function sendAction()
    {
        $senderId = $this->getAuthService()->getId();
        $receiverId = $this->request->getPost('receiver_id');
        $message = $this->request->getPost('message');

        // If Blocking module is available, you can also check if user is blocked
        // Before sending a message

        $this->getModuleService('messageService')->sendMessage($senderId, $receiverId, $message);
        return 1;
    }

    /**
     * Loads a dialog between two users
     * 
     * @param int $receiverId
     * @return string
     */
    public function dialogAction($receiverId)
    {
        $senderId = $this->getAuthService()->getId();
        $dialog = $this->getModuleService('messageService')->fetchDialog($receiverId, $senderId);

        // Get receivers of current user
        $receivers = $this->getModuleService('messageService')->fetchReceivers($this->getAuthService()->getId());

        //return $this->json($dialog);
        return $this->view->render('profile/chat', array(
            'receivers' => $receivers,
            'dialog' => $dialog
        ));
    }

    /**
     * Clears a dialog with another user
     * 
     * @return string
     */
    public function clearAction()
    {
        $senderId = $this->getAuthService()->getId();
        $receiverId = $this->request->getPost('receiver_id');

        $this->getModuleService('messageService')->deleteDialog($senderId, $receiverId);

        return 1;
    }
}
