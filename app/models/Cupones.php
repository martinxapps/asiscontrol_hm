<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace app\models;

use app\models as Model;
use Ocrend\Kernel\Models\IModels;
use Ocrend\Kernel\Models\Models;
use Ocrend\Kernel\Models\ModelsException;
use Ocrend\Kernel\Router\IRouter;
use SoapClient;

/**
 * Modelo Cupones
 */
class Cupones extends Models implements IModels
{
    # Variables de clase
    private $pstrSessionKey = 0;
    private $val            = null;
    private $sortField      = 'ROWNUM_';
    private $sortType       = 'desc'; # desc
    private $offset         = 1;
    private $limit          = 25;
    private $searchField    = null;
    private $startDate      = null;
    private $endDate        = null;
    private $tresMeses      = null;

    # Variables de conexion
    private $usuario        = 'mchang';
    private $pass           = '1501508480';
    private $cadenaconexion = '(    DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=172.16.3.247)(PORT=1521)))(CONNECT_DATA=(SID=conclina)))';

    /**
     * Variables privadas
     * @return void
     */

    private function conectar_Oracle()
    {
        // Conectar con Oracle:
        $conexion = oci_connect(
            $this->usuario,
            $this->pass,
            $this->cadenaconexion,
            'AL32UTF8' // Configuracion para UTF8
        ) or die("Error al conectar : " . oci_error());

        return $conexion;
    }

    public function getReporteCupones(): array
    {

        try {

            $sql = "SELECT A.NUMERO_CUPON, A.NUMERO_ID, A.NOMBRES, A.EMAIL, A.CANAL, A.TIPO_ATENCION, A.FECHA_REGISTRO, A.CANJEADO,
                   D.USUARIO_CREA USR_ADMITE, D.FECHA_ADMISION FECHA_CANJE,  B.FK_PACIENTE HCL, B.FK_ADMISION ADM, fun_busca_nombre_pte(B.FK_PACIENTE) PACIENTE,
                   NVL(E.VALOR_HOSPITAL,0)- NVL(E.DCTO_HOSPITAL,0)+ NVL(E.IVA_HOSPITAL,0) VALOR_PREFACTURA, E.ESTADO,
                  AA.DESCRIPCION ESPECIALIDAD, CENTRO.DESC_CC CENTRO_COSTO,
                   F.DESCRIPCION CONVENIO, fun_busca_fp_cupones(e.pk_numero_prefactura) Forma_pago
            FROM CAD_CUPONES_WEB A, CAD_CUPONES B, CAD_ADMISIONES D, CCF_PREFACTURAS E, AAS_ESPECIALIDADES AA, CPC_PLANES F,
                 (SELECT DISTINCT(AR.DESCRIP_CC) DESC_CC, DP.PK_FK_PREFACTURA_NUMERO PREFACTURA
                   FROM CCF_DETALLES_PREFACTURA DP, ARCGCECO AR
                   WHERE DP.CENTRO_COSTO = AR.CENTRO(+)) CENTRO
            --WHERE TRUNC(A.FECHA_REGISTRO) BETWEEN '22/OCT/2018' AND trunc(sysdate)--
            WHERE TRUNC(A.FECHA_REGISTRO) >= '22/OCT/2018'
            --and d.pk_fk_paciente = 25464501 and d.pk_numero_admision = 13
            AND A.NUMERO_CUPON = B.NUMERO_CUPON_WEB(+)
            AND B.FK_PACIENTE = D.PK_FK_PACIENTE(+) AND B.FK_ADMISION = D.PK_NUMERO_ADMISION(+)
            AND D.PK_FK_PACIENTE = E.FK_PACIENTE(+) AND D.PK_NUMERO_ADMISION = E.FK_ADMISION(+)
            AND D.FK_ESPECIALIDAD = AA.PK_CODIGO(+) AND E.FK_PLN_CODIGO = F.PK_CODIGO(+)
            AND E.PK_NUMERO_PREFACTURA = CENTRO.PREFACTURA(+)
            ORDER BY A.FECHA_REGISTRO ASC";

            $stmt = oci_parse($this->conectar_Oracle(), $sql); // Preparar la sentencia
            $ok   = oci_execute($stmt); // Ejecutar la sentencia
            $arr  = array();
            $NUM  = 1; // ITERADOR

            if ($ok == true) {

                if ($obj = oci_fetch_object($stmt)) {

                    do {

                        $arr[] = (array) $obj;

                    } while ($obj = oci_fetch_object($stmt));

                } else {

                    throw new ModelsException('No existen elementos.', 4080);
                }

            } else {

                throw new ModelsException('Error en consulta BDD.', 4080);
            }

            oci_free_statement($stmt); // Liberar los recursos asociados a una sentencia o cursor
            oci_close($this->conectar_Oracle());

            # Ya no existe resultadso
            if (count($arr) == 0) {
                throw new ModelsException('No existe más resultados.', 4080);
            }

            # Devolver Información
            return array(
                'status'     => $ok,
                'customData' => $arr,
            );

        } catch (ModelsException $e) {

            if ($e->getCode() == 4080) {

                return array(
                    'status'     => false,
                    'customData' => false,
                    'message'    => $e->getMessage(),
                    'errorCode'  => 4080,
                );

            }

            return array('status' => false, 'message' => $e->getMessage(), 'errorCode' => $e->getCode());

        }

    }

    # Metodo LOGIN webservice laboratorio ROCHE
    public function wsLab_LOGIN()
    {

        try {

            $client = new SoapClient(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'zdk.ws.wSessions.wsdl.xml');

            $Login = $client->Login(array(
                "pstrUserName" => "CONSULTA",
                "pstrPassword" => "CONSULTA1",
            ));

            # Guaradar  KEY de session WS
            $this->pstrSessionKey = $Login->LoginResult;

            # Retorna KEY de session WS
            return $Login->LoginResult;

        } catch (SoapFault $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }

    }

    # Metodo LOGOUT webservice laboratorio ROCHE
    public function wsLab_LOGOUT()
    {

        try {

            # INICIAR SESSION
            # $this->wsLab_LOGIN();

            $client = new SoapClient(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'zdk.ws.wSessions.wsdl.xml');

            $Logout = $client->Logout(array(
                "pstrSessionKey" => $this->pstrSessionKey,
            ));

            return $Logout->LogoutResult;

        } catch (SoapFault $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }

    }

    # Metodo wReports webservice laboratorio ROCHEdevuelve el resultado pdf del paciente
    public function wsLab_GET_REPORT_PDF(string $SC, string $FECHA)
    {

        try {

            # INICIAR SESSION
            $this->wsLab_LOGIN();

            $client = new SoapClient(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wso.ws.wReports.wsdl.xml');

            $FECHA_final = explode('-', $FECHA);

            $Preview = $client->Preview(array(
                "pstrSessionKey"        => $this->pstrSessionKey,
                "pstrSampleID"          => $SC, # '0015052333',
                "pstrRegisterDate"      => $FECHA_final[2] . '-' . $FECHA_final[1] . '-' . $FECHA_final[0], # '2018-11-05',
                "pstrFormatDescription" => 'METROPOLITANO',
                "pstrPrintTarget"       => 'Destino por defecto',
            ));

            # CERRAR SESSION POR LICENCIAS HSF
            $this->wsLab_LOGOUT();

            if (isset($Preview->PreviewResult)) {
                return array(
                    'status' => true,
                    'data'   => array('_PDF' => str_replace("SERVER-ROCHE", "resultados.hmetro.med.ec", $Preview->PreviewResult),
                    ),
                );
            }

            #
            throw new ModelsException('No existe el documento solicitado.', 4080);

        } catch (SoapFault $e) {
            return array('success' => false, 'message' => $e->getMessage(), 'errorCode' => $e->getCode());

        } catch (ModelsException $b) {
            return array('success' => false, 'message' => $b->getMessage(), 'errorCode' => $b->getCode());
        }

    }

    # Ordenar array por campo
    public function orderMultiDimensionalArray($toOrderArray, $field, $inverse = 'desc')
    {
        $position = array();
        $newRow   = array();
        foreach ($toOrderArray as $key => $row) {
            $position[$key] = $row[$field];
            $newRow[$key]   = $row;
        }
        if ($inverse == 'desc') {
            arsort($position);
        } else {
            asort($position);
        }
        $returnArray = array();
        foreach ($position as $key => $pos) {
            $returnArray[] = $newRow[$key];
        }
        return $returnArray;
    }

    private function get_Order_Pagination(array $arr_input)
    {
        # SI ES DESCENDENTE

        $arr = array();
        $NUM = 1;

        if ($this->sortType == 'desc') {

            $NUM = count($arr_input);
            foreach ($arr_input as $key) {
                $key['NUM'] = $NUM;
                $arr[]      = $key;
                $NUM--;
            }

            return $arr;

        }

        # SI ES ASCENDENTE

        foreach ($arr_input as $key) {
            $key['NUM'] = $NUM;
            $arr[]      = $key;
            $NUM++;
        }

        return $arr;
    }

    private function get_page(array $input, $pageNum, $perPage)
    {
        $start = ($pageNum - 1) * $perPage;
        $end   = $start + $perPage;
        $count = count($input);

        // Conditionally return results
        if ($start < 0 || $count <= $start) {
            // Page is out of range
            return array();
        } else if ($count <= $end) {
            // Partially-filled page
            return array_slice($input, $start);
        } else {
            // Full page
            return array_slice($input, $start, $end - $start);
        }
    }

/**
 * __construct()
 */

    public function __construct(IRouter $router = null)
    {
        parent::__construct($router);
    }
}
