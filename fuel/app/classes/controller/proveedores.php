<?php

class Controller_Proveedores extends Controller_Portalbase
{
    protected $portal_code = 'proveedores';

    public function action_index()
    {
        $this->template->title = 'Proveedores';
        $this->template->content = View::forge('portal/dashboard', ['portal_label' => 'Proveedores']);
    }
}
