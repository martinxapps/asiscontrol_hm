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
 * Modelo Users
 */
class Users extends Models implements IModels
{
    use DBModel;

    /**
     * Máximos intentos de inincio de sesión de un usuario
     *
     * @var int
     */
    const MAX_ATTEMPTS = 5;

    /**
     * Tiempo entre máximos intentos en segundos
     *
     * @var int
     */
    const MAX_ATTEMPTS_TIME = 120; # (dos minutos)

    /**
     * Log de intentos recientes con la forma 'email' => (int) intentos
     *
     * @var array
     */
    private $recentAttempts = array();

    /**
     * Hace un set() a la sesión login_user_recentAttempts con el valor actualizado.
     *
     * @return void
     */
    private function updateSessionAttempts()
    {
        global $session;

        $session->set('login_user_recentAttempts', $this->recentAttempts);
    }

    /**
     * Revisa si las contraseñas son iguales
     *
     * @param string $pass : Contraseña sin encriptar
     * @param string $pass_repeat : Contraseña repetida sin encriptar
     *
     * @throws ModelsException cuando las contraseñas no coinciden
     */
    private function checkPassMatch(string $pass, string $pass_repeat)
    {
        if ($pass != $pass_repeat) {
            throw new ModelsException('Las contraseñas no coinciden.');
        }
    }

    /**
     * Verifica el email introducido, tanto el formato como su existencia en el sistema
     *
     * @param string $email: Email del usuario
     *
     * @throws ModelsException en caso de que no tenga formato válido o ya exista
     */
    private function checkEmail(string $email)
    {
        # Formato de email
        if (!Helper\Strings::is_email($email)) {
            throw new ModelsException('El email no tiene un formato válido.');
        }
        # Existencia de email
        $email = $this->db->scape($email);
        $query = $this->db->select('id', 'users', null, "JSON_UNQUOTE(data->'$.email')='$email'", 1);
        if (false !== $query) {
            throw new ModelsException('El email ingresado ya existe.');
        }
    }

    /**
     * Restaura los intentos de un usuario al iniciar sesión
     *
     * @param string $email: Email del usuario a restaurar
     *
     * @throws ModelsException cuando hay un error de lógica utilizando este método
     * @return void
     */
    private function restoreAttempts(string $email)
    {
        if (array_key_exists($email, $this->recentAttempts)) {
            $this->recentAttempts[$email]['attempts'] = 0;
            $this->recentAttempts[$email]['time']     = null;
            $this->updateSessionAttempts();
        } else {
            throw new ModelsException('Error lógico');
        }
    }

    /**
     * Genera la sesión con el id del usuario que ha iniciado
     *
     * @param array $user_data: Arreglo con información de la base de datos, del usuario
     *
     * @return void
     */
    private function generateSession(array $user_data)
    {
        global $session, $cookie, $config;

        # Generar un session hash
        $cookie->set('session_hash', md5(time()), $config['sessions']['user_cookie']['lifetime']);

        # Generar la sesión del usuario
        $session->set($cookie->get('session_hash') . '__user_id', (int) $user_data['id_user']);

        # Generar data encriptada para prolongar la sesión
        if ($config['sessions']['user_cookie']['enable']) {
            # Generar id encriptado
            $encrypt = Helper\Strings::ocrend_encode($user_data['id_user'], $config['sessions']['user_cookie']['key_encrypt']);

            # Generar cookies para prolongar la vida de la sesión
            $cookie->set('appsalt', Helper\Strings::hash($encrypt), $config['sessions']['user_cookie']['lifetime']);
            $cookie->set('appencrypt', $encrypt, $config['sessions']['user_cookie']['lifetime']);
        }
    }

    /**
     * Verifica en la base de datos, el email y contraseña ingresados por el usuario
     *
     * @param string $email: Email del usuario que intenta el login
     * @param string $pass: Contraseña sin encriptar del usuario que intenta el login
     *
     * @return bool true: Cuando el inicio de sesión es correcto
     *              false: Cuando el inicio de sesión no es correcto
     */
    private function authentication(string $user, string $pass): bool
    {
        $user  = $this->db->scape($user);
        $query = $this->db->select("
            id,JSON_UNQUOTE(data->'$.user') as user,
            JSON_UNQUOTE(data->'$.pass') as pass ",
            'users',
            null,
            " data->'$.user'='$user' or data->'$.email'='$user' ",
            1
        );

        # Incio de sesión con éxito
        if (false !== $query && Helper\Strings::chash($query[0]['pass'], $pass)) {

            # Restaurar intentos
            $this->restoreAttempts($user);

            # Generar la sesión
            $query[0]['id_user'] = $query[0]['id'];

            $this->generateSession($query[0]);
            return true;
        }

        return false;
    }

    /**
     * Establece los intentos recientes desde la variable de sesión acumulativa
     *
     * @return void
     */
    private function setDefaultAttempts()
    {
        global $session;

        if (null != $session->get('login_user_recentAttempts')) {
            $this->recentAttempts = $session->get('login_user_recentAttempts');
        }
    }

    /**
     * Establece el intento del usuario actual o incrementa su cantidad si ya existe
     *
     * @param string $email: Email del usuario
     *
     * @return void
     */
    private function setNewAttempt(string $email)
    {
        if (!array_key_exists($email, $this->recentAttempts)) {
            $this->recentAttempts[$email] = array(
                'attempts' => 0, # Intentos
                'time'     => null, # Tiempo
            );
        }

        $this->recentAttempts[$email]['attempts']++;
        $this->updateSessionAttempts();
    }

    /**
     * Controla la cantidad de intentos permitidos máximos por usuario, si llega al límite,
     * el usuario podrá seguir intentando en self::MAX_ATTEMPTS_TIME segundos.
     *
     * @param string $email: Email del usuario
     *
     * @throws ModelsException cuando ya ha excedido self::MAX_ATTEMPTS
     * @return void
     */
    private function maximumAttempts(string $email)
    {
        if ($this->recentAttempts[$email]['attempts'] >= self::MAX_ATTEMPTS) {

            # Colocar timestamp para recuperar más adelante la posibilidad de acceso
            if (null == $this->recentAttempts[$email]['time']) {
                $this->recentAttempts[$email]['time'] = time() + self::MAX_ATTEMPTS_TIME;
            }

            if (time() < $this->recentAttempts[$email]['time']) {
                # Setear sesión
                $this->updateSessionAttempts();
                # Lanzar excepción
                throw new ModelsException('Ya ha superado el límite de intentos para iniciar sesión.');
            } else {
                $this->restoreAttempts($email);
            }
        }
    }

    /**
     * Obtiene datos de un usuario según su id en la base de datos
     *
     * @param int $id: Id del usuario a obtener
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios
     *
     * @return false|array con información del usuario
     */
    public function getUserById(int $id, string $select = '*')
    {
        return $this->db->select($select, 'users', null, "id='$id'", 1);
    }

    /**
     * Obtiene a todos los usuarios
     *
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios
     *
     * @return false|array con información de los usuarios
     */
    public function getUsers(string $select = '*')
    {
        global $session;

        $users = $this->db->select($select, 'users', 'INNER JOIN roles ON users.rol = roles.role');

        # No hay resultados
        if (false == $users) {
            return array('customData' => false);
        }

        $filter_users = array();

        $NUM = 1;

        foreach ($users as $key => $val) {

            $data = json_decode($val['data'], true);

            $filter_users[] = array(
                'id_box'      => $NUM,
                'id_user'     => $val['id'],
                'user'        => $val['user'],
                'name'        => $val['name'],
                'email'       => $data['email'],
                'permissions' => $val['permissions'],

            );

            $NUM++;

        }

        return array(
            'customData' => $filter_users,
            # 'data'       => $users,
        );
    }

    /**
     * Obtiene datos del usuario conectado actualmente
     *
     * @param string $select : Por defecto es *, se usa para obtener sólo los parámetros necesarios
     *
     * @throws ModelsException si el usuario no está logeado
     * @return array con datos del usuario conectado
     */
    public function getOwnerUser(string $select = '*'): array
    {
        if (null !== $this->id_user) {

            $user = $this->db->select("id,
                JSON_UNQUOTE(data->'$.id_box') as id_box,
                JSON_UNQUOTE(data->'$.status') as status,
                JSON_UNQUOTE(data->'$.user') as name,
                JSON_UNQUOTE(data->'$.rol') as rol,
                JSON_UNQUOTE(data->'$.email') as email,
                JSON_UNQUOTE(data->'$.membresia') as membresia,
                JSON_UNQUOTE(data->'$.box_type') as box_type,
                data->'$.user_data' as user_data,
                data,
                JSON_UNQUOTE(data->'$.permissions') as permissions", 'users', null, "id='$this->id_user'", 1);

            $rol = $this->db->select($select, 'roles', null, "role='" . $user[0]['rol'] . "'", 1);

            $user[0]['rol_name'] = $rol[0]['name'];

            $user_data = json_decode($user[0]['user_data'], true);

            $user[0]['user_data'] = $user_data;

            $user_membresia  = filter_var($user[0]['membresia'], FILTER_VALIDATE_BOOLEAN);
            $user_active_box = filter_var($user[0]['box_type'], FILTER_VALIDATE_BOOLEAN);

            if ($user_membresia == true or $user_active_box == true) {
                $data                           = json_decode($user[0]['data'], true);
                $user[0]['fecha_vigencia_plan'] = Helper\Functions::fecha('l d ', $data['fecha_vigencia_plan']) . 'de ' . Helper\Functions::fecha('F', $data['fecha_vigencia_plan']) . ' del ' . Helper\Functions::fecha('Y', $data['fecha_vigencia_plan']);
            }

            # Si se borra al usuario desde la base de datos y sigue con la sesión activa
            if (false === $user) {
                $this->logout();
            }

            return $user[0];
        }

        throw new \RuntimeException('El usuario no está logeado.');
    }

    /**
     * Realiza la acción de login dentro del sistema
     *
     * @return array : Con información de éxito/falla al inicio de sesión.
     */
    public function login(): array
    {
        try {
            global $http;

            # Definir de nuevo el control de intentos
            $this->setDefaultAttempts();

            # Obtener los datos $_POST
            $user = strtolower($http->request->get('user'));
            $pass = $http->request->get('pass');

            # Verificar que no están vacíos
            if (Helper\Functions::e($user, $pass)) {
                throw new ModelsException('Credenciales incompletas.');
            }

            # Añadir intentos
            $this->setNewAttempt($user);

            # Verificar intentos
            $this->maximumAttempts($user);

            # Autentificar
            if ($this->authentication($user, $pass)) {
                return array('success' => true, 'message' => 'Conectado con éxito.');
            }

            throw new ModelsException('Credenciales incorrectas.');

        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Realiza la acción de registro dentro del sistema
     *
     * @return array : Con información de éxito/falla al registrar el usuario nuevo.
     */
    public function register(): array
    {
        try {
            global $http;

            # Obtener los datos $_POST
            $user        = $http->request->get('user');
            $email       = $http->request->get('email');
            $pass        = $http->request->get('pass');
            $pass_repeat = $http->request->get('2pass');

            # Verificar que no están vacíos
            if (Helper\Functions::e($user, $email, $pass, $pass_repeat)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Verificar email
            $this->checkEmail($email);

            # Veriricar contraseñas
            $this->checkPassMatch($pass, $pass_repeat);

            # Registrar al usuario
            $data = array(
                'user'        => $user,
                'email'       => $email,
                'pass'        => Helper\Strings::hash($pass),
                'rol'         => 1, # Rol por default para usuarios nuevos
                'status'      => true, # Rol por default para usuarios nuevos
                'permissions' => ['Metromaternidad', 'Inbox Digital'],
            );

            $id_user = $this->db->insert('users', array(
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ));

            # Iniciar sesión
            /*
            $this->generateSession(array(
            'id_user' => $id_user,
            ));
             */

            return array('success' => true, 'message' => 'Registrado con éxito. Espere la activación de su cuenta por parte del administrador del sistema.');
        } catch (ModelsException $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Realiza la acción de registro dentro del sistema
     *
     * @return array : Con información de éxito/falla al registrar el usuario nuevo.
     */
    public function register_back(): array
    {
        try {
            global $http;

            # Setear variables
            $http->request->set('user', strtolower($http->request->get('user')));

            # Obtener los datos $_POST
            $name        = $http->request->get('nomb');
            $ape         = $http->request->get('ape');
            $user        = $http->request->get('user');
            $pass        = $http->request->get('pass');
            $email       = strtolower($http->request->get('email'));
            $pass_repeat = $http->request->get('2pass');
            $rol         = ($http->request->get('rol') != null) ? $http->request->get('rol') : 4;
            $modulos     = ($http->request->get('modulos') != null) ? implode(',', $http->request->all()['modulos']) : '';

            # Verificar que no están vacíos
            if (Helper\Functions::e($name, $ape, $email, $pass, $pass_repeat, $rol, $modulos)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Veriricar contraseñas
            $this->checkPassMatch($pass, $pass_repeat);

            # Verificar email
            $this->checkEmail($email);

            # GENERAR ID DE USER
            $usuarios = $this->db->select('MAX(id) AS id_user', 'users');

            # id_user
            $id_user = $usuarios[0]['id_user'] + 1;

            # user box
            if ($user == '') {
                $user_box = 'box' . str_pad($id_user, 3, "0", STR_PAD_LEFT);
            } else {
                $user_box = $user;
            }

            # Dtaos de usuario
            $data = array(
                'user'        => $user_box,
                'email'       => $email,
                'rol'         => (int) $rol,
                'pass'        => Helper\Strings::hash($pass),
                'permissions' => $modulos,
                'box_type'    => false,
                'status'      => false,
                'membresia'   => false,
                'user_data'   => array(
                    'nombres'   => $name,
                    'apellidos' => $ape,
                ),
            );

            # Establecer y asigar los permisso sde los roles del sistema.
            foreach ($http->request->all()['modulos'] as $key) {
                $data[strtolower($key)] = [];
            }

            # Registrar al usuario
            $nuevo_user = $this->db->insert('users', array(
                'id'   => $id_user,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ));

            $data['id']     = $id_user;
            $data['id_box'] = $user_box;

            # Enviar correo de activacion
            $this->send_to_Active($data);

            return array(
                'success' => 1,
                'message' => 'Registrado con éxito. <br/>Hemos enviado un correo electrónico a: <b>' . $email . '</b>. Para activar la cuenta.');

        } catch (ModelsException $e) {

            return array(
                'success' => 0,
                'message' => $e->getMessage(),
                'data'    => $http->request->all(),
            );
        }
    }

    /**
     * Realiza la acción de registro publico dentro del sistema
     *
     * @return array : Con información de éxito/falla al registrar el usuario nuevo.
     */
    public function register_public(): array
    {
        try {
            global $http;

            # Obtener los datos $_POST
            $terms       = $http->request->get('terms');
            $name        = $http->request->get('nombres');
            $ape         = $http->request->get('apellidos');
            $email       = strtolower($http->request->get('email'));
            $pass        = $http->request->get('pass');
            $pass_repeat = $http->request->get('2pass');

            # Verificar que no están vacíos
            if (Helper\Functions::e($name, $ape, $email, $pass, $pass_repeat, $terms)) {
                throw new ModelsException('Todos los datos son necesarios');
            }

            # Veriricar contraseñas
            $this->checkPassMatch($pass, $pass_repeat);

            # Verificar email
            $this->checkEmail($email);

            # GENERAR ID DE USER
            $usuarios = $this->db->select('MAX(id) AS id', 'users');

            # id_user
            $id_user = $usuarios[0]['id'] + 1;

            # id_user
            $user_box = 'box' . str_pad($id_user, 3, "0", STR_PAD_LEFT);

            # Dtaos de usuario publico
            $data = array(
                'id'          => $id_user,
                'user'        => $user_box,
                'email'       => $email,
                'rol'         => 4,
                'pass'        => Helper\Strings::hash($pass),
                'permissions' => "Box",
                'box'         => [],
                'box_type'    => false,
                'status'      => false,
                'membresia'   => false,
                'user_data'   => array(
                    'nombres'   => $name,
                    'apellidos' => $ape,
                ),
            );

            # Registrar al usuario
            $nuevo_user = $this->db->insert('users', array(
                'id'   => $id_user,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ));

            $data['id_box'] = $user_box;

            # Enviar correo de activacion
            $this->send_to_Active($data);

            return array(
                'success' => 1,
                'message' => 'Registrado con éxito. <br/>Hemos enviado un correo electrónico a: <b>' . $email . '</b>. Para activar tu cuenta.');

        } catch (ModelsException $e) {

            return array(
                'success' => 0,
                'message' => $e->getMessage(),
                'data'    => $http->request->all(),
            );
        }
    }

    /**
     * Envía un correo electrónico al usuario que quiere recuperar la contraseña, con un token y una nueva contraseña.
     * Si el usuario no visita el enlace, el sistema no cambiará la contraseña.
     *
     * @return array<string,integer|string>
     */
    private function send_to_Active(array $data)
    {
        global $config;

        # Generar token y contraseña
        $token = md5(time());
        $pass  = uniqid();
        $link  = $config['build']['url'] . 'verify?token=' . $token . '&user=' . $data['id_box'];

        # Enviar el correo electrónico
        $dest                 = array();
        $dest[$data['email']] = $data['id_box'];
        $email_send           = Helper\Emails::send($dest, array(
            # Título del mensaje
            '{{link}}'     => $link,
            # Tittle de correo
            '{{title}}'    => 'Activa tu cuenta My box - box4bnb.com',
            # username
            '{{username}}' => $data['email'],
        ), 2);

        # Verificar si hubo algún problema con el envío del correo
        if (false === $email_send) {
            throw new ModelsException('No se ha podido enviar el correo electrónico.');
        }

        # Actualizar datos
        $data['token'] = $token;
        $id_user       = $data['id'];

        # Seterar id para actalizacion
        unset($data['id']);

        $this->db->update('users', array(
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ), "id='" . $id_user . "'", 1);

    }

    /**
     * Envía un correo electrónico al usuario que quiere recuperar la contraseña, con un token y una nueva contraseña.
     * Si el usuario no visita el enlace, el sistema no cambiará la contraseña.
     *
     * @return array<string,integer|string>
     */
    public function lostpass(): array
    {
        try {
            global $http, $config;

            # Obtener datos $_POST
            $email = $http->request->get('email');

            # Campo lleno
            if (Helper\Functions::emp($email)) {
                throw new ModelsException('El campo email debe estar lleno.');
            }

            # Filtro
            $email = $this->db->scape($email);

            # Obtener información del usuario
            $user_data = $this->db->select('id_user,name', 'users', null, "email='$email'", 1);

            # Verificar correo en base de datos
            if (false === $user_data) {
                throw new ModelsException('El email no está registrado en el sistema.');
            }

            # Generar token y contraseña
            $token = md5(time());
            $pass  = uniqid();
            $link  = $config['build']['url'] . 'lostpass?token=' . $token . '&user=' . $user_data[0]['id_user'];

            # Construir mensaje y enviar mensaje
            $HTML = 'Hola <b>' . $user_data[0]['name'] . '</b>, ha solicitado recuperar su contraseña perdida, si no ha realizado esta acción no necesita hacer nada.
                    <br />
                    <br />
                    Para cambiar su contraseña por <b>' . $pass . '</b> haga <a href="' . $link . '" target="_blank">clic aquí</a> o en el botón de recuperar.';

            # Enviar el correo electrónico
            $dest         = array();
            $dest[$email] = $user_data[0]['name'];
            $email_send   = Helper\Emails::send($dest, array(
                # Título del mensaje
                '{{title}}'     => 'Recuperar contraseña de ' . $config['build']['name'],
                # Url de logo
                '{{url_logo}}'  => $config['build']['url'],
                # Logo
                '{{logo}}'      => $config['mailer']['logo'],
                # Contenido del mensaje
                '{{content}} '  => $HTML,
                # Url del botón
                '{{btn-href}}'  => $link,
                # Texto del boton
                '{{btn-name}}'  => 'Recuperar Contraseña',
                # Copyright
                '{{copyright}}' => '&copy; ' . date('Y') . ' <a href="' . $config['build']['url'] . '">' . $config['build']['name'] . '</a> - Todos los derechos reservados.',
            ), 0);

            # Verificar si hubo algún problema con el envío del correo
            if (false === $email_send) {
                throw new ModelsException('No se ha podido enviar el correo electrónico.');
            }

            # Actualizar datos
            $id_user = $user_data[0]['id_user'];
            $this->db->update('users', array(
                'tmp_pass' => Helper\Strings::hash($pass),
                'token'    => $token,
            ), "id_user='$id_user'", 1);

            return array('success' => 1, 'message' => 'Se ha enviado un enlace a su correo electrónico.');
        } catch (ModelsException $e) {
            return array('success' => 0, 'message' => $e->getMessage());
        }
    }

    /**
     * Desconecta a un usuario si éste está conectado, y lo devuelve al inicio
     *
     * @return void
     */
    public function logout()
    {
        global $session, $cookie;

        $session->remove($cookie->get('session_hash') . '__user_id');
        foreach ($cookie->all() as $name => $value) {
            $cookie->remove($name);
        }

        Helper\Functions::redir();
    }

    /**
     * Cambia la contraseña de un usuario en el sistema, luego de que éste haya solicitado cambiarla.
     * Luego retorna al sitio de inicio con la variable GET success=(bool)
     *
     * La URL debe tener la forma URL/lostpass?token=TOKEN&user=ID
     *
     * @return void
     */
    public function changeTemporalPass()
    {
        global $config, $http;

        # Obtener los datos $_GET
        $id_user = $http->query->get('user');
        $token   = $http->query->get('token');

        $success = false;
        if (!Helper\Functions::emp($token) && is_numeric($id_user) && $id_user >= 1) {
            # Filtros a los datos
            $id_user = $this->db->scape($id_user);
            $token   = $this->db->scape($token);
            # Ejecutar el cambio
            $this->db->query("UPDATE users SET pass=tmp_pass, tmp_pass=NULL, token=NULL
            WHERE id_user='$id_user' AND token='$token' LIMIT 1;");
            # Éxito
            $success = true;
        }

        # Devolover al sitio de inicio
        Helper\Functions::redir($config['build']['url'] . '?sucess=' . (int) $success);
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

        switch ($this->getOwnerUser()['rol']) {
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
            'nuevo' => 'Nuevo Usuario',
            ''      => 'Todos los Usuarios',
        );

        $page = explode('/', $http->getPathinfo());
        # pagina vigente
        $page = end($page);

        $li = '';

        # Construir menu
        foreach ($menu as $key => $value) {

            if ($key == $page) {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'usuarios/' . $key), 'li-active.html');
            } else {
                $li .= Helper\Sections::loadTemplate(array('{{name}}' => $value, '{{url}}' => $config['build']['url'] . 'usuarios/' . $key), 'li.html');

            }

        }

        $menu = Helper\Sections::loadTemplate(array('{{name}}' => 'Usuarios', '{{li}}' => $li), 'user_menu.html');

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
