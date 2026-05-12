<?php

class Controller_Socios extends Controller_Portalbase
{
    protected $portal_code = 'socios';

    public function action_index()
    {
        $this->template->title = 'Socios';
        $this->template->content = View::forge('portal/dashboard', ['portal_label' => 'Socios']);
    }
}
