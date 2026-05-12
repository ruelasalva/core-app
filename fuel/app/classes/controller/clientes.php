<?php

class Controller_Clientes extends Controller_Portalbase
{
    protected $portal_code = 'clientes';

    public function action_index()
    {
        $this->template->title = 'Clientes';
        $this->template->content = View::forge('portal/dashboard', ['portal_label' => 'Clientes']);
    }
}
