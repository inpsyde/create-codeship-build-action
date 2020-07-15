<?php

declare(strict_types=1);

/**
 * @return array<string>
 */
function actionInputs(): array
{
    $user = getenv('CODESHIP_USER');
    $pwd = getenv('CODESHIP_PWD');

    if (!$user || !$pwd || !filter_var($user, FILTER_VALIDATE_EMAIL)) {
        throw new Error("Invalid basic auth credentials.");
    }

    $orga = getenv('CODESHIP_ORGA');
    $project = getenv('CODESHIP_PROJECT');
    $ref = getenv('CODESHIP_REF') ?: 'heads/master';
    if ($ref && stripos($ref, 'heads/') !== 0) {
        $ref = "heads/{$ref}";
    }

    if (!$orga) {
        throw new Error("Invalid Codeship organization name.");
    }

    if (!$project || !isUuid($project)) {
        throw new Error("Invalid Codeship project UUID.");
    }

    return ["{$user}:{$pwd}", strtolower($orga), strtolower($project), strtolower($ref)];
}

/**
 * @param string $string
 * @return bool
 */
function isUuid(string $string): bool
{
    return (bool)preg_match('/^[a-z0-9]{8}-(?:[a-z0-9]{4}-){3}[a-z0-9]{12}$/i', $string);
}

/**
 * @param string $endpoint
 * @param string|null $basicAuth
 * @param string|null $token
 * @param string|null $body
 * @return stdClass
 */
function callApi(
    string $endpoint,
    ?string $basicAuth = null,
    ?string $token = null,
    ?string $body = null
): stdClass {

    $endpointClean = ltrim($endpoint, '/');
    $curl = curl_init("https://api.codeship.com/v2/{$endpointClean}");
    if (!is_resource($curl)) {
        throw new Error("Failed initializing CURL for '/{$endpointClean}'.");
    }

    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    $token and $headers[] = "Authorization: Bearer {$token}";

    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    $basicAuth and curl_setopt($curl, CURLOPT_USERPWD, $basicAuth);
    $body and curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

    $result = curl_exec($curl);
    $data = $result
        ? json_decode((string)$result, false, 128, JSON_THROW_ON_ERROR)
        : new stdClass();

    if (!$data instanceof stdClass) {
        curl_close($curl);
        throw new Error("Failed parsing JSON response from '/{$endpointClean}'.");
    }

    if ((int)(curl_getinfo($curl)['http_code'] ?? 0) > 226) {
        $errors = (array)($data->errors ?? []);
        $errorStr = $errors ? ' Errors: ' . implode(', ', $errors) : '';
        curl_close($curl);

        throw new Error("Error response from '/{$endpointClean}'.{$errorStr}");
    }

    curl_close($curl);

    return $data;
}

/**
 * @param string $name
 * @param array $organizations
 * @return string
 */
function findOrganizationUuid(string $name, array $organizations): string
{
    $orgaUuid = null;
    foreach ($organizations as $organization) {
        if (($organization->name ?? null) !== $name) {
            continue;
        }

        $uuid = $organization->uuid ?? null;
        $scopes = $organization->scopes ?? null;

        if (!$uuid || !is_string($uuid) || !isUuid($uuid)) {
            throw new Error("Invalid UUID found for organization {$name}.");
        }

        if (!$scopes || !is_array($scopes) || !in_array('build.write', $scopes, true)) {
            throw new Error("User has no access to scope 'build.write' on '{$name}'.");
        }

        $orgaUuid = $uuid;
        break;
    }

    if (!$orgaUuid) {
        throw new Error(
            "Organization '{$name}' not found in response. Make sure user has access to it."
        );
    }

    return $orgaUuid;
}

/**
 * @return void
 */
function executeAction(): void
{
    [$basicAuth, $orga, $project, $ref] = actionInputs();
    $json = callApi('auth', $basicAuth);

    $token = $json->access_token ?? null;
    $organizations = $json->organizations ?? null;
    if (!$token || !is_string($token)) {
        throw new Error('oAuth token not found in response.');
    }

    if (!$organizations || !is_array($organizations)) {
        throw new Error('Codeship organizations not found in response.');
    }

    $orgaUuid = findOrganizationUuid($orga, $organizations);
    $body = json_encode(compact('ref'), JSON_UNESCAPED_SLASHES);

    callApi("organizations/{$orgaUuid}/projects/{$project}/builds", null, $token, $body);
    fwrite(STDOUT, "\nBuild of project '{$project}' in '{$orga}' organization requested.\n");
}

try {
    executeAction();
} catch (Throwable $error) {
    fwrite(STDERR, "\n" . $error->getMessage() . "\n");
    exit(1);
}
