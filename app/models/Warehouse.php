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
 * Modelo Warehouse
 */

class Warehouse extends Models implements IModels
{
    use DBModel;

    private $user_rol = null;

    public function accesControl()
    {
        global $config;

        $user = new Model\Users;

        $this->user_rol = $user->getOwnerUser();

        $permissions = strpos('Warehouse', $this->user_rol['permissions']);

        # Confirmar accesso al modulo Ordenes

        if ($this->user_rol['rol'] > 2 or $permissions) {
            # code...
            Helper\Functions::redir($config['build']['url']);

        }

    }

    public function getRequest()
    {

        try {

            $this->accesControl();

            global $http;

            $request = strtolower($http->request->get('request'));

            switch ($request) {

                case 'nueva_bodega':
                    return $this->nuevaBodega();
                    break;

                case 'full-bodegas':
                    return $this->fullBodegas();
                    break;

                case 'select':
                    return $this->loadSelect();
                    break;

                default:
                    throw new ModelsException('No existe un proceso definido');

                    break;
            }

        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());

        }

    }

    /**
     * Realiza la acción de una nueva orden de pauqete en el sistema
     *
     * @return array : Con información de éxito/falla al registrar nueva orden de pauqete en el sistema
     */
    private function loadSelect()
    {

        $query = $this->db->select("
            id,
            data->'$.name' AS warehouse
            ", 'box_warehouses', null);

        if (false == $query) {

            return array(
                'customData' => false,
            );
            # code...
        }

        return array(
            'customData' => $query,
        );

    }

    /**
     * Realiza la acción de una nueva orden de pauqete en el sistema
     *
     * @return array : Con información de éxito/falla al registrar nueva orden de pauqete en el sistema
     */
    private function misOrdenes()
    {
        $id_box = $this->user_rol['id'];

        $query = $this->db->select("
            data->'$.id_paquete' AS id_paquete,
            data->'$.id_box' AS id_box,
            FROM_UNIXTIME(data->'$.date_open') AS entrega,
            data->'$.store' AS store,
            data->'$.log_estados' AS estados

            ", 'box_transactions', null, "data->'$.id_box'='$id_box' ");

        if (false == $query) {

            return array(
                'customData' => false,
            );
            # code...
        }

        $filter_query = array();

        foreach ($query as $key) {
            $ultimo_estado  = json_decode($key['estados'], true);
            $key['estado']  = end($ultimo_estado);
            $filter_query[] = $key;
        }

        return array(
            'customData' => $filter_query,
        );

    }

    /**
     * Realiza la acción de una nueva orden de pauqete en el sistema
     *
     * @return array : Con información de éxito/falla al registrar nueva orden de pauqete en el sistema
     */
    private function fullBodegas()
    {

        $query = $this->db->select("
            id,
            data->'$.name' AS name,
            data->'$.dir' AS dir,
            data->'$.city' AS city,
            data->'$.type' AS type,
            data->'$.warehouse' AS warehouse

            ", 'box_warehouses', null);

        if (false == $query) {

            return array(
                'customData' => false,
            );
        }

        $filter_query = array();

        foreach ($query as $key) {

            $warehouse = json_decode($key['warehouse'], true);

            if (count($warehouse) == 0) {
                $key['warehouse'] = 0;
            }

            $filter_query[] = $key;
        }

        return array(
            'customData' => $filter_query,
        );

    }

    /**
     * Realiza la acción de una nueva orden de pauqete en el sistema
     *
     * @return array : Con información de éxito/falla al registrar nueva orden de pauqete en el sistema
     */
    private function nuevaBodega()
    {
        try {
            global $http;

            $name = $http->request->get('name');
            $dir  = $http->request->get('dir');
            $city = $http->request->get('city');
            $tipo = $http->request->get('type');

            if (Helper\Functions::e($name, $dir, $city, $tipo)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            $warehouse['stands'] = '';

            $http->request->set('warehouse', array());
            $http->request->set('status', 'active');

            $insert_nueva_bodega = $this->db->insert('box_warehouses', array(
                'data' => json_encode($http->request->all(), JSON_UNESCAPED_UNICODE),
            ));

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

    /**
     * Imprime el Menú del modulo Usuarios según su rol vista actual
     *
     *
     *  devuelve un html precargado
     *
     * @return string
     */
    public function getMenu()
    {

        switch ($this->user_rol['rol']) {
            case 1:
                return $this->menuAdminstradores();
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
    private function menuAdminstradores()
    {
        global $config, $http;

        $menu = array(
            'nueva-bodega' => 'Nueva Bodega',
            'ordenes'      => 'Todas los Ordenes',
            'bodegas'      => 'Todas los Bodegas',
        );

        $page = explode('/', $http->getPathinfo());
        # pagina vigente
        $page = end($page);

        $li = '';

        # Construir menu
        foreach ($menu as $key => $value) {

            if ($key == $page) {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'warehouse/' . $key), 'li-active.html');
            } else {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'warehouse/' . $key), 'li.html');

            }

        }

        $menu = Helper\Sections::loadTemplate(array('{{name}}' => 'Warehouse', '{{li}}' => $li), 'user_menu.html');

        return $menu;
    }

    private function random_str($length, $keyspace = 'abcdefghjklmnprstuwxyzABCDEFGHJKLMNPRSTUWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
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
