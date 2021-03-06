<?php
/**
 * IndexController.php
 * IndexController
 *
 * The index controller and its actions
 *
 * @author      Nikos Dimopoulos <nikos@niden.net>
 * @since       2012-06-21
 * @category    Controllers
 * @license     MIT - https://github.com/NDN/phalcon-angular-harryhogfootball/blob/master/LICENSE
 *
 */

class IndexController extends NDN_Controller
{
    public function initialize()
    {
        Phalcon_Tag::setTitle('Welcome');
        parent::initialize();
        $this->view->setVar('top_menu', $this->_constructMenu($this));
    }

    /**
     * index Action
     */
    public function indexAction()
    {

    }
}
