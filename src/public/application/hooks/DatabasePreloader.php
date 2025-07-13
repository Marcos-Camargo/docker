<?php
class DatabasePreloader {

    public static $readonlyRoutes = [
    ];

    public function init()
    {
        // Código abaixo precisa carregar as configs base
        require_once(APPPATH.'config/database.php');

        if ($this->currentRouteIsReadonly() && isset($db['readonly'])) {
            $active_group = 'readonly';
        }

        // Define as variáveis globais do CI com base no grupo escolhido
        require_once(BASEPATH.'database/DB.php');
        $GLOBALS['CI_DB'] = &DB($db[$active_group], TRUE);
    }

    protected function currentRouteIsReadonly(): bool
    {
        if (empty(self::$readonlyRoutes)) {
            return false;
        } // default: nada é readonly

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uriPath = trim(parse_url($uri, PHP_URL_PATH), '/');

        foreach (self::$readonlyRoutes as $rota) {
            if (stripos($uriPath, $rota) === 0) {
                return true;
            }
        }

        return false;
    }

}
