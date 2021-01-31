<?php
  
      
class paginadorIncidencia {
    private $id;
           
    function __construct($id=null, $idPrimerRender, $tipoConsulta){
        $this->id=$id;
        $this->idPrimerRender=$idPrimerRender;
        $this->tipoConsulta=$tipoConsulta;
    }

    function paginadorIncidencias($datos){

        if(!isset($datos[1]))
        {
            $pagina = 1;
        }else{
            $pagina = intval($datos[1]);
        }
        
        $pagina = isset($datos[1]) ?(int)$datos[1]  : 1;

        $regPagina = 20;
        $inicio = ($pagina > 1) ? (($pagina * $regPagina) - $regPagina) : 0 ;
        $where = CondicionalMYSQL($this->idPrimerRender, $this->tipoConsulta,  $this->id);

        $sql = "SELECT SQL_CALC_FOUND_ROWS `proyectos`.`id_proyecto`, `proyectos`.`id_estatus`as `id_proyecto_estatus`, `proyectos`.`id_estado` as `id_proyecto_estado` , `datos`.*, `soluciones`.`nomenclatura`, `ciclos`.*, `ejecucion_financiera`.* ,`inversion`.*, `estados`.`estado`, `municipios`.`municipio`, `parroquias`.`parroquia`, `hidrologicas`.`hidrologica`, `estatus`.`estatus`, `lapso`.*, `lps`.* ,  `poblacion`.`poblacion_inicial`, `situaciones de servicio`.`situacion_de_servicio`, `soluciones`.`solucion`
                FROM proyectos 
                LEFT JOIN datos ON proyectos.id_datos = datos.id_datos
                LEFT JOIN soluciones ON datos.id_tipo_solucion = soluciones.id_solucion 
                LEFT JOIN estados ON proyectos.id_estado = estados.id_estado
                LEFT JOIN municipios ON proyectos.id_municipio = municipios.id_municipio 
	            LEFT JOIN parroquias ON parroquias.id_parroquia = proyectos.id_parroquia
                LEFT JOIN ejecucion_financiera ON proyectos.id_ejecucion_financiera = `ejecucion_financiera`.`id_ejecucion_financiera` 
                LEFT JOIN inversion ON inversion.id_ejecucion_financiera = `ejecucion_financiera`.`id_ejecucion_financiera`
                LEFT JOIN ciclos ON proyectos.id_ciclo = ciclos.id_ciclo
                LEFT JOIN `hidrologicas` ON `hidrologicas`.`id_hidrologica`= `proyectos`.`id_hidrologica`
                LEFT JOIN `lapso` ON `proyectos`.`id_lapso` = `lapso`.`id_lapso` 
                LEFT JOIN `lps` ON `proyectos`.`id_lps` = `lps`.`id_lps` 
                LEFT JOIN `poblacion` ON `proyectos`.`id_poblacion` = `poblacion`.`id_problacion` 
                LEFT JOIN `situaciones de servicio` ON proyectos.id_estado_proyecto = `situaciones de servicio`.id_situacion_de_servicio
                LEFT JOIN acciones_especificas ON acciones_especificas.id_datos = datos.id_datos 
                LEFT JOIN intervencion ON acciones_especificas.id_intervencion = intervencion.id_intervencion 
                LEFT JOIN unidades ON acciones_especificas.id_unidades = unidades.id_unidades
                LEFT JOIN estatus ON proyectos.id_estatus = estatus.id_estatus {$where} LIMIT $inicio , $regPagina";

        if ($where !== "") {
            if ($this->tipoConsulta!==null) {
                if ($this->idPrimerRender===20) {
                    $db = New DB();
                    $resultado = $db->consultaAll('mapa',$sql, $this->id);
                }else{
                    $db = New DB();
                    $param= merge([$this->idPrimerRender], ...$this->id);
                    $resultado = $db->consultaAll('mapa',$sql, $param);
                }
            }else {
                $db = New DB();
                $resultado = $db->consultaAll('mapa',$sql, [$this->idPrimerRender]);
            }
        }else {
            $db = New DB();
            $resultado = $db->consultaAll('mapa',$sql);
        }

        for ($i=0; $i < count($resultado); $i++) { 
             $resultado[$i]["id_search"] ="#".$resultado[$i]["nomenclatura"]."-".$resultado[$i]["id_proyecto"];        
        }

        $nroPaginas = ceil($datos[0] / $regPagina);

        
            return [
                "cantidadProyectos" => $datos[0],
                "cantidadPaginas" => $nroPaginas,
                "proyectos" => $resultado
            ];       
        
        

        

        
    }

}



?>