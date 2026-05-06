<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mam_exception — jerarquia de excepciones del dominio MAM ERP.
 *
 * Motivacion: el manejo de errores del proyecto es inconsistente — algunos
 * metodos devuelven false, otros null, otros tiran \Exception, otros llaman
 * show_error(). Esto obliga a cada caller a chequear de forma distinta y
 * hace imposible un handler central de errores.
 *
 * Esta library define una base comun y 6 hijas con codigos HTTP fijos, un
 * codigo de error string (para consumers de la API), y un payload opcional
 * de errores por campo (util para validaciones).
 *
 * Uso:
 *
 *     if (!$client) {
 *         throw new NotFoundException('Cliente no encontrado', 'CLIENT_NOT_FOUND');
 *     }
 *
 *     if ($budget->state !== 0) {
 *         throw new BusinessRuleException(
 *             'No se puede embalar un presupuesto ya aprobado',
 *             'BUDGET_NOT_OPEN'
 *         );
 *     }
 *
 *     throw new ValidationException('Datos invalidos', 'INVALID_INPUT', [
 *         'email' => 'Formato invalido',
 *         'phone' => 'Requerido',
 *     ]);
 *
 * Api_response::error() acepta instancias de Mam_exception y produce la
 * respuesta JSON con el formato estandar {status, message, code, errors?}.
 * En el web, los controllers pueden atrapar y mostrar la vista de error
 * correspondiente (pendiente de implementar por modulo, no es scope de
 * esta tarea).
 *
 * Migracion: el codigo existente NO se refactoriza ahora. Estas clases
 * se usan en features nuevas y refactors futuros. El plan es migrar
 * incrementalmente, no en un big-bang.
 */

/**
 * Base de todas las excepciones de dominio de MAM ERP.
 *
 * Ademas de lo que trae \Exception (message, code, previous) agrega:
 *   - $httpCode    codigo HTTP que deberia devolverse al cliente
 *   - $errorCode   string legible tipo 'CLIENT_NOT_FOUND' para consumers
 *   - $errors      detalles opcionales (campos invalidos en validaciones)
 *
 * CI3 instancia esta clase al hacer $this->load->library('mam_exception').
 * Eso es un no-op porque Exception permite instanciarse sin args. Nos
 * interesa solo el side-effect de definir las clases hijas al cargar el
 * archivo.
 */
class Mam_exception extends \Exception
{
    /** @var int Codigo HTTP asociado a esta excepcion. */
    protected $httpCode = 500;

    /** @var string Codigo de error legible (p. ej. 'INVALID_INPUT'). */
    protected $errorCode = 'INTERNAL_ERROR';

    /** @var array<string,mixed> Detalles opcionales (ej: errores por campo). */
    protected $errors = [];

    /**
     * @param string                $message   Mensaje para humanos, en espanol.
     * @param string|null           $errorCode Codigo string para consumers (opcional).
     * @param array<string,mixed>   $errors    Detalles adicionales (opcional).
     * @param \Throwable|null       $previous  Excepcion original encadenada (opcional).
     */
    public function __construct($message = '', $errorCode = null, array $errors = [], \Throwable $previous = null)
    {
        // El segundo parametro de Exception es un int (codigo). Lo fijamos
        // en $httpCode para compatibilidad con handlers que inspeccionen
        // getCode(), pero exponemos $errorCode como string aparte.
        parent::__construct($message, $this->httpCode, $previous);

        if ($errorCode !== null && $errorCode !== '') {
            $this->errorCode = $errorCode;
        }
        $this->errors = $errors;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string,mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Representacion serializable lista para convertir a JSON en una
     * respuesta de API. El campo `errors` se omite si esta vacio.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $out = [
            'status'  => 'error',
            'message' => $this->getMessage(),
            'code'    => $this->errorCode,
        ];
        if (!empty($this->errors)) {
            $out['errors'] = $this->errors;
        }
        return $out;
    }
}

/**
 * El recurso solicitado no existe.
 * HTTP 404.
 *
 * Ejemplos:
 *   - Cliente con ese id no esta en la DB
 *   - Presupuesto borrado
 *   - Producto con idProduct inexistente
 */
class NotFoundException extends Mam_exception
{
    protected $httpCode = 404;
    protected $errorCode = 'NOT_FOUND';
}

/**
 * Input invalido por el usuario (formato, campos faltantes, reglas de
 * formato). Incluye tipicamente un array $errors con detalle por campo.
 * HTTP 422 Unprocessable Entity.
 */
class ValidationException extends Mam_exception
{
    protected $httpCode = 422;
    protected $errorCode = 'VALIDATION_FAILED';
}

/**
 * El usuario no esta autenticado — token invalido, expirado o ausente.
 * HTTP 401 Unauthorized.
 *
 * Distinta de AuthorizationException: esta implica "logueate y volve";
 * la de Authorization implica "estas logueado pero no tenes este permiso".
 */
class AuthenticationException extends Mam_exception
{
    protected $httpCode = 401;
    protected $errorCode = 'UNAUTHENTICATED';
}

/**
 * El usuario esta autenticado pero no tiene permiso sobre el recurso.
 * HTTP 403 Forbidden.
 *
 * Ejemplos:
 *   - Un vendedor intenta ver presupuestos de otro vendedor
 *   - Un almacenista intenta facturar (no es parte de su rol)
 */
class AuthorizationException extends Mam_exception
{
    protected $httpCode = 403;
    protected $errorCode = 'FORBIDDEN';
}

/**
 * Se viola una regla de negocio — el estado actual del dominio no
 * permite la accion pedida.
 * HTTP 409 Conflict.
 *
 * Ejemplos:
 *   - Intentar embalar un presupuesto que ya fue facturado
 *   - Aprobar un pago sobre una factura ya liquidada
 *   - Cerrar un periodo contable que tiene asientos posteriores
 *
 * NO usar para validaciones de formato — esas son ValidationException.
 */
class BusinessRuleException extends Mam_exception
{
    protected $httpCode = 409;
    protected $errorCode = 'BUSINESS_RULE_VIOLATION';
}

/**
 * Falla en una API externa (17TRACK, Interrapidisimo, Anthropic, Google
 * Maps). Se distingue de errores del sistema para que el caller pueda
 * reintentar o mostrar un mensaje especifico ("el tracking no responde
 * ahora, intentalo en unos minutos").
 * HTTP 502 Bad Gateway.
 */
class ExternalApiException extends Mam_exception
{
    protected $httpCode = 502;
    protected $errorCode = 'EXTERNAL_API_FAILURE';
}
