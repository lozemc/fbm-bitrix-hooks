<?php

function env($key = null)
{
    if (is_file('/var/www/.env')) {
        $env = parse_ini_file('/var/www/.env', true);

        if (!is_null($key)) {
            return $env[$key] ?? null;
        }

        return $env;
    }

    return null;
}
