<?php

namespace Controllers;

use Model\Proyecto;
use Model\Usuario;
use MVC\Router;


class DashboardController {
    public static function index(Router $router) {
        
        session_start();
        isAuth();

        $id = $_SESSION['id'];

        $proyectos = Proyecto::belongsTo('propietarioId', $id);

        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyectos
        ]);
    }

    public static function crear_proyecto(Router $router) {
        session_start();
        isAuth();
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proyecto = new Proyecto($_POST);
            
            // Validacion
            $alertas = $proyecto->validarProyecto();

            if(empty($alertas)) {
                // Generar URL 
                $hash = md5(uniqid());
                $proyecto->url = $hash;
                // ALmacenar el creador del prouyecto
                $proyecto->propietarioId = $_SESSION['id'];
                // Guardar el Proyecto
                $proyecto->guardar();
                // Redireccionar
                header('Location: /proyecto?url=' . $proyecto->url);

            }
        }

        $router->render('dashboard/crear-proyecto', [
            'titulo' => 'Crear Proyecto',
            'alertas' => $alertas
        ]);
    }

    public static function proyecto(Router $router) {

        session_start();
        isAuth();

        $token = $_GET['url'];

        if(!$token) header('Location: /dashboard');
        // Revisar autenicacion del proyecto con el usuario
        $proyecto = Proyecto::where('url', $token);

        if($proyecto->propietarioId !== $_SESSION['id']) {
            header('Location: /dashboard');
        }

        $router->render('dashboard/proyecto', [
            'titulo' => $proyecto->proyecto
        ]);
    }

    public static function perfil(Router $router) {
        session_start();
        $alertas = [];
        $usuario = Usuario::find($_SESSION['id']);
        isAuth();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validar_perfil();

            if(empty($alertas)) {

                $existeUsuario = Usuario::where('email', $usuario->email);

                if($existeUsuario && $existeUsuario->id !== $usuario->id) {
                    // Mensaje de error
                    Usuario::getAlertas('error', 'El email no es valido, ya esta registrado');
                    $alertas = $usuario->getAlertas();
                } else {
                    // Guardar el Usuario
                    $usuario->guardar();

                    Usuario::setAlerta('exito', 'Guardado correctamente');
                    $alertas = $usuario->getAlertas();
    
                    $_SESSION['nombre'] = $usuario->nombre;
                }
            }
        }

        $router->render('dashboard/perfil', [
            'titulo' => 'Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function cambiar_password(Router $router) {
        
        session_start();
        isAuth();

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = Usuario::find($_SESSION['id']);

            // SIncronicar datos
            $usuario->sincronizar($_POST);
            $alertas = $usuario->nuevo_password();
            
            if(empty($alertas)) {
                $resultado = $usuario->comprobar_password();

                if($resultado){
                    $usuario->password = $usuario->password_nuevo;

                    unset($usuario->password_actual);
                    unset($usuario->password_nuevo);
                    // Asignar nuevo password
                    $usuario->hashPassword();

                    $resultado = $usuario->guardar();

                    if($resultado) {
                        Usuario::setAlerta('exito', 'Password Guardado correctamente');
                        $alertas = $usuario->getAlertas();
                    }

                } else {
                    Usuario::setAlerta('error', 'Password incorrecto');
                    $alertas = $usuario->getAlertas();
                }
            }
        }
        
        $router->render('dashboard/cambiar-password', [
            'titulo' => 'Cambiar Password',
            'alertas' => $alertas
        ]);
    }
}