<?php

class Response
{
    public static function success($mensaje, $datos = [])
    {
        return [
            "estado" => "ok",
            "mensaje" => $mensaje,
            "datos" => $datos
        ];
    }

    public static function error($mensaje)
    {
        return [
            "estado" => "error",
            "mensaje" => $mensaje
        ];
    }
}