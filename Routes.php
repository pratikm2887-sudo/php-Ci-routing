<?php

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\HTTP\IncomingRequest;

#######################################
# GLOBAL VARIABLES
#######################################
$request = request();   # Incoming Request
$namespace_string;  # namespace String
$redirect_path = ['controller' => '', 'function' => ''];    # Redirect Path of the String
$inputRequestType = $request->getMethod();
# Input Request Type and Functions to Match Variable
$requestMethodFnMatch = [
    'DEFAULT' => 'index',
    'GET' => 'index',
    'POST' => 'update',
    'DELETE' => 'remove',
];

#######################################
# ROUTES Logic
#######################################

// Load the system's routing file first, so that the app and ENVIRONMENT can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php'))
    require SYSTEMPATH . 'Config/Routes.php';

# Set the Base Route for Faster Access
$routes->get('/', '\App\Modules\Home\Controllers\Home::index');

/**
 *  Documentation for Routes
 *      Consider
 *          URI is demotest/test/fnname
 *              searches for Module Demotest, Controller Test, Function fnname
 *          URI is demotest/test
 *              searches for Module Demotest, Controller Test, Function index
 *              if not found then, will search for Module Demotest, Controller Demotest, function index 
 *          URI is demotest
 *              searches for Module Demotest, Controller Demotest, Function index
 * 
 *          If nothing is found will redirect to path stored in UNAUTHORISED_ACCESS constant
 */
# Modify the Routes to accomodate above Logic for all other Links
if (!empty($_SERVER['PATH_INFO'])) {
    # If Module Exists
    if (file_exists(APPPATH . 'Modules')) {
        $path = explode('/', trim($_SERVER['PATH_INFO'], '/'));
        # Step modified to Search for Actual Path instead of Namespaced One 
        $base_module_path = implode('/', [rtrim(APPPATH, '/'), 'Modules', ucfirst($path[0]), 'Controllers']);
        if (file_exists($base_module_path)) {
            $namespace_string = $path[0];
            $redirect_path['controller'] = $base_module_path;
            if (isset($path[1]) and file_exists($base_module_path . '/' . ucfirst($path[1]) . '.php')) {
                # There exists a Controller with the entered Name
                $namespace_string .= '/' . $path[1];
                $redirect_path['controller'] .= '\\' . ucfirst($path[1]);
                if (isset($path[2])) {
                    $namespace_string .= '/' . $path[2];
                    $redirect_path['function'] = lcfirst($path[2]);
                } else
                    $redirect_path['function'] = 'index';
            } else {
                # The Controller should be the Name of the Module
                $redirect_path['controller'] .= '/' . ucfirst($path[0]);
                $redirect_path['function'] = isset($path[1]) ? lcfirst($path[1]) : 'index';
            }
        } else {
            $routes->setDefaultNamespace('App\Controllers');
            $routes->setDefaultController('Home');
            $routes->setDefaultMethod('index');
            $routes->setTranslateURIDashes(false);
            $routes->set404Override();
            $routes->setAutoRoute(true);
            return;
        }

        # Extra Step Added to Accomodate the Added APPPATH, because Controller is searched in Namespace (Add \ to avoid confusion of the System)
        $redirect_path['controller'] = '\App\\' . trim(implode('\\', explode('/', ltrim($redirect_path['controller'], APPPATH))), '\\');
        ### Set the Base Route, and match any other String Beyond It
        /** Assess Incoming Input request type and assign the appropriate functions */
        if (empty($path[2]) and (!empty($path[1]) or !empty($path[0])))
            $routes->{strtolower($inputRequestType)}($namespace_string, $redirect_path['controller'] . '::' . ($requestMethodFnMatch[$request->getMethod()] ?? $requestMethodFnMatch['DEFAULT']));
        $routes->add($namespace_string, $redirect_path['controller'] . '::' . $redirect_path['function']);
        $routes->add($namespace_string . '/(:any)', $redirect_path['controller'] . '::' . $redirect_path['function'] . '/$1');
        return;
    }
}

//This is the default Controller and Method
$routes->setDefaultNamespace('App\Modules\Home\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php'))
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
