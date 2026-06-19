<?php

/**
 * SERVICE CORE COMMUNICATIONS STATISTICS
 *
 * Contrato futuro para metricas operativas del CAP.
 * No consulta ni agrega datos en esta fase.
 */
class Service_Core_Communications_Statistics
{
    /**
     * SUMMARY
     *
     * Contrato para obtener resumen estadistico por canal/proveedor/periodo.
     *
     * @param   array  $filters
     * @return  array
     */
    public function summary(array $filters = [])
    {
        return [
            'success' => false,
            'message' => 'Estadisticas de comunicaciones pendientes de implementacion.',
            'data' => [
                'filters' => $filters,
                'summary' => [],
            ],
            'errors' => [],
        ];
    }

    /**
     * TODO:
     * - Medir enviados, fallidos, abiertos, reintentos y latencia.
     * - Separar metricas internas de datos sensibles.
     */
}
