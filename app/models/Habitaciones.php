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
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Models\Traits\DBModel;
use Ocrend\Kernel\Router\IRouter;

/**
 * Modelo Habitaciones
 */

class Habitaciones extends Models implements IModels
{
    use DBModel;

    private $user = null;

    private function set()
    {
        global $http;

        $this->user = (new Model\Users)->getOwnerUser();

        # verificar permisos y roles
        if ($this->user['rol'] > 1) {
            throw new ModelsException('No tienes suficientes provilegios para esta información.');
        }

    }

    public function menu($modulo = 'Habitaciones')
    {
        global $config, $http;

        # inicailizar permisos y roles
        $this->set();

        $menu = array(
            '' => array(
                'url'  => $config['build']['url'] . strtolower($modulo),
                'name' => 'Ver Habitaciones',
                'rol'  => 1,
            ),
        );

        # setear opciones por rol

        $_menu = array();

        foreach ($menu as $key => $value) {

            if ($value['rol'] <= $this->user['rol']) {
                $_menu[] = $value;
            }
        }

        return $_menu;
    }

    /**
     * Get habitaciones

    retorna valores sobre la tabla de habitacione sy sus estados

    numero de habitacion => 01
    paciente => '',
    status => conectado, navegando,
    timestamp ultima sesion => fecha


     */

    public function getHabitaciones()
    {
        # inicailizar permisos y roles
        $this->set();

        $habitaciones = $this->db->select('*', 'habitaciones');

        return array('customData' => $habitaciones);

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
