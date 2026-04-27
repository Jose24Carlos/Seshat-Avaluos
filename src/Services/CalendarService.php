<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Env;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class CalendarService
{
    public static function libreriaDisponible(): bool
    {
        return class_exists(\Google\Client::class);
    }

    /** @param array<string, mixed> $valuador fila usuarios */
    public static function clienteGoogle(): ?\Google\Client
    {
        if (!self::libreriaDisponible()) {
            return null;
        }
        $id = Env::get('GOOGLE_CLIENT_ID');
        $secret = Env::get('GOOGLE_CLIENT_SECRET');
        $redirect = Env::get('GOOGLE_REDIRECT_URI');
        if ($id === null || $id === '' || $secret === null || $secret === '' || $redirect === null || $redirect === '') {
            return null;
        }
        $client = new \Google\Client();
        $client->setClientId($id);
        $client->setClientSecret($secret);
        $client->setRedirectUri($redirect);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->addScope(\Google\Service\Calendar::CALENDAR_READONLY);
        $client->addScope(\Google\Service\Calendar::CALENDAR_EVENTS);
        return $client;
    }

    /** @param array<string, mixed> $valuador */
    public static function aplicarTokensACliente(\Google\Client $client, array $valuador): void
    {
        $access = $valuador['google_access_token'] ?? null;
        if (!is_string($access) || $access === '') {
            return;
        }
        $decoded = json_decode($access, true);
        if (is_array($decoded)) {
            $client->setAccessToken($decoded);
        } else {
            $client->setAccessToken($access);
        }
        if ($client->isAccessExpired()) {
            $refresh = $valuador['google_refresh_token'] ?? null;
            if (is_string($refresh) && $refresh !== '') {
                $client->fetchAccessTokenWithRefreshToken($refresh);
                $token = $client->getAccessToken();
                if (is_array($token)) {
                    self::guardarTokensValuador((int) $valuador['id'], $token);
                    $client->setAccessToken($token);
                }
            }
        }
    }

    /** @param array<string, mixed> $token */
    public static function guardarTokensValuador(int $userId, array $token): void
    {
        $pdo = Database::pdo();
        $access = isset($token['access_token']) ? json_encode($token) : null;
        $refresh = $token['refresh_token'] ?? null;
        $exp = null;
        if (isset($token['created'], $token['expires_in'])) {
            $exp = date('Y-m-d H:i:s', (int) $token['created'] + (int) $token['expires_in']);
        }
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET google_access_token = ?, google_refresh_token = COALESCE(?, google_refresh_token), google_token_expires_at = ? WHERE id = ?'
        );
        $stmt->execute([$access, $refresh, $exp, $userId]);
    }

    /**
     * Devuelve tramos libres (ISO 8601) entre $desde y $hasta para el calendario del valuador.
     * @return list<array{inicio:string, fin:string}>
     */
    public static function slotsLibres(
        array $valuador,
        string $desde,
        string $hasta,
        int $duracionMinutos = 60,
        int $pasoMinutos = 30
    ): array {
        $tzName = Env::get('APP_TIMEZONE', 'America/Chicago') ?? 'America/Chicago';
        $tz = new DateTimeZone($tzName);
        $client = self::clienteGoogle();
        if ($client === null) {
            return [];
        }
        self::aplicarTokensACliente($client, $valuador);
        if (!$client->getAccessToken()) {
            return [];
        }
        $calendarId = (string) ($valuador['google_calendar_id'] ?? 'primary');
        $service = new \Google\Service\Calendar($client);
        $freeReq = new \Google\Service\Calendar\FreeBusyRequest();
        $freeReq->setTimeMin($desde);
        $freeReq->setTimeMax($hasta);
        $item = new \Google\Service\Calendar\FreeBusyRequestItem();
        $item->setId($calendarId);
        $freeReq->setItems([$item]);
        $busyResp = $service->freebusy->query($freeReq);
        $calendars = $busyResp->getCalendars();
        if ($calendars === null) {
            return [];
        }
        $cal = $calendars[$calendarId] ?? null;
        $busyList = $cal ? $cal->getBusy() : [];
        /** @var list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $busy */
        $busy = [];
        foreach ($busyList ?? [] as $b) {
            $s = $b->getStart();
            $e = $b->getEnd();
            if ($s && $e) {
                $busy[] = [
                    'start' => new DateTimeImmutable($s, $tz),
                    'end' => new DateTimeImmutable($e, $tz),
                ];
            }
        }

        $inicio = new DateTimeImmutable($desde, $tz);
        $fin = new DateTimeImmutable($hasta, $tz);
        $slots = [];
        $cursor = $inicio;
        $dur = new DateInterval('PT' . max(15, $duracionMinutos) . 'M');
        $step = new DateInterval('PT' . max(15, $pasoMinutos) . 'M');

        while ($cursor < $fin) {
            $slotEnd = $cursor->add($dur);
            if ($slotEnd > $fin) {
                break;
            }
            if (!self::enHorarioLaboral($cursor, $tz)) {
                $cursor = $cursor->add($step);
                continue;
            }
            if (self::solapaBusy($cursor, $slotEnd, $busy)) {
                $cursor = $cursor->add($step);
                continue;
            }
            $slots[] = [
                'inicio' => $cursor->format(DateTime::ATOM),
                'fin' => $slotEnd->format(DateTime::ATOM),
            ];
            $cursor = $cursor->add($step);
            if (count($slots) >= 200) {
                break;
            }
        }
        return $slots;
    }

    private static function enHorarioLaboral(DateTimeImmutable $t, DateTimeZone $tz): bool
    {
        $d = (int) $t->format('N');
        if ($d >= 6) {
            return false;
        }
        $h = (int) $t->format('G');
        return $h >= 8 && $h < 18;
    }

    /**
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $busy
     */
    private static function solapaBusy(DateTimeImmutable $s, DateTimeImmutable $e, array $busy): bool
    {
        foreach ($busy as $b) {
            if ($s < $b['end'] && $e > $b['start']) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string, mixed> $valuador */
    public static function crearEventoVisita(
        array $valuador,
        string $titulo,
        string $inicioIso,
        string $finIso,
        ?string $descripcion = null
    ): ?string {
        $client = self::clienteGoogle();
        if ($client === null) {
            return null;
        }
        self::aplicarTokensACliente($client, $valuador);
        if (!$client->getAccessToken()) {
            return null;
        }
        $tzName = Env::get('APP_TIMEZONE', 'America/Chicago') ?? 'America/Chicago';
        $tz = new \DateTimeZone($tzName);
        $inicioRfc = self::normalizarRfc3339($inicioIso, $tz);
        $finRfc = self::normalizarRfc3339($finIso, $tz);
        if ($inicioRfc === null || $finRfc === null) {
            return null;
        }
        $calendarId = (string) ($valuador['google_calendar_id'] ?? 'primary');
        $service = new \Google\Service\Calendar($client);
        $event = new \Google\Service\Calendar\Event();
        $event->setSummary($titulo);
        if ($descripcion) {
            $event->setDescription($descripcion);
        }
        $start = new \Google\Service\Calendar\EventDateTime();
        $start->setDateTime($inicioRfc);
        $start->setTimeZone($tzName);
        $event->setStart($start);
        $end = new \Google\Service\Calendar\EventDateTime();
        $end->setDateTime($finRfc);
        $end->setTimeZone($tzName);
        $event->setEnd($end);
        $created = $service->events->insert($calendarId, $event);
        return $created->getId();
    }

    /** @return array<string, mixed>|null */
    public static function usuarioPorId(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ? AND rol = ?');
        $stmt->execute([$id, 'valuador']);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    private static function normalizarRfc3339(string $valor, \DateTimeZone $tz): ?string
    {
        $valor = trim($valor);
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $f) {
            $dt = \DateTimeImmutable::createFromFormat($f, $valor, $tz);
            if ($dt instanceof \DateTimeImmutable) {
                return $dt->format(\DateTimeInterface::RFC3339);
            }
        }
        try {
            return (new \DateTimeImmutable($valor, $tz))->format(\DateTimeInterface::RFC3339);
        } catch (\Throwable) {
            return null;
        }
    }
}
