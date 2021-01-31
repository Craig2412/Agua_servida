<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



$app = new \Slim\App();

require __DIR__ . '/../funciones/funciones.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../class/auth.php';
require __DIR__ . '/../dotenv/dotenvRun.php';
require __DIR__ . '/../jwtMiddleware/tuupola.php';
require __DIR__ . '/../class/estadisticas.php';
require __DIR__ . '/../class/classvalidate.php';
require __DIR__ . '/../class/classRegistros.php';
require __DIR__ . '/../class/classPaginador.php';
require __DIR__ . '/../class/crearUsuario.php';
require __DIR__ . '/../class/val.php';

$app->add(Tuupola());

/////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
//////////////////////////////* Usuario */////////////////////////////////
/////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////






$app->post('/base/acomodarr', function (Request $request, Response $response) {
    $body = json_decode($request->getBody());
    $types = typesConsultas($body);
    var_dump($types[1]);

    
});

$app->post('/we', function (Request $request, Response $response, array $args) {
    try {
        // decode data from the caller
        $GLOBALS['body'] = json_decode($request->getBody());
        /**
         * Send to Socket
         */
        \Ratchet\Client\connect('ws://127.0.0.1:8683')->then(function($conn) {
            $conn->send(
                json_encode(array(
                    "user" => $GLOBALS['body']->user,
                    "group" => $GLOBALS['body']->group,
                ))
            );
            $conn->close();
        }, function ($e) {
            $this->logger->info("Can not connect: {$e->getMessage()}");
        });
        return json_encode(array("status" => true));
    } catch (Exception $e) {
        $this->logger->info("Exception: ", $e->getMessage());
        return json_encode(array("status" => false));
    }
});

$app->put('/api/actualizacion/preferencial/lps', function (Request $request, Response $response){
       
    $body = json_decode($request->getBody());
    //$body = json_decode($body->body);

        $sql = "SELECT `usuarios`.`nick`  , `rol`.`rol` 
            FROM `usuarios`
            LEFT JOIN `rol` ON `usuarios`.`id_rol` = `rol`.`id_rol`
            WHERE `id_usuario` = ? ";
        $db = new DB();
        $stmt = $db->consultaAll('user', $sql, $body); 
        if ($stmt) {
            return "Actualizado";
        }else {return "Error";      }

 });

//creacion de usuario, solo con filtro de email y nick
$app->post('/api/creacion/usuarios', function (Request $request, Response $response) { 
    $body = json_decode($request->getBody());
     $nick = $body->{'nick'};
     $email = $body->{'email'};
     $email = filter_var($email, FILTER_SANITIZE_EMAIL);
     $pass = $body->{'pass'};
     $rol = $body->{'id_rol'};           
     $hidrologica = $body->{'id_hidrologica'};           
     
     $check = array($nick , $pass ,$email , $rol , $hidrologica );
     $contador = 0;
 
     for ($i=0; $i < count($check) ; $i++) { 
         if (!isset($check[$i])) {
                 $contador++;
             }
         }
            
             if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                 return "El email no es valido";
             }else  if ($contador === 0){
                 $usuarios = new Usuarios($nick , $pass);
                 return $usuarios->creacion($body , $email , $rol , $hidrologica);
             }else{
                 return 'Hay variables que no estan definida';
             }
            
             
      });


// Group de rutas de usuario
$app->group('/api/user/', function () use ($app) {

    $app->post('info', function (Request $request, Response $response) { 
        $body = json_decode($request->getBody());
        $nick = json_decode($body->body); 
            try {
                $sql = "SELECT usuarios.id_hidrologica FROM usuarios WHERE usuarios.nick = ?";
                $db = new DB();
                $resultado =  $db->consultaAll('user' ,$sql , [$nick]);
                $id_hidrologica = $resultado[0];       
                if ($resultado) {
                    
                    $sql = "SELECT hidrologicas.* FROM hidrologicas WHERE hidrologicas.id_hidrologica = ?";
                    $resultado = $db->consultaAll('mapa' ,$sql,$id_hidrologica);
                    $estados = [$resultado[0]['id_estado'], $resultado[0]['id_estado2'], $resultado[0]['id_estado3']];
                    $hidrologica = $resultado[0]['hidrologica'];
                    $id_hidrologica = $resultado[0]['id_hidrologica'];
        
                if ($resultado) {
                    $sql = "SELECT estados.id_estado, estados.estado
                            FROM estados
                            WHERE estados.id_estado 
                            IN (? , ? , ?)";
                    $resultado = $db->consultaAll('mapa', $sql , $estados);
        
                    $array = [
                        "hidrologica" => $hidrologica,
                        "id_hidrologica" => $id_hidrologica,
                        "estados" => $resultado
                    ];
                    return $response->withJson($array);
        
                    }
        
                }
                
            } 
            catch (MySQLDuplicateKeyException $e) {
                $e->getMessage();
            }
            catch (MySQLException $e) {
                $e->getMessage();
            }
            catch (Exception $e) {
                $e->getMessage();
            }
        });

        $app->post('authenticate', function (Request $request, Response $response) {
            $body = json_decode($request->getBody());
            $body=json_decode($body->body);
        
            $sql = "SELECT `usuarios`.*
                    FROM `usuarios`";
            $db = new DB();
            $resultado = $db->consultaAll('usuarios_m_soluciones', $sql);
            
            foreach ($resultado as $key => $user) {
            if ($user['nick'] == $body->user && $user['pass'] == $body->pass) {
                $current_user = $user;
            }}
        
            if (!isset($current_user)) {
                echo json_encode("No user found");
            } else{
        
                $sql = "SELECT * FROM tokens
                     WHERE id_usuario_token  = ?";
        
                try {
                    $db = new DB();
                    $token_from_db = $db->consultaAll('usuarios_m_soluciones', $sql, [$current_user['id_usuario']], 'objeto');
                    $db = null;
                    if ($token_from_db) {
                        return $response->withJson([
                            "Token" => $token_from_db->token,
                            "User_render" =>$current_user['id_rol'], 
                           // "Hidrologica" =>$current_user
                        ]);
                    }    
                    }catch (Exception $e) {
                    $e->getMessage();
                    }
        
                if (count($current_user) != 0 && !$token_from_db) {
        
        
                     $data = [
                        "user_login" => $current_user['nick'],
                        "user_id"    => $current_user['id_usuario'],
                        "user_rol"    => $current_user['id_rol'],
                        "user_hidrologica" => $current_user['id_hidrologica'],
                        "user_estado"    => $current_user['id_estado']
                    ];
        
                     try {
                        $token=Auth::SignIn($data);
                     } catch (Exception $e) {
                         echo json_encode($e);
                     }
        
                      $sql = "INSERT INTO tokens (id_usuario_token, token)
                          VALUES (?, ?)";
                      try {
                            $db = new DB();
                            $token_from_db = $db->consultaAll('usuarios_m_soluciones', $sql, [$current_user['id_usuario'], $token]);
                            $db = null;
                            return $response->withJson([
                                "Token" => $token,
                                "User_render" =>$current_user['id_rol']
                            ]);
         
                      } catch (PDOException $e) {
                          echo '{"error":{"text":' . $e->getMessage() . '}}';
                      }
                 }
            }
        
        });

});





$app->group('/api/proyectos/', function () use ($app) {
    
    $app->get('proyecto/{id_proyecto}', function (Request $request, Response $response) { //Informacion completa de un solo proyecto
        $id_proyecto = $request->getAttribute('id_proyecto');
        
    
                $sql = "SELECT `proyectos`.`id_proyecto`, `proyectos`.`id_estatus`as `id_proyecto_estatus`, `proyectos`.`id_estado` as `id_proyecto_estado` ,  `datos`.*, `ciclos`.*, `ejecucion_financiera`.* ,`inversion`.*, `estados`.`estado`, `municipios`.`municipio`, `parroquias`.`parroquia`, `hidrologicas`.`hidrologica`, `estatus`.`estatus`, `lapso`.*, `lps`.* ,  `obras`.`coordenadas` as obras, `obras`.`id_obra`, `poblacion`.`poblacion_inicial`, `sector`.`coordenadas` as sector, `sector`.`id_sector`, `situaciones de servicio`.`situacion_de_servicio`, `soluciones`.`solucion`
                FROM `proyectos` 
                    LEFT JOIN `datos` ON `proyectos`.`id_datos` = `datos`.`id_datos` 
                    LEFT JOIN `ciclos` ON `proyectos`.`id_ciclo` = `ciclos`.`id_ciclo` 
                    LEFT JOIN `ejecucion_financiera` ON `proyectos`.`id_ejecucion_financiera` = `ejecucion_financiera`.`id_ejecucion_financiera` 
                    LEFT JOIN `inversion` ON `inversion`.`id_ejecucion_financiera` = `ejecucion_financiera`.`id_ejecucion_financiera`
                    LEFT JOIN `estados` ON `proyectos`.`id_estado` = `estados`.`id_estado` 
                    LEFT JOIN `municipios` ON `proyectos`.`id_municipio` = `municipios`.`id_municipio` 
                    LEFT JOIN `parroquias` ON `parroquias`.`id_parroquia` = `proyectos`.`id_parroquia`
                    LEFT JOIN `hidrologicas` ON `hidrologicas`.`id_hidrologica`= `proyectos`.`id_hidrologica` 
                    LEFT JOIN `estatus` ON `proyectos`.`id_estatus` = `estatus`.`id_estatus` 
                    LEFT JOIN `lapso` ON `proyectos`.`id_lapso` = `lapso`.`id_lapso` 
                    LEFT JOIN `lps` ON `proyectos`.`id_lps` = `lps`.`id_lps`
                    LEFT JOIN `obras` ON `proyectos`.`id_obra` = `obras`.`id_obra` 
                    LEFT JOIN `poblacion` ON `proyectos`.`id_poblacion` = `poblacion`.`id_problacion` 
                    LEFT JOIN `sector` ON `proyectos`.`id_sector` = `sector`.`id_sector` 
                    LEFT JOIN `situaciones de servicio` ON `proyectos`.`id_estado_proyecto` = `situaciones de servicio`.`id_situacion_de_servicio` 
                    LEFT JOIN `soluciones` ON datos.`id_tipo_solucion` = `soluciones`.`id_solucion`
                    WHERE `proyectos`.`id_proyecto` =  ?";
    
                
        try {
            $db = new DB();
            $resultado = $db->consultaAll('mapa', $sql , [$id_proyecto]);
            $resultado[0]['obras'] = json_decode($resultado[0]['obras']);
            $resultado[0]['sector'] = json_decode($resultado[0]['sector']);
            $id_datos = $resultado[0]['id_datos'];
            $result = $resultado;
            
            
                if ($resultado) {
                    $sql = "SELECT `acciones_especificas`.* , unidades.* , intervencion.* 
                    FROM `acciones_especificas` 
                    LEFT JOIN intervencion ON acciones_especificas.id_intervencion = intervencion.id_intervencion 
                    LEFT JOIN unidades ON acciones_especificas.id_unidades = unidades.id_unidades 
                    WHERE acciones_especificas.id_datos = ?";
                    
                    
                    $resultado = $db->consultaAll('mapa', $sql , [$id_datos]);
                    $acciones = $resultado;
    
                    if ($resultado) {
                        
                            $accionesFinalizadas = null;
                            for ($i=0; $i < count($resultado) ; $i++) { 
                                if ($resultado[$i]['valor'] === 1) {
                                    $accionesFinalizadas++;
                                }
                            }
                            if ($accionesFinalizadas === 0) {
                                $porcentaje = "0";
                            }else {
                                $porcentaje = ($accionesFinalizadas * 100) / count($resultado);
                            }
                            $array = [
                                'accionesEspecificas' => $acciones,
                                'porcentaje' => $porcentaje
                            ];
                            array_push($result[0] , $array);
                    }
                    
                    
                }
    
                return $response->withJson($result);                        
            
            } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
        
    });

    $app->get('reportes[/{params:.*}]', function (Request $request, Response $response, $args) {

        /*
            ruta para obtener los reportes de el sistema
            1) existen tres parametros opcionales en la ruta, params[1] es el numero de paginacion, params[2] es el tipo de busqueda que vas a hacer si es el caso, params[3] es el id que se utiliza para formular la busqueda
            
        */
    
        $idHidrologica=$request->getAttribute('jwt')["data"]->user_hidrologica;
        /*
        Obteniendo user_hidrologica del usuario para el primer render de la paginacion, se obtiene por el Token
        1) se optiene el token para asi poder verificar o dar la informacion en base a lo que presenta el usuario en su informacion personal
        2) en esta opcion se optione el user_hidrologica que es el id de la hidrologica del usuario
        3) es totalmente necesario para el funcionamiento de la ruta
        */
    
        if (!empty($args['params'])) { //validamos si la ruta tiene algun valor opcional en el url
    
            ;
            $params = EliminarBarrasURL($args['params']);
            
            $tipoConsulta = null;
            if (count($params)>2) {
                $params[2] = trim(rawurldecode($params[2]), ' ');
                //comprobamos que en el params[1] venga el valor de busqueda
                if ($params[1] === "busqueda") {
                    $array = [];
                    if ($params[2][0]==="#") {
                        //si es un id, que se identifica por tener como primer valor el #, entonces extraemos los parametros para la consulta mysql, se le pasa como segundo valor a la funcion, el tipo, que en este caso es id
                        $tipoConsulta = ExtraerConsultaParametro($params[1], 'id');         
                            $paramsSaneado = explode('-', $params[2]);
                            $params[2] = [$paramsSaneado[1]];
                    }else {
                        // en caso contrario se pasa nada mas el primer parametro, y se crea un array con los valores duplicados para enviarlos a la consulta
                        $tipoConsulta = ExtraerConsultaParametro($params[1]);
                        for ($i=0; $i < count($tipoConsulta); $i++) {
                            $param = urldecode($params[2]);
                            $param = '%'.$params[2].'%';               
                            array_push($array, $param);
                        }
                        $params[2]=$array;
                        
                    }
                }else {
                    $tipoConsulta = ExtraerConsultaParametro($params[1]);
                    $params[2]=[ucfirst($params[2])];
                    
                }
                    
            }
            if ($tipoConsulta !== null) {
                $where = CondicionalMYSQL($idHidrologica, $tipoConsulta, $params[2]);
            }else {
                $where = CondicionalMYSQL($idHidrologica);
            }
    
            $sql = "SELECT COUNT(*) as Paginas
                    FROM proyectos
                    LEFT JOIN estados ON proyectos.id_estado = estados.id_estado 
                    LEFT JOIN municipios ON proyectos.id_municipio = municipios.id_municipio 
	                LEFT JOIN parroquias ON parroquias.id_parroquia = proyectos.id_parroquia
                    LEFT JOIN datos ON proyectos.id_datos = datos.id_datos
                    LEFT JOIN estatus ON proyectos.id_estatus = estatus.id_estatus
                    LEFT JOIN ciclos ON proyectos.id_ciclo = ciclos.id_ciclo
                    LEFT JOIN soluciones ON datos.id_tipo_solucion = soluciones.id_solucion
                    LEFT JOIN `situaciones de servicio` ON proyectos.id_estado_proyecto = `situaciones de servicio`.id_situacion_de_servicio
                    LEFT JOIN acciones_especificas ON acciones_especificas.id_datos = datos.id_datos
                    LEFT JOIN intervencion ON acciones_especificas.id_intervencion = intervencion.id_intervencion
                    LEFT JOIN unidades ON acciones_especificas.id_unidades = unidades.id_unidades
                    
                    {$where}";

                    

            if ($where !== "") {
                if ($tipoConsulta!==null) {
                    if ($idHidrologica===20) {
                        $db = new DB();
                        $datos = array($db->consultaAll('mapa', $sql, $params[2])[0]['Paginas'],$params[0]);
                        
                        
                    }else{
                        $db = new DB();
                        $param= merge([$idHidrologica], ...$params[2]);
                        $datos = array($db->consultaAll('mapa', $sql, $param)[0]['Paginas'], $params[0]);
                        
                    }
                }else {
                    $db = new DB();
                    $datos = array($db->consultaAll('mapa', $sql, [$idHidrologica])[0]['Paginas'],$params[0]);
                    
                }
            }else {
                $db = new DB();
                $datos = array($db->consultaAll('mapa',$sql)[0]['Paginas'], $params[0]);
                
            }
    
    
            if ($params[0] < 1 || $params[0] > ceil($datos[0] / 20)){
                return "La pagina solicitada no existe";
            }else{
                $paginador = New paginadorIncidencia($tipoConsulta!==null?$params[2]:null, $idHidrologica, $tipoConsulta);
                return json_encode($paginador->paginadorIncidencias($datos));             
            }
        }else {
            $sql = "SELECT proyectos.`id_proyecto`, datos.`nombre`, datos.`accion_general`, soluciones.`solucion`, estatus.`estatus`
            FROM proyectos 
            LEFT JOIN datos ON proyectos.`id_datos` = datos.`id_datos` 
            LEFT JOIN soluciones ON datos.`id_tipo_solucion` = soluciones.`id_solucion` 
            LEFT JOIN estatus ON proyectos.`id_estatus` = estatus.`id_estatus`";
             
             try {
                $db = new DB();
                $resultado = $db->consultaAll('mapa',$sql);  
                return $response->withJson($resultado);                  
             } 
            catch (MySQLDuplicateKeyException $e) {
                $e->getMessage();
            }
            catch (MySQLException $e) {
                $e->getMessage();
            }
            catch (Exception $e) {
                $e->getMessage();
            }
        }
            
    });
    
});


//////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////
//////////////////////////////* GET *//////////////////////////////////
//////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////



    
     
$app->get('/api/informacion/mapa/impacto', function (Request $request, Response $response) {

    

    $sql = "SELECT proyectos.id_proyecto, sector.coordenadas as 'sector'
    FROM proyectos 
        LEFT JOIN sector ON proyectos.id_sector = sector.id_sector";
        
        try {
            $db = new DB();
            $resultado = $db->consultaAll('mapa', $sql);
            $resultado = decodeJsonArray($resultado, 'sector');

            $array = ["sector"=>[]];
            for ($i=0; $i < count($resultado); $i++) { 
                $resultado[$i]['sector']->features[0]->properties->id = $resultado[$i]['id_proyecto'];
                unset($resultado[$i]['id_proyecto']);
                array_push($array['sector'], $resultado[$i]['sector']);
            }
            return $response->withJson($array);
            
        } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
});


/********************Inicio Dashboard***********************/

$app->get('/api/estadistica/situacion/servicio', function (Request $request, Response $response) { /*grafico abajo, centro, donde se muestra dos valores del estado de servicio global*/ 
    
        $sql  ="SELECT `situaciones de servicio`.`situacion_de_servicio` , COUNT(proyectos.id_estado_proyecto) as cantidad, SUM(poblacion.poblacion_inicial) as poblacion 
        FROM `situaciones de servicio` 
        LEFT JOIN `proyectos` ON `proyectos`.`id_estado_proyecto` = `situaciones de servicio`.`id_situacion_de_servicio`
        LEFT JOIN `poblacion` ON `proyectos`.`id_poblacion` = `poblacion`.`id_problacion`
        WHERE `situaciones de servicio`.`id_situacion_de_servicio` IN (1, 2, 3) 
        GROUP BY `situaciones de servicio`.`situacion_de_servicio`";

        try {
            $db = new DB();
            $resultado = $db->consultaAll('mapa', $sql);
            $db = null;
            for ($i=0; $i < count($resultado); $i++) { 
                
                $resultado[$i]['poblacion'] =number_format($resultado[$i]['poblacion'],0, '.','.');
            }
               
             return $response->withJson($resultado);
                    
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
});


$app->get('/api/estadistica/proyecto', function (Request $request, Response $response) { /* para grafico de proyectos finalizados, en ejecuion y por iniciar*/

    $sql ="SELECT estatus.estatus ,COUNT(proyectos.id_estatus) as cantidad 
	        FROM estatus 
	        LEFT JOIN `proyectos` ON `proyectos`.`id_estatus` = `estatus`.`id_estatus` 
	        WHERE estatus.id_estatus IN (0, 1, 2) 
	        GROUP BY estatus.id_estatus";

    try {
        $db = new DB();
        $resultado = $db->consultaAll('mapa', $sql);
        return $response->withJson($resultado);
    
    } 
    catch (MySQLDuplicateKeyException $e) {
        $e->getMessage();
    }
    catch (MySQLException $e) {
        $e->getMessage();
    }
    catch (Exception $e) {
        $e->getMessage();
    }
});
        
        

$app->get('/api/estadistica/tipos/soluciones', function (Request $request, Response $response) { /*grafico arriba centro, para mostrar el porcentaje de proyectos por cada solucion */
    
        $sql  ="SELECT datos.id_tipo_solucion, soluciones.solucion ,COUNT(proyectos.id_proyecto) as cantidad 
                FROM proyectos 
                LEFT JOIN datos ON proyectos.id_datos = datos.id_datos
                LEFT JOIN soluciones ON datos.id_tipo_solucion = soluciones.id_solucion 
                WHERE datos.id_tipo_solucion IN (1, 2, 3, 4)
                GROUP BY datos.id_tipo_solucion";

        try {
            $db = new DB();
            $resultado = $db->consultaAll('mapa',$sql);

            if (count($resultado) === 0) {
                $array= [                     
                    [ 
                        "id_tipo_solucion" => 1,
                        "solucion" => "Local o Comunitaria",
                        "cantidad" => 0
                    ],
                    [ 
                        "id_tipo_solucion" => 2,
                        "solucion" => "Convencional ",
                        "cantidad" => 0
                    ],
                    [
                        
                        "id_tipo_solucion" => 3,
                        "solucion" => "Estructurante",
                        "cantidad" => 0
                    ]
                    ,
                    [ 
                        "id_tipo_solucion" => 4,
                        "solucion" => "En fuentes",
                        "cantidad" => 0
                    ]
                ];
                return $response->withJson($array);
            }else{
                return $response->withJson($resultado);
            }

                    
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
         });



$app->get('/api/estadistica/tipos/unidades', function (Request $request, Response $response) { 
    
    $sql  ="SELECT unidades.unidad, COUNT(acciones_especificas.id_unidades) as cantidad 
                    FROM unidades 
            LEFT JOIN acciones_especificas ON acciones_especificas.id_unidades = unidades.id_unidades 
            GROUP BY unidades.unidad";

    try {
        $db = new DB();
        $resultado = $db->consultaAll('mapa',$sql);

        return $response->withJson($resultado);

    } 
    catch (MySQLDuplicateKeyException $e) {
        $e->getMessage();
    }
    catch (MySQLException $e) {
        $e->getMessage();
    }
    catch (Exception $e) {
        $e->getMessage();
    }

});


$app->get('/api/ultimos/reportes', function (Request $request, Response $response){

    $token = $request->getAttribute("jwt");
    $sql = "SELECT `proyectos`.`id_proyecto`, `datos`.`fecha`, `datos`.`nombre`, `hidrologicas`.`hidrologica`, `municipios`.`municipio`, `estados`.`estado` 
    FROM `proyectos` 
    LEFT JOIN `datos` ON `proyectos`.`id_datos` = `datos`.`id_datos` 
    LEFT JOIN `hidrologicas` ON `proyectos`.`id_hidrologica` = `hidrologicas`.`id_hidrologica` 
    LEFT JOIN `municipios` ON `proyectos`.`id_municipio` = `municipios`.`id_municipio` 
    LEFT JOIN `estados` ON `proyectos`.`id_estado` = `estados`.`id_estado` 
    LEFT JOIN `situaciones de servicio` ON `proyectos`.`id_estado_proyecto` = `situaciones de servicio`.`id_situacion_de_servicio` ORDER BY (proyectos.id_proyecto) DESC LIMIT 0 , 5";

    $db = new DB();
    $resultado = $db->consultaAll('mapa',$sql);
    return $response->withJson($resultado);
    
});

/********************Fin Dashboard***********************/


$app->get('/api/informacion/general/proyectos', function (Request $request, Response $response) { /* Mostrar proyetos con informacion minima y una vista previa*/
    

    $sql = "SELECT proyectos.`id_proyecto`, datos.`nombre`, datos.`accion_general`, soluciones.`solucion`, estatus.`estatus`
        FROM proyectos 
        LEFT JOIN datos ON proyectos.`id_datos` = datos.`id_datos` 
        LEFT JOIN soluciones ON datos.`id_tipo_solucion` = soluciones.`id_solucion` 
        LEFT JOIN estatus ON proyectos.`id_estatus` = estatus.`id_estatus`";
         
         try {
            $db = new DB();
            $resultado = $db->consultaAll('mapa',$sql);  
            return $response->withJson($resultado);                  
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
     });




$app->get('/api/informacion/proyectos/hidrologicas', function (Request $request, Response $response) { /* grafica arriba izquierda mostrando la cantidad de proyectos*/
    

    $sql = "SELECT hidrologicas.hidrologica, COUNT(proyectos.id_proyecto) AS cantidad 
            FROM hidrologicas 
            LEFT JOIN proyectos on proyectos.id_hidrologica = hidrologicas.id_hidrologica 
            GROUP BY hidrologicas.id_hidrologica";
         
         try {
            $db = new DB();
            $resultado = $db->consultaAll('mapa',$sql); 
            

            try{
                $sql2 = "SELECT hidrologicas.hidrologica, COUNT(proyectos.id_proyecto) AS finalizados 
                        FROM hidrologicas 
                        LEFT JOIN proyectos on proyectos.id_hidrologica = hidrologicas.id_hidrologica 
                        WHERE proyectos.id_estatus = 2 
                        GROUP BY hidrologicas.id_hidrologica";
                
                $db = new DB();
                $resultado2 = $db->consultaAll('mapa',$sql2); 

                for ($i=0; $i < count($resultado); $i++) {

                    for ($x=0; $x < count($resultado2); $x++) { 
                        if ($resultado[$i]["hidrologica"] === $resultado2[$x]["hidrologica"]) {
                            $resultado[$i]["proyectosFinalizados"]= $resultado2[$x]["finalizados"];
                        }
                    }
                }

                return $response->withJson($resultado);

                

            }
            catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
            }
            catch (MySQLException $e) {
                $e->getMessage();
            }
            catch (Exception $e) {
                $e->getMessage();
            }
            
                            
               
            
         } 
        catch (MySQLDuplicateKeyException $e) {
            $e->getMessage();
        }
        catch (MySQLException $e) {
            $e->getMessage();
        }
        catch (Exception $e) {
            $e->getMessage();
        }
});


$app->get('/api/desplegables/hidrologicas', function (Request $request, Response $response) {

    $sql = "SELECT hidrologicas.*
            FROM hidrologicas";
        
    $db = new DB();
    $resultado = $db->consultaAll('mapa',$sql);
    return $response->withJson($resultado); 

});


$app->get('/api/desplegables/estados[/{hidrologica}]', function (Request $request, Response $response) { 
    $id = $request->getAttribute('hidrologica')+0;
    if ($id) {

        $sql = "SELECT hidrologicas.* FROM hidrologicas WHERE hidrologicas.id_hidrologica = ?";
        $db = new DB();
        $resultado = $db->consultaAll('mapa',$sql, [$id]);

        if ($resultado) {

            $sql = "SELECT estados.id_estado, estados.estado
                    FROM estados
                    WHERE estados.id_estado 
                    IN (? , ? , ?)";
            $resultado = $db->consultaAll('mapa',$sql, [$resultado[0]['id_estado'],$resultado[0]['id_estado2'],$resultado[0]['id_estado3']]);
            return $response->withJson($resultado); 

        }
    }else{

        $sql = "SELECT `estados`.`id_estado`, `estados`.`estado` FROM `estados`";
        $db = new DB();
        $resultado = $db->consultaAll('mapa',$sql);      
        return $response->withJson($resultado);                        

    }
    
});

//ACA MANDAS EL ID DEL ESTADO POR EL ULR CABRON
$app->get('/api/desplegables/municipios/{id_estado}', function (Request $request, Response $response) {
    $id = $request->getAttribute('id_estado')+0;
    $sql = "SELECT municipios.id_municipio, municipios.municipio, estados.id_estado
        FROM municipios 
        LEFT JOIN estados ON municipios.id_estado = estados.id_estadO
        WHERE municipios.id_estado = ?";
        $db = new DB();
    $resultado = $db->consultaAll('mapa',$sql,[$id]); 
    return $response->withJson($resultado);
         
});

//ACA TAMBIEN MANDAS EL ID POR EL ULR, PERO ESTA VEZ DEL MUNICIPIO POLLO
$app->get('/api/desplegables/parroquias[/{id_municipio}]', function (Request $request, Response $response) {
    $id = $request->getAttribute('id_municipio');

    $sql = "SELECT parroquias.id_parroquia, parroquias.parroquia, municipios.id_municipio 
            FROM parroquias
            LEFT JOIN municipios ON parroquias.id_municipio = municipios.id_municipio 
            WHERE municipios.id_municipio = ?";
    $db = new DB();
    $resultado = $db->consultaAll('mapa',$sql,[$id]);
    return $response->withJson($resultado);

         
});








////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////
//////////////////////////////* POST *///////////////////////////////////
////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////




$app->post('/api/municipios', function (Request $request, Response $response) { 
    $body = json_decode($request->getBody());
    $id_estado = json_decode($body->body);
    $id_estado = $id_estado->id_estado + 0;

    $sql = "SELECT municipios.id_municipio, municipios.municipio, estados.id_estado 
            FROM municipios 
            LEFT JOIN estados ON municipios.id_estado = estados.id_estado 
            WHERE municipios.id_estado = ?";
                 
    try {
        
        
        $resultado = consultasConUnID($sql , $id_estado); 
        $resultado = $resultado->fetch_all(MYSQLI_ASSOC);         
        
        return $response->withJson($resultado);                        
        } 
    catch (MySQLDuplicateKeyException $e) {
        $e->getMessage();
    }
    catch (MySQLException $e) {
        $e->getMessage();
    }
    catch (Exception $e) {
        $e->getMessage();
    }
            
});

$app->post('/api/mapa/agregar', function (Request $request, Response $response) { 
    $body = json_decode($request->getBody());
    $body = json_decode($body->body);
    

    $sql = "UPDATE `sector` SET `coordenadas` = ? WHERE sector.id_sector = ?";
    $sql2 = "UPDATE `obras` SET `coordenadas`= ? WHERE `obras`.`id_obra`= ?";

    if ($body->ciclo < 4 && $body->opcion_ciclo === "dias") {
        $color = "#9191e3";
    }else if ($body->ciclo > 3 && $body->opcion_ciclo === "dias" || $body->ciclo < 45 && $body->opcion_ciclo === "dias" || $body->ciclo > 0 && $body->opcion_ciclo === "semanas" || $body->ciclo < 7 && $body->opcion_ciclo === "semanas" || $body->ciclo === 1 && $body->opcion_ciclo === "meses") {
        $color = "#ffa219";
    }else if ($body->ciclo > 44 && $body->opcion_ciclo === "dias" || $body->ciclo > 6 && $body->opcion_ciclo === "semanas" || $body->ciclo > 1 && $body->opcion_ciclo === "meses") {
       $color = "#ea00a2";
    }else {
        $color = "#ea00a2";
    }

    $obra = $body->obra;
    $obra->features[0]->properties->color = "#008ffb";

    $sector = $body->coordenadas_sector;
    $sector->features[0]->properties->color= $color ;

    $update1 = updateConDosID($sql, json_encode($sector), $body->id_sector);
    $update2 = updateConDosID($sql2, json_encode($obra), $body->id_obra);

    if ($update1 ===true && $update2 === true) {
        return "Agregado Correctamente";
    }

            
});



$app->post('/api/parroquias', function (Request $request, Response $response) { 
                $body = json_decode($request->getBody());
                $id_municipio = json_decode($body->body);
                $id_municipio = $id_municipio->id_municipio +0;

                 $sql = "SELECT parroquias.id_parroquia, parroquias.parroquia, municipios.id_municipio 
                 FROM parroquias 
                 LEFT JOIN municipios ON parroquias.id_municipio = municipios.id_municipio 
                 WHERE municipios.id_municipio = ?";

                 
            try {
               
                $resultado = consultasConUnID($sql , $id_municipio); 
                $resultado = $resultado->fetch_all(MYSQLI_ASSOC);
                
                return $response->withJson($resultado);                        
             } 
            catch (MySQLDuplicateKeyException $e) {
                $e->getMessage();
            }
            catch (MySQLException $e) {
                $e->getMessage();
            }
            catch (Exception $e) {
                $e->getMessage();
            }
            
            });



     
            $app->post('/api/registro/proyetos', function (Request $request, Response $response){
                $token = $request->getAttribute("jwt");
                
                $body = json_decode($request->getBody());
                $body = json_decode($body->body);

//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//||||||||||||||||||||||||||||||||||||||||||||Recepcion de variables|||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
                
                    $acciones_especificas = $body->{'acciones_especificas'};

                    if (isset($body->{'ciclo_inicial'})) {
                        $ciclo_inicial =$body->{'ciclo_inicial'};
                    }else {
                        $ciclo_inicial = 0;
                    }

                    if (isset($body->{'opcion_ciclo_inicial'})) {
                        $opcion_ciclo_inicial = $body->{'opcion_ciclo_inicial'};
                    }else {
                        $opcion_ciclo_inicial = "dias";
                    }

                   

        
                    if ($ciclo_inicial < 4 && $opcion_ciclo_inicial === "dias") {
                        $id_estado_proyecto = 1;
                        $color = "#9191e3";
                    }else if ($ciclo_inicial > 3 && $opcion_ciclo_inicial === "dias" || $ciclo_inicial < 45 && $opcion_ciclo_inicial === "dias" || $ciclo_inicial > 0 && $opcion_ciclo_inicial === "semanas" || $ciclo_inicial < 7 && $opcion_ciclo_inicial === "semanas" || $ciclo_inicial == 1 && $opcion_ciclo_inicial === "meses") {
                        $id_estado_proyecto = 2;
                        $color = "#ffa219";
                    }else if ($ciclo_inicial > 44 && $opcion_ciclo_inicial === "dias" || $ciclo_inicial > 6 && $opcion_ciclo_inicial === "semanas" || $ciclo_inicial > 1 && $opcion_ciclo_inicial === "meses") {
                       $id_estado_proyecto = 3;
                       $color = "#ea00a2";
                    }else {
                        $id_estado_proyecto = 3;
                        $color = "#ea00a2";
                    }
                    
                    if (isset($body->{'obra'})) {
                        $obra = $body->{'obra'};
                        $obra->features[0]->properties->color = "#008ffb";
                    }else{
                        $obra = "En Espera";
                    }
                    
                    if (isset($body->{'coordenadas_sector'})) {
                        $sector = $body->{'coordenadas_sector'};
                        
                        $sector->features[0]->properties->color= $color ; 
                    }else{
                        $sector = "En Espera";
                    }
                                        
                    $poblacion_inicial = $body->{'poblacion_inicial'};     
                    


                    if (isset($body->{'lps_inicial'})) {
                        $lps_inicial =$body->{'lps_inicial'};       
                    }else {
                        $lps_inicial = 0;
                    }
                 
                    
                    
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||                    
//||||||||||||||||||||||||||||||||||||||Arrays para enviar al registro|||||||||||||||||||                 
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||

                    $datos = array( $body->{'datos'}->{'nombre_datos'} ,
                                    $body->{'datos'}->{'id_tipo_solucion_datos'} ,
                                    $body->{'datos'}->{'descripcion_datos'} ,                                   
                                    $body->{'datos'}->{'accion_general'},
                                    $token['data']->user_id
                                );

                    if ($body->{'lapso_estimado_inicio'} AND $body->{'lapso_estimado_culminacion'}) {
                        $lapso = array( $body->{'lapso_estimado_inicio'},
                                        $body->{'lapso_estimado_culminacion'});           
                    }else {
                        $lapso = array( "0000-00-00",
                                        "0000-00-00");    
                    }
                                    
                    $ciclos = array( $ciclo_inicial ,
                                     $opcion_ciclo_inicial );


                    if (isset($body->{'ejecucion_bolivares'}) AND isset($body->{'ejecucion_euros'}) ) {
                        $ejecucion_financiera = array(  $body->{'ejecucion_bolivares'} ,
                                                        $body->{'ejecucion_euros'});
                                                        
                    }else {
                        $ejecucion_financiera = array(  0 ,
                                                        0);
                    }

                  

                    $inversion = array( $body->{'inversion_bolivares'} ,  
                                        $body->{'inversion_euros'});
                    
                                        
                    if ($body->{'id_hidrologica'}) {
                        $proyecto = array(  $body->{'id_hidrologica'} , 
                        $body->{'id_estado'} , 
                        $body->{'id_municipio'} , 
                        $body->{'id_parroquia'} , 
                        0, 
                        $id_estado_proyecto);
                    }else {
                        $proyecto = array(  
                        $token['data']->user_hidrologica, 
                        $body->{'id_estado'} , 
                        $body->{'id_municipio'} , 
                        $body->{'id_parroquia'} , 
                        0, 
                        $id_estado_proyecto);
                    }
                    
                    
                    
                
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||                    
//||||||||||||||||||||||||||||||||||||||Array para validacion||||||||||||||||||||||||||||                 
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||

                    $check = array( $body->{'datos'}->{'nombre_datos'} ,
                                    $body->{'datos'}->{'id_tipo_solucion_datos'} ,
                                    $body->{'datos'}->{'descripcion_datos'} ,                                   
                                    $body->{'datos'}->{'accion_general'}, 
                                    $sector , 
                                    $body->{'lapso_estimado_inicio'},
                                    $body->{'lapso_estimado_culminacion'},
                                    $ciclo_inicial , 
                                    $opcion_ciclo_inicial, 
                                    $ejecucion_financiera[0], 
                                    $ejecucion_financiera[1], 
                                    $body->{'inversion_bolivares'} ,  
                                    $body->{'inversion_euros'},  
                                    $body->{'id_hidrologica'} , 
                                    $body->{'id_estado'} , 
                                    $body->{'id_municipio'} , 
                                    $body->{'id_parroquia'}  , 
                                    $id_estado_proyecto, 
                                    $acciones_especificas , 
                                    $obra ,$poblacion_inicial , 
                                    $lps_inicial);
                    $contador = 0;
            
                    for ($i=0; $i < count($check) ; $i++) { 
                        if (!isset($check[$i])) {
                                $contador++;
                            }
                        }

                        
                        if (($contador === 0) ){
                            
                                $registro = new Registro();
                                return  $response->withJson($registro->crearProyectos(  $datos , 
                                                                                        $acciones_especificas , 
                                                                                        $obra , 
                                                                                        $sector, 
                                                                                        $lapso , 
                                                                                        $ciclos , 
                                                                                        $ejecucion_financiera , 
                                                                                        $inversion , 
                                                                                        $poblacion_inicial , 
                                                                                        $lps_inicial , 
                                                                                        $proyecto));

                        } else {
                            return 'Hay variables que no estan definida';
                        }
                        
                            
        
        
        
});
        









////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////
//////////////////////////////* PUT *///////////////////////////////////
////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////




$app->post('/api/actualizacion/acciones/especificas', function (Request $request, Response $response){
        
        $body = json_decode($request->getBody());
        $decode = json_decode($body->body);       
        $decodeId = $decode->id_proyecto + 0;
        $decode = $decode->actualizacion;

            $sql = "UPDATE acciones_especificas SET valor = ? WHERE acciones_especificas.id_accion_especifica = ?";
            $c = 0;   
            

        for ($i=0; $i < count($decode) ; $i++) {             
           
                $db = new DB();
                $db=$db->connection('mapa_soluciones');                
                $accion = $decode[$i]->id_accion_especifica+0;
                $valor = $decode[$i]->valor+0;
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ii", $valor, $accion );
                $stmt->execute(); 
                
                
                if ($stmt->affected_rows === 1) {
                    $c = $c+1;
                    $sql2 = "SELECT `proyectos`.`id_proyecto`, `obras`.* FROM `proyectos` LEFT JOIN `obras` ON `proyectos`.`id_obra` = `obras`.`id_obra` WHERE proyectos.id_proyecto = ?";
                    
                    $resultado = consultasConUnID($sql2 , $decodeId);
                    if (json_decode($resultado[0]["coordenadas"])!== "En Espera") {
                        $obra = json_decode($resultado[0]["coordenadas"]);
                        $obra->features[0]->properties->color = '#26e7a6';
                        $obra = json_encode($obra);
                    } 

                    if ($resultado) {
                        $sql3 = "UPDATE obras SET coordenadas = ? WHERE obras.id_obra = ?";
                        $db = new DB();
                        $db=$db->connection('mapa_soluciones');
                        $stmt = $db->prepare($sql3); 
                        $stmt->bind_param("si" , $obra, $resultado[0]['id_obra']);
                        $stmt->execute(); 

                        if ($stmt) {
                            $sql4 = "UPDATE proyectos SET id_estatus = ? WHERE proyectos.id_proyecto = ?";
                            $db = new DB();
                            $db=$db->connection('mapa_soluciones');
                            $stmt = $db->prepare($sql4); 
                            $estatus = 1;
                            $stmt->bind_param("ii" , $estatus, $decodeId);
                            $stmt->execute();
                        }
                    }
                
               }
                
            

             
        }
         
        if ($c > 0) {
            return "Se han actualizado las acciones";
        }else{
            return "No se lograron actualizar las acciones";
        }

     });



     $app->post('/api/actualizacion/final/proyetos', function (Request $request, Response $response){
       
        $body = json_decode($request->getBody());
        $body = json_decode($body->body);

        $id_obra = $body->{'id_obra'};//Check
        $obra = $body->{'obra'};//Check

        $id_sector = $body->{'id_sector'};//Check
        $sector = $body->{'sector'};//Check
       



        $id_lapso = $body->id_lapso;//Check        
        $lapso_culminacion_final = $body->lapso_culminacion_final;//Check        
        $lapso_culminación_inicio = $body->lapso_culminacion_inicio;//Check

        
        $ejecucion_bolivares_final = $body->ejecucion_bolivares_final;//Check
        $ejecucion_euros_final = $body->ejecucion_euros_final;//Check
        $id_ejecucion_financiera = $body->id_ejecucion_financiera;
        
        
        $id_ciclo = $body->id_ciclo;//Check        
        $ciclo_final = $body->ciclo_final;//Check
        $opcion_ciclo_final = $body->opcion_ciclo_final;//Check

        $id_estado_proyecto = null;
        $color = null;

        $lps_final = $body->lps_final;
        $id_lps = $body->id_lps;

        $id_estatus = $body->id_proyecto_estatus;
        $id_proyecto = $body->id_proyecto;//Check
        


        if ($ciclo_final < 4 && $opcion_ciclo_final === "dias") {
            $id_estado_proyecto = 1;
            $color = "#9191e3";
        }else if ($ciclo_final > 3 && $opcion_ciclo_final === "dias" || $ciclo_final < 45 && $opcion_ciclo_final === "dias" || $ciclo_final > 0 && $opcion_ciclo_final === "semanas" || $ciclo_final < 7 && $opcion_ciclo_final === "semanas" || $ciclo_final === 1 && $opcion_ciclo_final === "meses") {
            $id_estado_proyecto = 2;
            $color = "#ffa219";
        }else if ($ciclo_final > 44 && $opcion_ciclo_final === "dias" || $ciclo_final > 6 && $opcion_ciclo_final === "semanas" || $ciclo_final > 1 && $opcion_ciclo_final === "meses") {
           $id_estado_proyecto = 3;
           $color = "#ea00a2";
        }else {
            $id_estado_proyecto = 3;
            $color = "#ea00a2";
        }

        
        
        $sector->features[0]->properties->color = $color;
        
        
        
        $obra->features[0]->properties->color = "#feb019";
        
        
        


        $lapso = array( $id_lapso , 
                        $lapso_culminacion_final , 
                        $lapso_culminación_inicio);

        $ejecucion_financiera = array(  $ejecucion_bolivares_final , 
                                        $ejecucion_euros_final , 
                                        $id_ejecucion_financiera);

        $lps = array($lps_final , $id_lps);

        $situacion_servicio = array($ciclo_final , 
                                    $opcion_ciclo_final , 
                                    $color , 
                                    $id_ciclo);

        $proyectos = array( $id_estatus , 
                            $id_estado_proyecto, 
                            $id_proyecto);

        $coordenadas = array($obra , 
                             $sector , 
                             $id_obra , 
                             $id_sector);
        
        $check = array( $id_lapso , 
                        $lapso_culminacion_final , 
                        $lapso_culminación_inicio, 
                        $id_ciclo,
                        $ejecucion_bolivares_final , 
                        $ejecucion_euros_final ,  
                        $id_ejecucion_financiera, 
                        $lps_final , 
                        $id_lps, 
                        $id_estatus , 
                        $id_estado_proyecto, 
                        $id_proyecto);
        $contador = 0;

        
    
            for ($i=0; $i < count($check) ; $i++) { 
                if (!isset($check[$i])) {
                        $contador++;
                    }
                }
        

                   
                        $registro = new Registro();
                        return $registro->actualizacionFinal($lapso, 
                                                             $ejecucion_financiera , 
                                                             $lps, 
                                                             $proyectos, 
                                                             $situacion_servicio, 
                                                             $coordenadas);
            
                    


     });
