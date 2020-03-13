<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ReporteSegController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         return view('reporte_seg.index');
    }

    public function indexPost(Request $request)
    {
    	$daterange = $request->input('daterange');
        //Se divide la fecha del rango en inicio y fin
        $start = substr($daterange, 0, 10);
        //se cambia el formato de la fecha
        $start =  date("Y-m-d", strtotime($start));
        $end = substr($daterange, 13, 10);
        //Se cambia el formato de la fecha
        $end = date("Y-m-d", strtotime($end));
        $resultados = $this->Reporte_seg($start, $end);
        return view('reporte_seg.index', compact('resultados'));
    }

    public function Reporte_seg($start, $end){
        $this->start = $start;
        $this->end = $end;
        //Obtener información de la tabla seg por fecha
        $resultados_seg = $this->get_data_seg($start, $end);
        //Cast de los datos obtenidos tipo colección a array
        $resultados_seg = (array)$resultados_seg;
        $folios = [];
        //Loop para crear array de los numeros de folio de la tabla seg 
        foreach($resultados_seg as $resultado_seg)
        {
            array_push($folios, $resultado_seg->solicitud);  
        }
        //Se obtienen los datos de la base cece comparando con el arreglo de folios    
        $datos_cece = $this->get_data_cece($folios);
        //Se crea una nueva colección 
        $resultados = new Collection;
        $datos_completos =[];
        //Loop para comparar los folios de los datos y reunir la información
        foreach ($resultados_seg as $dato_seg) 
        {
            //Cast de dato_seg a array para hacer comparativa
            $dato_seg = (array)$dato_seg;
            foreach ($datos_cece as $dato_cece)
            {
                //Cast de dato_cece a array para hacer comparativa
                $dato_cece = (array)$dato_cece;
                //Condicional para verificar si los folios son los mismos
                if ($dato_seg['solicitud'] == $dato_cece['fol_seg']) 
                {
                    //unión de los datos en resultados 
                    $resultados = array_merge($dato_cece, $dato_seg);
                    //Guardar los resultados en datos completos
                    array_push($datos_completos, $resultados);
                    //dd($datos_completos);
                }
            }
        }        
        return $datos_completos;
    }

    public function get_data_seg($start, $end)
    {
        //Se obtienen todos los datos del la tabla seg comparandolo con la fecha
        $resultados = DB::select("SELECT * FROM seg
                                where fecha_solicitud between '".$start."' and '".$end."'
                               ");
        return $resultados;
    }

    public function get_data_cece($folios)
    {
        $array = [];
        //Se crea un loop para comparar los folios recibidos con los que existen en la base cece 
        foreach($folios as $folio) {
             $data_array = DB::connection('oracle')->select("SELECT 
                                            CLIENTE.ID,
                                            CLIENTE.NUMERO_CLIENTE_DISH,
                                            VALIDACION.TEL_ASIG,
                                            VALIDACION.FOL_SEG,
                                            CLIENTE.NOMBRE_CTE,
                                            CLIENTE.APP,
                                            CLIENTE.APM,
                                            VALIDACION.FECHA_ALTA,
                                            VALIDACION.FECHA_ORD,
                                            VALIDACION.HORA_INST,
                                            VALIDACION.VAL_FACT_VEL,
                                            VALIDACION.VEL_ALC,
                                            VALIDACION.MOTIVO_OBJ
                                        FROM SISTEMA 
                                        INNER JOIN VENDEDOR 
                                        ON SISTEMA.ID_CLIENTE=VENDEDOR.ID_CLIENTE 
                                        INNER JOIN CLIENTE 
                                        ON VENDEDOR.ID_CLIENTE=CLIENTE.ID 
                                        INNER JOIN DOMICILIO 
                                        ON CLIENTE.ID=DOMICILIO.ID_CLIENTE 
                                        INNER JOIN VALIDACION 
                                        ON DOMICILIO.ID_CLIENTE=VALIDACION.ID_CLIENTE 
                                        WHERE VALIDACION.FOL_SEG = '$folio'
                                        ");
            //Loop para guardar los datos en un arreglo
            foreach ($data_array as $data) {
                array_push($array, $data);
            }
        }
        //Retornar arreglo;
        return $array;
    }

    
}
