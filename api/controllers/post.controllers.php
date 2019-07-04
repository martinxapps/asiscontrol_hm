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

/**
 * Inicio de sesión
 *
 * @return json
 */
$app->post('/login', function () use ($app) {
    $u = new Model\Users;
    return $app->json($u->login());
});

/**
 * Registro de un usuario
 *
 * @return json
 */
$app->post('/registro', function () use ($app) {
    $u = new Model\Users;
    return $app->json($u->register());
});

/**
 * Recuperar contraseña perdida
 *
 * @return json
 */
$app->post('/lostpass', function () use ($app) {
    $u = new Model\Users;
    return $app->json($u->lostpass());
});

/**
 * Modulo Habitaciones
 *
 * @return json
 */
$app->post('/habitaciones', function () use ($app) {
    $u = new Model\Habitaciones;
    return $app->json($u->getHabitaciones());
});
