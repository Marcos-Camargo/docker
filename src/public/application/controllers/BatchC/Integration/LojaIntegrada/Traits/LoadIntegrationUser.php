<?php


trait LoadIntegrationUser
{

    protected function loadApiIntegrationUserByStore($store)
    {
        $integration = $this->model_api_integrations->getDataByStore($store['id']);
        $integration = $integration[0];
        $user = $this->model_users->getUserData($integration['user_id']);
        if ($user['store_id'] != $store['id']) {
            $storeUsers = $this->model_users->getUsersByStore($store['id']);
            foreach ($storeUsers as $storeUser) {
                if (((int)$storeUser['active']) === 1) {
                    $user = $storeUser;
                    break;
                }
            }
        }
        return $user;
    }

}