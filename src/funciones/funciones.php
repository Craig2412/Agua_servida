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
    $stmt->bind_param("i", $id );
    $stmt->execute();
    $resultado = $stmt->get_result();
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
