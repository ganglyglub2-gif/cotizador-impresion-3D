/* 
 * utilidades js
 */

function trim(cadena){
    for(i=0; i<cadena.length; ){
        if(cadena.charAt(i)==" ")
                cadena=cadena.substring(i+1, cadena.length);
        else
            break;
    }
    for(i=cadena.length-1; i>=0; i=cadena.length-1){
        if(cadena.charAt(i)==" ")
                cadena=cadena.substring(0,i);
        else
            break;
    }
    return cadena;
}

function getDiaNombre(num){
    switch(parseInt(num)){
        case 0: return "Domingo";
        case 1: return "Lunes";
        case 2: return "Martes";
        case 3: return "Miércoles";
        case 4: return "Jueves";
        case 5: return "Viernes";
        case 6: return "Sábado";
    }
}

function fechaGuion(fechaTxt){//de dd/mm/aaaa a aaaa-mm-dd
    fechaTxt = trim(fechaTxt);
    if(fechaTxt.substr(2,1) == "/" && fechaTxt.substr(5,1) == "/"){// dd/mm/aaaa
        var fechaArr = fechaTxt.split("/");
        return fechaArr[2]+"-"+fechaArr[1]+"-"+fechaArr[0];
    }
    if(fechaTxt.substr(4,1) == "-" && fechaTxt.substr(7,1) == "-")// aaaa-mm-dd
        return fechaTxt;
    return "";
    
}

function fechaObjeto(fechaTxt){//de dd/mm/aaaa a aaaa-mm-dd
    fechaTxt = trim(fechaTxt);
    if(fechaTxt.substr(2,1) == "/" && fechaTxt.substr(5,1) == "/"){// dd/mm/aaaa
        var fechaArr = fechaTxt.split("/");
        return new Date(parseInt(fechaArr[2]), parseInt(fechaArr[1])-1, parseInt(fechaArr[0]) );
    }
    if(fechaTxt.substr(4,1) == "-" && fechaTxt.substr(7,1) == "-"){// aaaa-mm-dd
        var fechaArr = fechaTxt.split("-");
        return new Date(parseInt(fechaArr[0]), parseInt(fechaArr[1])-1, parseInt(fechaArr[2]) );
    }
    return false;
    
}

function validaFecha(fechaTxt){
    if(fechaTxt.charAt(4) == "-" && fechaTxt.charAt(7) == "-"){//yyyy-mm-dd
        var fechaArr = fechaTxt.split("-");
        var ano= fechaArr[0];
        var mes= fechaArr[1];
        var dia= fechaArr[2];
    }
    if(fechaTxt.charAt(2) == "/" && fechaTxt.charAt(5) == "/"){//dd-mm-aaaa
        var fechaArr = fechaTxt.split("/");
        var ano= fechaArr[2];
        var mes= fechaArr[1];
        var dia= fechaArr[0];
    }

    var d = new Date();
    var anoActual = d.getFullYear();
    if (isNaN(ano) || ano.length < 4 || parseInt(ano, 10) < (anoActual-1)){ return false; }
    if (isNaN(mes) || parseInt(mes, 10) < 1 || parseInt(mes, 10) > 12){ return false; }
    if (isNaN(dia) || parseInt(dia, 10) < 1 || parseInt(dia, 10) > 31){ return false; }
    if (mes == 4 || mes == 6 || mes == 9 || mes== 11) {
        if (dia > 30) { return false; }
    } else{
        if (mes == 2) {
            if(dia <= 28 )
                return true;
            else{
                if ((ano % 4 == 0) && dia == 29) return true;
                else return false;
            }
        }
    }
    return true;
}
