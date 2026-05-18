<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * The Welcome Controller.
 *
 * A basic controller example.  Has examples of how to set the
 * response body and status.
 *
 * @package  app
 * @extends  Controller
 */
class Controller_Welcome extends Controller
{
	/**
	 * The basic welcome message
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_index()
	{
		return Response::forge(View::forge('welcome/index'));
	}

	/**
	 * A typical "Hello, Bob!" type example.  This uses a Presenter to
	 * show how to use them.
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_hello()
	{
		return Response::forge(Presenter::forge('welcome/hello'));
	}

	/**
	 * The 404 action for the application.
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_404()
	{
		return Response::forge(Presenter::forge('welcome/404'), 404);
	}

	/**
	 * Bad request action.
	 *
	 * @access  public
	 * @return  Response
	 */
	public function action_400()
	{
		$content_type = (string) Input::headers('Content-Type', '');
		$accept = (string) Input::headers('Accept', '');
		$is_json = stripos($content_type, 'application/json') !== false || stripos($accept, 'application/json') !== false;

		if ($is_json) {
			return Response::forge(
				json_encode(['error' => 'La solicitud no paso la validacion de seguridad. Recarga la pantalla e intenta de nuevo.']),
				400,
				['Content-Type' => 'application/json']
			);
		}

		return Response::forge(Presenter::forge('welcome/404'), 400);
	}
}
