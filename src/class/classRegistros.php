<?php

class Registro {

    private $id_proyecto;
    private $accion;

    function __construct($id_proyecto=null , $accion=null){
        $this->id_proyecto=$id_proyecto;
        $this->accion=$accion;
    }


    function actualizacionFinal($lapso, $ejecucion_financiera , $lps, $proyectos , $situacion_servicio,$coordenadas){

        $sql = "UPDATE lapso SET lapso_culminación_inicio = ?, lapso_culminación_final = ? WHERE lapso.id_lapso = ?";
        
        try {
            $db = new DB();
            $db=$db->connection('mapa_soluciones');
            $stmt = $db->prepare($sql); 
            $stmt->bind_param("ssi" , $lapso[2] , $lapso[1] , $lapso[0]);
            $stmt->execute();


                        if ($stmt) {
                            $sql = "UPDATE ejecucion_financiera SET ejecucion_bolivares_final = ?, ejecucion_euros_final = ? WHERE ejecucion_financiera.id_ejecucion_financiera = ?";
                            $db = new DB();
                            $db=$db->connection('mapa_soluciones');
                            $stmt = $db->prepare($sql); 
                            $stmt->bind_param("ddi" , $ejecucion_financiera[0] , $ejecucion_financiera[1] , $ejecucion_financiera[2]);
                            $stmt->execute();


                                if ($stmt) {

                                    $sql = "UPDATE lps SET lps_final = ? WHERE lps.id_lps = ?";
                                    $db = new DB();
                                    $db=$db->connection('mapa_soluciones');
                                    $stmt = $db->prepare($sql); 
                                    $stmt->bind_param("ii" , $lps[0] , $lps[1] );
                                    $stmt->execute();

                                    if ($stmt) {
                                        $sql = "UPDATE ciclos SET ciclo_final = ? , opcion_ciclo_final = ? WHERE ciclos.id_ciclo = ?";
                                        $db = new DB();
                                        $db=$db->connection('mapa_soluciones');
                                        $stmt = $db->prepare($sql); 
                                        $stmt->bind_param("isi" , $situacion_servicio[0] , $situacion_servicio[1] , $situacion_servicio[3] );
                                        $stmt->execute();

                                        if ($stmt) {

                                            $sql = "UPDATE proyectos SET id_estatus = ?, id_estado_proyecto = ? WHERE proyectos.id_proyecto = ?";
                                            $db = new DB();
                                            $db=$db->connection('mapa_soluciones');
                                            $stmt = $db->prepare($sql); 
                                            $stmt->bind_param("iii" , $proyectos[0] , $proyectos[1], $proyectos[2]);
                                            $stmt->execute();
                                            
                                            if ($stmt) {
                                                $coordenadas[0] = json_encode($coordenadas[0]);
                                                $sql = "UPDATE obras SET coordenadas = ? WHERE obras.id_obra = ?";
                                                $db = new DB();
                                                $db=$db->connection('mapa_soluciones');
                                                $stmt = $db->prepare($sql); 
                                                $stmt->bind_param("si" , $coordenadas[0] , $coordenadas[2]);
                                                $stmt->execute(); 

                                                if ($stmt) {
                                                    
                                                    $coordenadas[1] = json_encode($coordenadas[1]);
                                                    $sql = "UPDATE sector SET coordenadas = ? WHERE sector.id_sector = ?";
                                                    $db = new DB();
                                                    $db=$db->connection('mapa_soluciones');
                                                    $stmt = $db->prepare($sql); 
                                                    $stmt->bind_param("si" , $coordenadas[1] , $coordenadas[3]);
                                                    $stmt->execute();

                                                    if ($stmt) {
                                                        $sql = "UPDATE proyectos SET id_estatus = ?, id_estado_proyecto = ? WHERE proyectos.id_proyecto = ?";
                                                        $db = new DB();
                                                        $db=$db->connection('mapa_soluciones');
                                                        $stmt = $db->prepare($sql); 
                                                        $stmt->bind_param("iii" , $proyectos[0] , $proyectos[1], $proyectos[2]);
                                                        $stmt->execute();
                                                        return "Se ha actualizado";


                                                                    }
                                                
                                                                }
                                                        }
                                                }
                                        }                                                
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
    }    
    
    function crearProyectos($datos , $acciones_especificas , $obras , $sector, $lapso , $ciclos , $ejecucion_financiera , $inversion , $poblacion_inicial , $lps_inicial , $proyecto){
        $fecha = date('Y-m-d');
        $sql = "INSERT INTO datos (id_datos, usuario, nombre, id_tipo_solucion, descripcion, accion_general, fecha) VALUES (NULL, ?, ? , ? , ? , ?, ?)";
        $db = new DB();
        $id =$datos[1]+0;
        $stmt = $db->consultaAll('mapa', $sql, [$datos[4], $datos[0] , $id , $datos[2] , $datos[3], $fecha]);
           
        if ($stmt) {

            $id_datos = $stmt->{"insert_id"};
            $sql = "INSERT INTO acciones_especificas (id_accion_especifica, accion_especifica, id_intervencion, cantidad, id_unidades, id_datos, valor) VALUES (NULL, ? , ? , ? , ? , ? , 0 );";
            for ($i=0; $i < count($acciones_especificas) ; $i++) { 

                $db = new DB();                
                $accion = [$acciones_especificas[$i]->descripcionAccion, $acciones_especificas[$i]->intervencion+0, $acciones_especificas[$i]->cantidad+0, $acciones_especificas[$i]->unidad+0, $id_datos];
                $stmt = $db->consultaAll('mapa', $sql, $accion);
                
            }

            if ($stmt) {     

                $sql = "INSERT INTO obras (id_obra, coordenadas) VALUES (NULL, ?)";
                $db = new DB();
                $obras = json_encode($obras);
                $stmt = $db->consultaAll('mapa', $sql, [$obras]); 

                if ($stmt) {
                    
                    $id_obras = $stmt->{"insert_id"};
                    $sql = "INSERT INTO sector (id_sector, coordenadas) VALUES (NULL, ?)";
                    $db = new DB();
                    $sector = json_encode($sector);
                    $stmt = $db->consultaAll('mapa', $sql, [$sector]);

                    if ($stmt) { 

                        $id_sector = $stmt->{"insert_id"};
                        $sql = "INSERT INTO lapso (id_lapso, lapso_estimado_inicio, lapso_estimado_culminacion, lapso_culminación_inicio, lapso_culminación_final) VALUES (NULL, ? , ? , 0 , 0 );";
                        $db = new DB();
                        $stmt = $db->consultaAll('mapa', $sql, [$lapso[0] , $lapso[1]]);

                        if ($stmt) { 

                            $id_lapso = $stmt->{"insert_id"};
                            $sql = "INSERT INTO ciclos (id_ciclo, ciclo_inicial, opcion_ciclo_inicial, ciclo_final, opcion_ciclo_final) VALUES (NULL, ? , ? , 0 , 'sin asignar' )";
                            $db = new DB();
                            $stmt = $db->consultaAll('mapa', $sql, [$ciclos[0] , $ciclos[1]]);
                            
                            if ($stmt) { 

                                $id_ciclos = $stmt->{"insert_id"};
                                $sql = "INSERT INTO ejecucion_financiera (id_ejecucion_financiera, ejecucion_bolivares, ejecucion_euros, ejecucion_bolivares_final, ejecucion_euros_final) VALUES (NULL, ? , ?, 0 , 0)";
                                $db = new DB();                                         
                                $stmt = $db->consultaAll('mapa', $sql, [$ejecucion_financiera[0] , $ejecucion_financiera[1]]);
                                
                                if ($stmt) { 

                                    $id_ejecucion_financiera = $stmt->{"insert_id"};
                                    $sql = "INSERT INTO inversion (id_inversion, inversion_bolivares, id_ejecucion_financiera, inversion_euros) VALUES (NULL, ? , ? , ? )";
                                    $db = new DB();
                                    $stmt = $db->consultaAll('mapa', $sql, [$inversion[0], $id_ejecucion_financiera, $inversion[1]]);

                                    if ($stmt) { 

                                        $sql = "INSERT INTO poblacion (id_problacion, poblacion_inicial) VALUES (NULL, ? )";
                                        $db = new DB(); 
                                        $stmt = $db->consultaAll('mapa', $sql, [$poblacion_inicial]);

                                        if ($stmt) { 

                                            $id_poblacion = $stmt->{"insert_id"};
                                            $sql = "INSERT INTO lps (id_lps, lps_inicial, lps_final) VALUES (NULL, ? , 0 )";
                                            $db = new DB(); 
                                            $stmt = $db->consultaAll('mapa', $sql, [$lps_inicial]);

                                            if ($stmt){  

                                                $id_lps = $stmt->{"insert_id"};
                                                $sql = "INSERT INTO proyectos (id_proyecto, id_datos, id_hidrologica, id_estado, id_municipio, id_parroquia, id_obra, id_sector, id_lapso, id_ciclo, id_estatus, id_estado_proyecto, id_ejecucion_financiera, id_poblacion, id_lps) 
                                                        VALUES (NULL, ? ,  ? , ? , ? , ? , ?, ? , ? , ?, 0 , ? , ? , ?, ?)";
                                                $db = new DB();
                                                $proyecto = [$id_datos , $proyecto[0] , $proyecto[1] , $proyecto[2] , $proyecto[3]  , $id_obras , $id_sector , $id_lapso , $id_ciclos , $proyecto[5] , $id_ejecucion_financiera , $id_poblacion , $id_lps];
                                                $stmt = $db->consultaAll('mapa', $sql, $proyecto);
                                                $id_proyecto = $stmt->{"insert_id"};
                                                
                                                    if ($stmt) {
                                                        $sql = "SELECT `hidrologicas`.`hidrologica`, `hidrologicas`.`id_hidrologica`, `estados`.`estado` FROM `hidrologicas` LEFT JOIN `estados` ON `hidrologicas`.`id_estado` = `estados`.`id_estado` WHERE `hidrologicas`.`id_estado`=? or `hidrologicas`.`id_estado2`=? OR `hidrologicas`.`id_estado3` = ?";
                                                
                                                        try {
                                                            $db = new DB();
                                                            $stmt = $db->consultaAll('mapa', $sql, [$proyecto[1],$proyecto[1], $proyecto[1]]);
                                                            $id_hidrologica = $stmt[0]["id_hidrologica"];
                                                            $hidrologica = $stmt[0]["hidrologica"];
                                                            $estado = $stmt[0]["estado"];

                                                                if ($stmt) {
                                                                    $sql = "SELECT municipios.municipio
                                                                    FROM municipios WHERE municipios.id_municipio = ?";
                                                                    $db = new DB();
                                                                    $stmt = $db->consultaAll('mapa', $sql, [$proyecto[2]]);
                                                                    $municipio = $stmt[0]["municipio"];
                                                                    
                                                                }
                                                                    if ($stmt) {
                                                                        
                                                                        $sql = "SELECT `soluciones`.`solucion`
                                                                        FROM `soluciones` WHERE `soluciones`.`id_solucion` = ?";
                                                                        $db = new DB();
                                                                        $id = $datos[1]+0;
                                                                        $stmt = $db->consultaAll('mapa', $sql, [$id]);   
                                                                        $solucion = $stmt[0]["solucion"];
                                                                        
                                                                    }

                                                            $array = [
                                                                "mensaje" => "Proyecto Creado",
                                                                "proyecto"=> $datos[0],
                                                                "id_proyecto" => $id_proyecto,
                                                                "hidrologica" => $hidrologica,
                                                                "id_hidrologica" => $id_hidrologica,
                                                                "estado" => $estado,
                                                                "municipio" => $municipio,
                                                                "solucion" => $solucion
                                                            ];
                                                            return $array;
                                                                                    
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
                                                
                                            }
                                        }
                                    }            
                                }
                            }
                        }
                    }    
                }    
            }
        }    

    }
}


//Estado, hidrologica, nombre de proyecto, id?proyecto







?>