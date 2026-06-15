<?php

/**
 * SERVICE CORE_WORKSPACE_WIDGETRUNNER
 *
 * Ejecuta widgets registrados validando permisos.
 */
class Service_Core_Workspace_WidgetRunner
{
    public function run($code, array $context, array $filters = [], array $settings = [])
    {
        try {
            $class = (new \Service_Core_Workspace_WidgetRegistry())->resolve($code);
            if (!$class) {
                return $this->error('Widget no disponible.');
            }

            /** @var Service_Core_Workspace_Widget $widget */
            $widget = new $class();
            if (!$widget->authorize($context)) {
                return $this->error('No tienes permiso para ver este widget.');
            }

            $payload = $widget->load($context, $filters, $settings);
            $response = $widget->response($payload);
            $response['refresh_time'] = $widget->refresh_time();
            return $this->normalize($response);
        } catch (\Exception $e) {
            \Log::error('Workspace widget error code='.$code.' message='.$e->getMessage());
            return $this->error('No se pudo cargar el widget.');
        }
    }

    protected function normalize(array $response)
    {
        return [
            'success' => isset($response['success']) ? (bool) $response['success'] : true,
            'html' => isset($response['html']) ? (string) $response['html'] : '',
            'message' => isset($response['message']) ? (string) $response['message'] : '',
            'refresh_time' => isset($response['refresh_time']) ? max(0, (int) $response['refresh_time']) : 0,
        ];
    }

    protected function error($message)
    {
        return $this->normalize([
            'success' => false,
            'message' => $message,
            'html' => '',
            'refresh_time' => 0,
        ]);
    }
}
