<?php

/**
 * SERVICE CORE AUTOMATION SCHEDULER
 *
 * Contrato futuro para tareas programadas y automatizaciones diferidas.
 * No agenda ni ejecuta trabajos en esta fase.
 */
class Service_Core_Automation_Scheduler
{
    /**
     * SCHEDULE
     *
     * Define el contrato para programar una automatizacion futura.
     *
     * @param   string  $job_code
     * @param   array   $payload
     * @param   array   $schedule
     * @return  array
     */
    public function schedule($job_code, array $payload = [], array $schedule = [])
    {
        return [
            'success' => false,
            'message' => 'Scheduler pendiente de implementacion.',
            'data' => [
                'job_code' => (string) $job_code,
                'payload' => $payload,
                'schedule' => $schedule,
            ],
            'errors' => [],
        ];
    }

    /**
     * DUE JOBS
     *
     * Contrato futuro para listar trabajos vencidos y seguros de ejecutar.
     *
     * @param   array  $filters
     * @return  array
     */
    public function due_jobs(array $filters = [])
    {
        return [
            'success' => false,
            'message' => 'Consulta de trabajos pendientes no implementada.',
            'data' => [
                'filters' => $filters,
                'jobs' => [],
            ],
            'errors' => [],
        ];
    }

    /**
     * TODO:
     * - Reintentar emails fallidos.
     * - Sincronizar IMAP.
     * - Ejecutar automatizaciones diferidas.
     * - Archivar conversaciones.
     * - Limpiar datos temporales.
     */
}
