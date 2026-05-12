<?php

/**
 * CONTROLADOR FIX
 *
 * Ruta reservada para bloquear accesos no implementados.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Fix extends Controller
{
    /**
     * BEFORE
     *
     * RESPONDE 404 PARA CUALQUIER ACCESO A ESTE CONTROLADOR
     *
     * @return  Void
     */
    public function before()
    {
        # SE EJECUTA EL BEFORE BASE
        parent::before();

        # SE BLOQUEA EL ACCESO
        throw new \HttpNotFoundException;
    }
}
