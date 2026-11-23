<?php
// Recibe el formulario de cotización del sitio y lo envía por correo.
// Corre en Hostinger (PHP). En el servidor de desarrollo de Next no se ejecuta; el formulario
// ofrece entonces enviar los datos por WhatsApp.

header('Content-Type: application/json; charset=utf-8');

const DESTINO = 'contacto@indupest.com.mx';
const REMITENTE = 'sitio@indupest.com.mx';

function responder($ok, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(['ok' => $ok]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(false, 405);

// Campo trampa: si un bot lo llena, se responde como enviado sin mandar nada
if (!empty($_POST['sitio_web'])) responder(true);

function campo($nombre, $max = 200) {
    $v = isset($_POST[$nombre]) ? trim((string) $_POST[$nombre]) : '';
    $v = str_replace(["\r", "\n"], ' ', $v);
    return mb_substr(strip_tags($v), 0, $max);
}

$nombre = campo('nombre');
$telefono = campo('telefono', 40);
$correo = campo('correo', 120);
if ($nombre === '' || $telefono === '') responder(false, 422);
if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) $correo = '';

$mensaje = isset($_POST['mensaje']) ? mb_substr(strip_tags(trim((string) $_POST['mensaje'])), 0, 2000) : '';

$lineas = [
    'Nueva solicitud de cotización desde indupest.com.mx',
    '',
    'Nombre: ' . $nombre,
    'Empresa: ' . campo('empresa'),
    'Teléfono: ' . $telefono,
    'Correo: ' . $correo,
    'Ciudad: ' . campo('ciudad', 60),
    'Instalación: ' . campo('instalacion', 80),
    'Metros cuadrados: ' . campo('metros', 30),
    'Auditoría próxima: ' . campo('auditoria', 60),
    'Página de origen: ' . campo('origen', 80),
    'Campaña: ' . (campo('campana', 300) ?: 'Sin campaña (visita directa o de Google sin anuncio)'),
    'gclid: ' . campo('gclid', 200),
    '',
    'Mensaje:',
    $mensaje,
];

$asunto = '=?UTF-8?B?' . base64_encode('Cotización web: ' . $nombre . ' (' . campo('instalacion', 80) . ')') . '?=';
$cabeceras = [
    'From: InduPest sitio web <' . REMITENTE . '>',
    'Content-Type: text/plain; charset=UTF-8',
];
if ($correo !== '') $cabeceras[] = 'Reply-To: ' . $correo;

$enviado = mail(DESTINO, $asunto, implode("\n", $lineas), implode("\r\n", $cabeceras));
responder($enviado, $enviado ? 200 : 500);
