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
 * Modelo Menu

Renderiza el menu segun los modulos del usuario

 */

class Menu extends Models implements IModels
{
    use DBModel;

    private $user = null;

    public function getMenu($controlador, $metodo)
    {

        $this->controller = $controlador;
        $this->metodo     = $metodo;

        $this->user = (new Model\Users)->getOwnerUser();

        switch ($this->user['rol']) {
            # Rol administrador
            case 1:
                return $this->menuAdminstradores();
                break;

            # Rol Gestionadores
            case 2:
                return $this->menuGestionadores();
                break;

            # Rol Monitoreo
            case 3:
                return $this->menuMonitoreo();
                break;

            default:
                return $this->menuUsuarios();
                break;
        }

    }

    private function icons($key = '')
    {
        switch ($key) {

            case 'Habitaciones':
                return 'icon-h-square';
                break;

            case 'Usuarios':
                return 'icon-users';
                break;

            default:
                return 'icon-object-group';
                break;
        }
    }

    public function menuAdminstradores()
    {
        global $config, $http;

        $modules = explode(',', $this->user['permissions']);

        $menu = '';

        $page = explode('/', $http->getPathinfo());

        foreach ($modules as $key => $value) {

            if ($key == ucwords($this->controller) && $this->controller != 'home') {

                $menu .= Helper\Sections::loadTemplate(
                    array(
                        '{{name}}'   => str_replace("-", " ", $value),
                        '{{li}}'     => $this->getLiMenu($value),
                        '{{active}}' => 'open active',
                        '{{icon}}'   => $this->icons($value),
                    ),
                    'user_menu.html'
                );

            } else {

                $menu .= Helper\Sections::loadTemplate(
                    array(
                        '{{name}}'   => str_replace("-", " ", $value),
                        '{{li}}'     => $this->getLiMenu($value),
                        '{{active}}' => '',
                        '{{icon}}'   => $this->icons($value),
                    ),
                    'user_menu.html'
                );

            }

        }

        return $menu;
    }

    private function getLiMenu($modulo)
    {

        global $config, $http;

        $modules = $this->getModulo($modulo);

        $page = explode('/', $http->getPathinfo());

        $li = '';

        # Construir menu
        foreach ($modules as $key => $value) {

            if ($key == $this->metodo && $this->controller != 'home' && $this->controller == strtolower($modulo)) {

                $li .= Helper\Sections::loadTemplate(
                    array(
                        '{{name}}' => $value['name'],
                        '{{url}}'  => $value['url'],
                    ),
                    'li-active.html'
                );

            } else {

                $li .= Helper\Sections::loadTemplate(
                    array(
                        '{{name}}' => $value['name'],
                        '{{url}}'  => $value['url'],
                    ),
                    'li.html'
                );

            }

        }

        return $li;
    }

    private function getModulo($modulo)
    {

        global $config, $http;

        switch ($modulo) {

            case 'Habitaciones':

                return (new Model\Habitaciones)->menu();

                break;

            case 'Usuarios':

                return array(
                    '' => array(
                        'url'  => $config['build']['url'] . strtolower($modulo),
                        'name' => 'Ver Usuarios',

                    ),
                );

                break;

            default:

                return array(
                    'habitaciones' => array(
                        'url'  => $config['build']['url'] . strtolower($modulo),
                        'name' => 'Ver Habitaciones',
                    ),
                    'bloquedas'    => array(
                        'url'  => $config['build']['url'] . strtolower($modulo) . '/bloquedas',
                        'name' => 'Habitaciones bloquedas',
                    ),
                );

                break;
        }

    }

    public function menuGestionadores()
    {
        global $config, $http;

        $menu = array(
            'mis-procesos' => 'Mis Procesos',

        );

        $page = explode('/', $http->getPathinfo());
        # pagina vigente
        $page = end($page);

        $li = '';

        # Construir menu
        foreach ($menu as $key => $value) {

            if ($key == $page) {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/' . $key), 'li-active.html');
            } else {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/' . $key), 'li.html');

            }

        }

        $menu = Helper\Sections::loadTemplate(array('{{name}}' => 'Backoffice', '{{li}}' => $li), 'user_menu.html');

        return $menu;
    }

    public function menuUsuarios()
    {
        global $config, $http;

        $menu = array(
            '' => 'Todos los Procesos',

        );

        $page = explode('/', $http->getPathinfo());
        # pagina vigente
        $page = end($page);

        $li = '';

        # Construir menu
        foreach ($menu as $key => $value) {

            if ($key == $page) {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/' . $key), 'li-active.html');
            } else {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'backoffice/' . $key), 'li.html');

            }

        }

        $menu = Helper\Sections::loadTemplate(array('{{name}}' => 'Habitaciones', '{{li}}' => $li), 'user_menu.html');

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
