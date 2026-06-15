<?php

/**
 * SERVICE CORE_WORKSPACE_CONTAINER
 *
 * Contenedor ligero para resolver servicios del Workspace.
 */
class Service_Core_Workspace_Container
{
    public function context()
    {
        return (new \Service_Core_Workspace_Context())->build();
    }

    public function catalog()
    {
        return new \Service_Core_Workspace_WidgetCatalog();
    }

    public function runner()
    {
        return new \Service_Core_Workspace_WidgetRunner();
    }

    public function quick_actions()
    {
        return new \Service_Core_Workspace_QuickActions();
    }

    public function preferences()
    {
        return new \Service_Core_Workspace_Preferences();
    }
}

