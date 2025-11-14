<?php
/* 
 * Funciones de utilidad
 */

function fechaGuion($fechaTxt){//convierte fecha a guiones
    $fechaTxt = trim($fechaTxt);
    if(substr($fechaTxt,2,1) == "/" && substr($fechaTxt,5,1) == "/"){// dd/mm/aaaa
        $fechaArr = explode("/", $fechaTxt);
        return $fechaArr[2]."-".$fechaArr[1]."-".$fechaArr[0];
    }
    if(substr($fechaTxt,4,1) == "-" && substr($fechaTxt,7,1) == "-")// aaaa-mm-dd
        return $fechaTxt;
    return "";
}
function fechaSlash($fechaTxt){//convierte fecha a /
    $fechaTxt = trim($fechaTxt);
    if(substr($fechaTxt,2,1) == "/" && substr($fechaTxt,5,1) == "/"){// dd/mm/aaaa
        return $fechaTxt;
    }
    if(substr($fechaTxt,4,1) == "-" && substr($fechaTxt,7,1) == "-"){// aaaa-mm-dd
        $fechaArr = explode("-", $fechaTxt);
        return $fechaArr[2]."/".$fechaArr[1]."/".$fechaArr[0];
    }
    return "";
}

function fechaTexto($fechaTxt, $showYear = true){//convierte fecha a cadena de texto
    $fechaTxt = trim($fechaTxt);
    if(substr($fechaTxt,2,1) == "/" && substr($fechaTxt,5,1) == "/"){// dd/mm/aaaa
        $fechaArr = explode("/", $fechaTxt);
        if($showYear)
            return intval($fechaArr[0])." de ".mesNombre($fechaArr[1])." de ".$fechaArr[2];
        else
            return intval($fechaArr[0])." de ".mesNombre($fechaArr[1]);
    }
    if(substr($fechaTxt,4,1) == "-" && substr($fechaTxt,7,1) == "-"){// aaaa-mm-dd
        $fechaArr = explode("-", $fechaTxt);
        if($showYear)
            return intval($fechaArr[2])." de ".mesNombre($fechaArr[1])." de ".$fechaArr[0];
        else
            return intval($fechaArr[2])." de ".mesNombre($fechaArr[1]);
    }
    return "";
}

function mesNombre($num){
    $meses=array(1=>"enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre");
    return $meses[intval($num)];
}

function diaNombre($num){
    $dias=array("domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado");
    return $dias[intval($num)];
}

function horaMin($arr, $campo = "Horario_hora"){
    $min = "";
    foreach($arr as $horario){
        if($min == "" || date('H:i', strtotime($horario[$campo])) < date('H:i', strtotime($min))){
            $min = $horario[$campo];
        }
    }
    return date('H:i', strtotime($min));
}

function horaMax($arr, $campo = "Horario_hora_final"){
    $max = "";
    foreach($arr as $horario){
        if($max == "" || date('H:i', strtotime($horario[$campo])) > date('H:i', strtotime($max))){
            $max = $horario[$campo];
        }
    }
    return date('H:i', strtotime($max));
}
function duracionMinutos($fechahora_i, $fechahora_f){
    return round((strtotime($fechahora_f) - strtotime($fechahora_i)) / 60,2);
}

function validaPassword($pass){
    $expr = '/^\S*(?=\S{5,})(?=\S*[a-zA-Z])(?=\S*[\d])(?=\S*[\W])\S*$/';
    return preg_match($expr, $pass);
}

function quitaNumeros($words){
    $words = strtoupper($words);
    return preg_replace('/\d/', '', $words );
}
function quitaLetras($words){
    $words = strtoupper($words);
    return preg_replace("/[^0-9]/", "", $words);
}

function getIniciales($materia){
    $ret = '';
    $materia = str_ireplace( array("Á","É","Í","Ó","Ú","Ñ","Ä","Ë","Ï","Ö","Ü","Â","Ê","Î","Ô","Û","Ã"), array("A","E","I","O","U","N","A","E","I","O","U","A","E","I","O","U","A"), utf8_encode($materia));
    foreach (explode(' ', $materia) as $word){
        if(ctype_alpha($word[0]))
            $ret .= $word[0];
    }
    return strtoupper($ret); 
}