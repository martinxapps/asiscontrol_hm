<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use app\models as Model;

$app->get('/', function () use ($app) {
    return $app->json(array());
});

$app->get('/usuarios', function () use ($app) {
    $u = new Model\Users;
    return $app->json($u->getUsers());
});

/**
 * Modulo Habitaciones
 *
 * @return json
 */
$app->get('/habitaciones', function () use ($app) {
    $u = new Model\Habitaciones;
    return $app->json($u->getHabitaciones());
});
