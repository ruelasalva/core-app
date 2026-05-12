<?php

class Controller_Revendedores extends Controller_Portalbase
{
    protected $portal_code = 'revendedores';

    public function action_index()
    {
        $this->template->title = 'Revendedores';
        $this->template->content = View::forge('portal/dashboard', ['portal_label' => 'Revendedores']);
    }
}
