<?php

namespace App\Service;

use DateTimeImmutable;

class JWTService
{
    //On génère le token

    public function generate(array $header, array $payload, string $secret, int $validity = 10800): string
    {
        // Vérifier si validité < 0
        if ($validity > 0) {
            // Récupère le timeStamp & on add la validité
            $now = new DateTimeImmutable();
            $exp = $now->getTimestamp() + $validity;

            //Ds le payload add ['iat']= Le timeStamp de maintenant
            $payload['iat'] = $now->getTimestamp();
            //Ds le payload add ['exp']= Le timeStamp de maintenant + l expiration
            $payload['exp'] = $exp;
        }

        //Encoder en base64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        //On retire les +, /, =
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

    // Génère la signature => il faut le secret

        //Pour le secret aller ds .env => création du secret
        //JWT_SECRET = '0hLa83lleBroue11e!';
        // Aller chercher le secret
        // On encode le secret
        $secret = base64_encode($secret);

        //On génère la partie turquoise
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);

        //Encode + nettoie la signature
        $base64Signature = base64_encode($signature);
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

        // Création du token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $signature;

        return $jwt;
    }

    /**
     * Vérification si token est valide (correctement formé)
     *
     * @param string $token
     * @return boolean
     */
    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }

    // On récupère le Payload
    public function getPayload(string $token): array
    {
        // On démonte le token
        $array = explode('.', $token);

        // On décode le Payload
        $payload = json_decode(base64_decode($array[1]), true);

        return $payload;
    }

    // On récupère le Header
    public function getHeader(string $token): array
    {
        // On démonte le token
        $array = explode('.', $token);

        // On décode le header
        $header = json_decode(base64_decode($array[0]), true);

        return $header;
    }

    /**
     * Vérification si token a expiré
     */
    public function isExpired(string $token): bool
    {
        // 1. Récupère le payload
        $payload = $this->getPayload($token);

        // 2. Récupère le temps de maintenant 
        $now = new DateTimeImmutable();
        
        // 3. Comparaison des deux temps
        return $payload['exp'] < $now->getTimestamp();
    }

    /**
     * Vérification de la signature
     */
    public function check($token, string $secret)
    {
        //Récupère le Header et le Payload
        $payload = $this->getPayload($token);
        $header = $this->getHeader($token);

        //Rgénérer un token juste pour vérifier si la partie turquoise est la même 
        $verifToken = $this->generate($header, $payload, $secret, 0);

        return $token === $verifToken;
    }

}