<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\controllers;

use app\models as Model;
use Ocrend\Kernel\Controllers\Controllers;
use Ocrend\Kernel\Controllers\IControllers;
use Ocrend\Kernel\Router\IRouter;

/**
 * Controlador home and login/
 *
 * @author Xapps.link <martin@xapps.link>
 */

class homeController extends Controllers implements IControllers
{

    public function __construct(IRouter $router)
    {
        parent::__construct($router);

        # Home Admin Asis
        if (count($this->user) == 0) {
            $this->template->display('overall/login');
        } else {

            $m = new Model\Menu;
            $this->template->display('dashboard/' . $this->user['rol_name'] . '/dashboard', array(
                'menu' => $m->getMenu($this->controller, $this->method),
            ));
        }

    }
}
