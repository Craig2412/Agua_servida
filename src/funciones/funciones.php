<?php 


function Token ($header) {
    $auth = new Auth();
    $token = explode('"',$header);
    $newToken = $token[1];
    $valor = $auth->Check($newToken);

    if ($valor === false) {
        return "token vacio";
    }
    
    return $auth->GetData($newToken);
}



//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//||||||||||||||||||||||||||||||||||||Consultas usuarios_m_soluciones||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||

function consultasBasicasUser($sql){
    $db = new DB();
    $db=$db->connection('usuarios_m_soluciones');
    $stmt = $db->prepare($sql); 
    $stmt->execute();
    
    $stmt = $stmt->get_result();
    $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
    return $resultado;
}


function consultasUserUnID($sql,$id){
    $db = new DB();
    $db=$db->connection('usuarios_m_soluciones');
    $stmt = $db->prepare($sql); 
    $stmt->bind_param("i", $id );
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado;
    
}

function EliminarBarrasURL ($array) {
    return explode('/', $array);
}

function consultasUserWhereNick($sql, $nick){
    $db = new DB();
    $db=$db->connection('usuarios_m_soluciones');
    $stmt = $db->prepare($sql); 
    $stmt->bind_param("s", $nick);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado = $resultado->fetch_object();
}
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//||||||||||||||||||||||||||||||||||||Consultas Mapa_Solucines|||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
//|||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||

function consultasBasicas($sql){
    $db = new DB();
    $db=$db->connection('mapa_soluciones');
    $stmt = $db->prepare($sql); 
    $stmt->execute();
    
    $stmt = $stmt->get_result();
    $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
    return $resultado;
}

function consultasConUnID($sql,$id){
    $db = new DB();
    $db=$db->connection('mapa_soluciones');
    $stmt = $db->prepare($sql); 
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt = $stmt->get_result();
    $resultado = $stmt->fetch_all(MYSQLI_ASSOC);
    return $resultado;
    
}
function updateConDosID($sql,$valor1, $valor2){
    $db = new DB();
    $db=$db->connection('mapa_soluciones');
    $stmt = $db->prepare($sql); 
    $stmt->bind_param("si", $valor1, $valor2 );
    $stmt->execute();
    if ($stmt->affected_rows>0) {
        return true;
    }else{
        return false;
    }
    
    
}

function consultaTresValoresEnteros($sql, $parametro1 , $parametro2 , $parametro3){
    $db = new DB();
    $db=$db->connection('mapa_soluciones');
    $stmt = $db->prepare($sql); 
    $stmt->bind_param("iii", $parametro1 , $parametro2 , $parametro3 );
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado = $resultado->fetch_all(MYSQLI_ASSOC);    

}

function ExtraerConsultaParametro ($valor, $tipo = null) {
    $tipoConsulta = array('', 'proyectos.id_estado' , 'proyectos.id_municipio' ,  'proyectos.id_hidrologica', 'proyectos.id_parroquia' , 'proyectos.id_estatus' , 'poblacion.poblacion_inicial' , 'proyectos.id_proyecto' );//"proyectos.id_estado"
    if ($valor === "busqueda") {
        if ($tipo === 'id') {
            $typoConsultaBusqueda = [$tipoConsulta[7]];
            return $typoConsultaBusqueda;
        }
        $typoConsultaBusqueda = [$tipoConsulta[7],'datos.nombre', 'estado', 'municipio', 'parroquia', 'estatus.estatus', 'ciclos.opcion_ciclo_inicial', 'soluciones.solucion', 'soluciones.nomenclatura', 'situacion_de_servicio', 'acciones_especificas.accion_especifica', 'intervencion.nombre' , 'unidades.unidad'];
        return $typoConsultaBusqueda;
    }
    return $tipoConsulta[$valor];
}

function CondicionalMYSQL ($idPrimerRender, $campoCondicional=null,  $id = null){

    if (gettype($campoCondicional) === "array") {
        $where = "";

        for ($i=0; $i < count($campoCondicional); $i++) { 
            $whereNew = count($campoCondicional)>1?"{$campoCondicional[$i]} LIKE ? OR ":"{$campoCondicional[$i]} = ?";
            $where = $where.$whereNew;
        }

        if ($idPrimerRender !== 20) {
            if (count($campoCondicional)>1) {
                $where = 'WHERE proyectos.id_hidrologica = ? AND '.$where;
                $where = substr($where, 0, -4);
            } else{
                $where = "WHERE proyectos.id_hidrologica = ? AND ".$where;
            }
        }else {
            
            if (count($campoCondicional)>1) {
                $where = "WHERE ".$where;
                
                $where = substr($where, 0, -4);
            } else{
                $where = "WHERE ".$where;
            }
            
        } 
    } else{
        if ($idPrimerRender !== 20 && !$campoCondicional) {
            $where = "WHERE proyectos.id_hidrologica = ?";
        }
        else if (!$campoCondicional) {
            return "";
        } 
        else if($idPrimerRender !== 20 && $id !== 0){
            $where ="WHERE proyectos.id_hidrologica = ? AND {$campoCondicional} = ?";
        }else {
            $where = "WHERE {$campoCondicional} = ?";
        }
    }
 
    return $where;
}


function typesConsultas ($array){
    $types = "";
    $newArray = [];
    
    foreach ($array as $key => $value) {
        if (gettype($array) === 'object') {
            $array = $object = (array) $array;
        }
        if (is_numeric($value) === true) {
            $types = $types."i";
            $array["$key"] = $array["$key"]+0;
            array_push($newArray, $array["$key"]);
        } else {
            $types = $types."s";
            array_push($newArray, $array["$key"]);
        }
    }
       
    return array($types, $newArray);
}


function decodeJsonArray($array, $type) {
    if ($type === 'sector') {
        for ($i=0; $i <count($array) ; $i++) { 
            $array[$i]['sector'] = json_decode($array[$i]['sector']);
        }
        
    } else if ($type === 'obra') {
        for ($i=0; $i <count($array) ; $i++) { 
            $array[$i]['obras'] = json_decode($array[$i]['obras']);
        }
    } else {
        for ($i=0; $i <count($array) ; $i++) { 
            $array[$i]['obras'] = json_decode($array[$i]['obras']);
            $array[$i]['sector'] = json_decode($array[$i]['sector']);
        }
    }

    return $array;
}
