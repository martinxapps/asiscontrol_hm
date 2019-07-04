<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\models;

use app\models as Model;
use Ocrend\Kernel\Helpers as Helper;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo Metromaternidad
 */

class Metromaternidad extends Models implements IModels
{
    use DBModel;

    private $user_backoffice = null;

    /**
     * Imprime el Menú del modulo Usuarios según su rol vista actual
     *
     *
     *  devuelve un html precargado
     *
     * @return string
     */
    public function getMenu($id_user)
    {

        switch ($id_user) {
            # Rol administrador
            case 1:
                return $this->menuAdminstradores();
                break;

            # Rol gestionador
            case 2:
                return $this->menuGestionadores();
                break;

            # Rol gestionador
            case 3:
                return $this->menuMonitoreo();
                break;

            default:
                return $this->menuAdminstradores();
                break;
        }

    }

    /**
     * Imprime el Menú del modulo Usuarios según su rol vista actual
     *
     *
     *  devuelve un html precargado
     *
     * @return string
     */
    public function menuAdminstradores()
    {
        global $config, $http;

        $menu = array(
            ''              => 'Inbox',
            'reportes'      => 'Mis Reportes',
            'configuracion' => 'Configuración',

        );

        $page = explode('/', $http->getPathinfo());
        # pagina vigente
        $page = end($page);

        $li = '';

        # Construir menu
        foreach ($menu as $key => $value) {

            if ($key == $page) {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/metromaternidad/' . $key), 'li-active.html');
            } else {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/metromaternidad/' . $key), 'li.html');

            }

        }

        $menu = Helper\Sections::loadTemplate(array('{{name}}' => 'Metromaternidad', '{{li}}' => $li), 'user_menu.html');

        return $menu;
    }

    public function menuGestionadores()
    {
        global $config, $http;

        $menu = array(
            ''             => 'Inbox',
            'mis-reportes' => 'Mis Reportes',

        );

        $page = explode('/', $http->getPathinfo());
        # pagina vigente
        $page = end($page);

        $li = '';

        # Construir menu
        foreach ($menu as $key => $value) {

            if ($key == $page) {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/metromaternidad/' . $key), 'li-active.html');
            } else {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/metromaternidad/' . $key), 'li.html');

            }

        }

        $menu = Helper\Sections::loadTemplate(array('{{name}}' => 'Metromaternidad', '{{li}}' => $li), 'user_menu.html');

        return $menu;
    }

    public function menuMonitoreo()
    {
        global $config, $http;

        $menu = array(
            'reportes' => 'Reporte',

        );

        $page = explode('/', $http->getPathinfo());
        # pagina vigente
        $page = end($page);

        $li = '';

        # Construir menu
        foreach ($menu as $key => $value) {

            if ($key == $page) {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/metromaternidad/' . $key), 'li-active.html');
            } else {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/metromaternidad/' . $key), 'li.html');

            }

        }

        $menu = Helper\Sections::loadTemplate(array('{{name}}' => 'Metromaternidad', '{{li}}' => $li), 'user_menu.html');

        return $menu;
    }

    /**
     * __construct()
     */

    public function __construct(IRouter $router = null)
    {
        parent::__construct($router);
        $this->startDBConexion();
    }
}
