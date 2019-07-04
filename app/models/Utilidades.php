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
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo Backoffice
 */

class Utilidades extends Models implements IModels
{
    use DBModel;

    private $user_backoffice = null;

    public function accesControl()
    {

        $user = new Model\Users;

        $this->user_backoffice = $user->getOwnerUser_Backoffice();

    }

    public function loadPage()
    {

        try {

            $this->accesControl();

            switch ($this->user_backoffice['rol']) {
                # Rol administrador
                case 1:
                    return $this->load_Page_Adminstradores();
                    break;

                # Rol Gestionador
                case 2:
                    return $this->load_Page_Gestionadores();
                    break;

                # Rol Monitoreo
                case 3:
                    return $this->load_Page_Monitoreo();
                    break;

                default:
                    return $this->load_Page_Usuarios();
                    break;
            }

        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());

        }

    }

    private function load_Page_Adminstradores()
    {

        try {

            global $config, $http;

            $routing = explode('/', $http->query->get('routing'));

            switch ($routing[0]) {

                case 'backoffice':

                    switch ($routing[1]) {

                        # Vista reportes de  Backoffice de todos los procesos
                        case 'mis-procesos':

                            return array(
                                'page'       => 'mis-procesos',
                                'controller' => ucwords($routing[0]),
                                'title'      => 'Mis Procesos',
                                'menu'       => $this->getMenu(),
                            );

                            break;

                        # Vista reportes de  Backoffice de todos los procesos
                        case 'nuevo-proceso':

                            return array(
                                'page'       => 'backoffice',
                                'controller' => ucwords($routing[0]),
                                'title'      => 'Nuevo Proceso',
                                'menu'       => $this->getMenu(),
                            );

                            break;

                        case '':

                            # Vista procesos de Backoffice de todos los procesos
                            return array(
                                'page'       => 'backoffice',
                                'controller' => ucwords($routing[0]),
                                'title'      => 'Todos los Procesos',
                                'menu'       => $this->getMenu(),
                            );

                            break;

                        # Backoffice metromaternidad
                        case 'metromaternidad':

                            $menu = new Model\Metromaternidad;

                            switch ($routing[2]) {

                                case '':
                                    # Vista inbox metromaternidad
                                    return array(
                                        'page'       => 'procesos/metromaternidad/metromaternidad',
                                        'controller' => ucwords($routing[0]),
                                        'title'      => 'Inbox',
                                        'menu'       => $menu->getMenu($this->user_backoffice['rol']),
                                    );

                                    break;

                                case 'reportes':
                                    # Vista inbox metromaternidad
                                    return array(
                                        'page'       => 'procesos/metromaternidad/mis-reportes',
                                        'controller' => ucwords($routing[0]),
                                        'title'      => 'Mis Reportes',
                                        'menu'       => $menu->getMenu($this->user_backoffice['rol']),
                                    );

                                    break;

                                case 'configuracion':
                                    # Cinfuguracion proceso metromaternidad
                                    return array(
                                        'page'       => 'procesos/metromaternidad/mis-reportes',
                                        'controller' => ucwords($routing[0]),
                                        'title'      => 'Configuración',
                                        'menu'       => $menu->getMenu($this->user_backoffice['rol']),
                                    );

                                    break;

                                default:
                                    Helper\Functions::redir($config['build']['url'] . 'backoffice/metromaternidad/');
                                    break;
                            }

                            break;

                        default:

                            Helper\Functions::redir($config['build']['url'] . 'backoffice/');

                            break;
                    }

                    break;

                default:

                    Helper\Functions::redir($config['build']['url'] . 'backoffice/');

                    break;
            }

        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());

        }

    }

    private function load_Page_Gestionadores()
    {

        try {

            global $config, $http;

            $routing = explode('/', $http->query->get('routing'));

            switch ($routing[0]) {

                case 'backoffice':

                    switch ($routing[1]) {

                        # Vista reportes de  Backoffice de todos los procesos
                        case 'mis-procesos':

                            return array(
                                'page'       => 'mis-procesos',
                                'controller' => ucwords($routing[0]),
                                'title'      => 'Mis Procesos',
                                'menu'       => $this->getMenu(),
                            );

                            break;

                        # Backoffice metromaternidad
                        case 'metromaternidad':

                            $menu = new Model\Metromaternidad;

                            switch ($routing[2]) {

                                case '':
                                    # Vista inbox metromaternidad
                                    return array(
                                        'page'       => 'procesos/metromaternidad/metromaternidad',
                                        'controller' => ucwords($routing[0]),
                                        'title'      => 'Inbox',
                                        'menu'       => $menu->getMenu($this->user_backoffice['rol']),
                                    );

                                    break;

                                case 'mis-reportes':
                                    # Vista inbox metromaternidad
                                    return array(
                                        'page'       => 'procesos/metromaternidad/mis-reportes',
                                        'controller' => ucwords($routing[0]),
                                        'title'      => 'Mis Reportes',
                                        'menu'       => $menu->getMenu($this->user_backoffice['rol']),
                                    );

                                    break;

                                default:
                                    Helper\Functions::redir($config['build']['url'] . 'backoffice/metromaternidad/');
                                    break;
                            }

                            break;

                        default:

                            Helper\Functions::redir($config['build']['url'] . 'backoffice/mis-procesos');

                            break;
                    }

                    break;

                default:

                    Helper\Functions::redir($config['build']['url'] . 'backoffice/');

                    break;
            }

        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());

        }

    }

    private function load_Page_Monitoreo()
    {

        try {

            global $config, $http;

            $routing = explode('/', $http->query->get('routing'));

            switch ($routing[0]) {

                case 'backoffice':

                    switch ($routing[1]) {

                        # Vista reportes de  Backoffice de todos los procesos
                        case '':

                            return array(
                                'page'       => 'backoffice',
                                'controller' => ucwords($routing[0]),
                                'title'      => 'Monitoreo de Procesos',
                                'menu'       => $this->getMenu(),
                            );

                            break;

                        # Reporte metromaternidad
                        case 'metromaternidad':

                            $menu = new Model\Metromaternidad;

                            switch ($routing[2]) {

                                case 'reportes':
                                    # Vista inbox metromaternidad
                                    return array(
                                        'page'       => 'procesos/metromaternidad/reportes',
                                        'controller' => ucwords($routing[0]),
                                        'title'      => 'Reporte Metromaternidad',
                                        'menu'       => $menu->getMenu($this->user_backoffice['rol']),
                                    );

                                    break;

                                default:
                                    Helper\Functions::redir($config['build']['url'] . 'backoffice/metromaternidad/reportes');
                                    break;
                            }

                            break;

                        # Reporte cupones web
                        case 'cupones-web':

                            $menu = new Model\Metromaternidad;

                            switch ($routing[2]) {

                                case 'reportes':
                                    # Vista inbox metromaternidad
                                    return array(
                                        'page'       => 'procesos/cuponesweb/reportes',
                                        'controller' => ucwords($routing[0]),
                                        'title'      => 'Reporte Cupones Web',
                                        'menu'       => $menu->getMenu($this->user_backoffice['rol']),
                                    );

                                    break;

                                default:
                                    Helper\Functions::redir($config['build']['url'] . 'backoffice/metromaternidad/reportes');
                                    break;
                            }

                            break;

                        default:

                            Helper\Functions::redir($config['build']['url'] . 'backoffice/');

                            break;
                    }

                    break;

                default:

                    Helper\Functions::redir($config['build']['url'] . 'backoffice/');

                    break;
            }

        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());

        }

    }

    public function getRequest()
    {

        try {

            $this->accesControl();

            global $http;

            $request = strtolower($http->request->get('request'));

            switch ($request) {

                case 'full-procesos':
                    return $this->fullProcesos();
                    break;

                case 'mis-procesos':
                    return $this->misProcesos();
                    break;

                case 'reporte-cupones-web':

                    $reporte = new Model\Cupones;
                    return $reporte->getReporteCupones();
                    break;

                default:
                    throw new ModelsException('No existe un proceso definido');
                    break;
            }

        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());

        }

    }

    public function fullProcesos()
    {
        $id_box = $this->user_backoffice['rol'];

        $query = $this->db->select("
            JSON_UNQUOTE(data->'$.proceso') AS proceso,
            JSON_UNQUOTE(data->'$.status') AS status,
            JSON_UNQUOTE(data->'$.type') AS type
 ", 'procesos_backoffice', null);

        if (false == $query) {

            return array(
                'customData' => false,
            );

        }

        return array(
            'customData' => $query,
        );

    }

    public function misProcesos()
    {

        $query = $this->db->select("
            JSON_UNQUOTE(data->'$.proceso') AS proceso,
            JSON_UNQUOTE(data->'$.status') AS status,
            JSON_UNQUOTE(data->'$.type') AS type", 'procesos_backoffice', null);

        if (false == $query) {

            return array(
                'customData' => false,
            );

        }

        $filter_procesos = array();

        $misProcesos = json_decode($this->user_backoffice['backoffice'], true);

        foreach ($query as $key) {

            if (in_array($key['proceso'], $misProcesos)) {
                $filter_procesos[] = $key;
            }
        }

        return array(
            'customData' => $filter_procesos,
        );

    }

    public function nuevaOrden()
    {
        try {
            global $http;

            $id_paquete  = $http->request->get('paquete');
            $store       = $http->request->get('store');
            $comentarios = $http->request->get('comentarios');

            # Si es otra store
            if ($store == 'OTRA STORE') {
                $http->request->set('store', strtoupper($http->request->get('otra-store')));
                $http->request->remove('otra-store');
            } else {
                $http->request->set('store', $http->request->get('store'));

            }

            # Si alguien retira mi parquete por mi
            $retira = $http->request->get('retira');

            if ($retira == 'SI') {
                $http->request->set('retira', strtoupper($http->request->get('otro-retira')));
                $http->request->remove('otro-retira');
            } else {
                $http->request->set('retira', $this->user_rol['id']);
            }

            # Setear fecha de llegada del pauqete
            $dateOpen = strtotime($http->request->get('date-open'));

            if (Helper\Functions::e($store, $retira, $id_paquete, $dateOpen)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            if (Helper\Functions::e($comentarios)) {
                $http->request->set('comentarios', 'Sin Comentarios');
            }

            if ($http->request->get('date-open') < date('d-m-Y')) {
                throw new ModelsException('La fecha de llegada del paquete a mi BOX no puede ser menor a la fecha actual.');
            }

            # VALIDACION PAQUETE YA ESTA REGISTRADO
            $this->existedPackage();

            # Valores a descontar

            $estados['Registrado'] = time();

            $http->request->set('date_open', $dateOpen);
            $http->request->set('id_paquete', $id_paquete);
            $http->request->set('id_box', $this->user_rol['id']);
            $http->request->set('log_estados', $estados);

            # Eliminar variables antes de insertar en bbd
            $http->request->remove('date-open');
            $http->request->remove('paquete');

            # Insertar datos de orden y paquete
            $insert_nueva_orden = $this->db->insert('box_transactions', array(
                'data' => json_encode($http->request->all(), JSON_UNESCAPED_UNICODE),
            ));

            # SETEAR PARA VISTA
            $http->request->set('estado', 'Registrado');

            return array(
                'success' => true,
                'message' => 'Registrado con éxito.',
                'data'    => $http->request->all(),
            );

        } catch (ModelsException $e) {

            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => $http->request->all(),
            );
        }
    }

    public function getMenu()
    {

        switch ($this->user_backoffice['rol']) {
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
                return $this->menuAdminstradores();
                break;
        }

    }

    public function menuAdminstradores()
    {
        global $config, $http;

        $menu = array(
            'nuevo-proceso' => 'Nuevo Proceso',
            'mis-procesos'  => 'Mis Procesos',
            ''              => 'Todos los Procesos',

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

    public function menuMonitoreo()
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

        $menu = Helper\Sections::loadTemplate(array('{{name}}' => 'Backoffice', '{{li}}' => $li), 'user_menu.html');

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
