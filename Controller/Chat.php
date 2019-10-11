<?php

namespace Chat\Controller;

use Site\Controller\AbstractSiteController;
use Krystal\Stdlib\VirtualEntity;

final class Chat extends AbstractSiteController
{
    /**
     * Renders chat page
     * 
     * @param array $vars Page variables
     * @return string
     */
    private function renderChat(array $vars)
    {
        // Append assets
        $this->view->getPluginBag()->appendStylesheet('@Chat/chat-style.css?v=' . time())
                                   ->appendLastScript('@Chat/chat-handler.js?v=' . time());

        return $this->view->render('profile/chat', $vars);
    }

    /**
     * Renders data grid
     * 
     * @return string
     */
    public function indexAction()
    {
        // Get receivers of current user
        $receivers = $this->getModuleService('messageService')->fetchReceivers($this->getAuthService()->getId());

        return $this->renderChat(array(
            'receivers' => $receivers
        ));
    }

    /**
     * Deletes a dialog
     * 
     * @param int $receiverId
     * @return string
     */
    public function deleteAction($receiverId)
    {
        $this->getModuleService('messageService')->deleteDialog($this->getAuthService()->getId(), $receiverId);

        $this->flashBag->set('success', 'Selected dialog has been deleted successfully');
        return $this->response->back();
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
        $receiver = $this->getAuthService()->findById($receiverId);

        if ($receiver) {
            $msgServ = $this->getModuleService('messageService');

            $senderId = $this->getAuthService()->getId();
            $dialog = $msgServ->fetchDialog($receiverId, $senderId);

            // Get receivers of current user
            $receivers = $msgServ->fetchReceivers($senderId);

            $output = $this->renderChat(array(
                'receivers' => $receivers,
                'dialog' => $dialog,
                'receiverId' => $receiverId,
                'current' => $receiver
            ));

            // Mark new messages as read
            $msgServ->markAsRead($receiverId, $senderId);

            return $output;

        } else {
            // Invalid id
            return false;
        }
    }
}
