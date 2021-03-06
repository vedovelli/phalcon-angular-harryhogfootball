<?php
/**
 * AwardsController.php
 * AwardsController
 *
 * The awards controller and its actions
 *
 * @author      Nikos Dimopoulos <nikos@niden.net>
 * @since       2012-06-24
 * @category    Controllers
 * @license     MIT - https://github.com/NDN/phalcon-angular-harryhogfootball/blob/master/LICENSE
 *
 */

use Phalcon_Tag as Tag;
use NDN_Session as Session;

class AwardsController extends NDN_Controller
{
    /**
     * Initializes the controller
     */
    public function initialize()
    {
        Tag::setTitle('Manage Awards');
        parent::initialize();

        $this->_bc->add('Awards', 'awards');

        $auth = Session::get('auth');
        $add  = '';

        if ($auth) {
            $add = Tag::linkTo(
                array(
                    'awards/add',
                    'Add Award'
                )
            );
        }

        $this->view->setVar('addButton', $add);
        $this->view->setVar('top_menu', $this->_constructMenu($this));
    }

    /**
     * The index action
     */
    public function indexAction()
    {

    }

    /**
     * The add action
     */
    public function addAction()
    {
        $auth = Session::get('auth');

        if ($auth) {
            $this->_bc->add('Add', 'awards/add');

            $allEpisodes = Episodes::find(array('order' => 'airDate DESC'));
            $allUsers    = Users::find(array('order' => 'username'));
            $allPlayers  = Players::find(array('order' => 'active DESC, name'));

            $this->view->setVar('users', $allUsers);
            $this->view->setVar('episodes', $allEpisodes);
            $this->view->setVar('players', $allPlayers);

            if ($this->request->isPost()) {

                $datetime = date('Y-m-d H:i:s');

                $award = new Awards();
                $award->userId    = $this->request->getPost('userId', 'int');
                $award->episodeId = $this->request->getPost('episodeId', 'int');
                $award->playerId  = $this->request->getPost('playerId', 'int');
                $award->award     = $this->request->getPost('award', 'int');

                $award->lastUpdate       = $datetime;
                $award->lastUpdateUserId = (int) $auth['id'];

                if (!$award->save()) {
                    foreach ($award->getMessages() as $message) {
                        Session::setFlash(
                            'error',
                            (string) $message,
                            'alert alert-error'
                        );
                    }
                } else {
                    Session::setFlash(
                        'success',
                        'Award created successfully',
                        'alert alert-success'
                    );

                    $this->response->redirect('awards/');
                }

            }
        }
    }

    /**
     * Gets the Hall of Fame
     */
    public function getAction($action, $limit = null)
    {
        $this->view->setRenderLevel(Phalcon_View::LEVEL_LAYOUT);

        $connection = Phalcon_Db_Pool::getConnection();
        $sql = 'SELECT COUNT(a.id) AS total, p.name AS playerName, a.award '
             . 'FROM awards a '
             . 'INNER JOIN players p ON a.playerId = p.id '
             . 'WHERE a.award = %s ';

        switch ($action) {
            case 1:
                $sql .= 'AND p.active = 1 ';
                break;
            case 2:
            case 3:
            case 4:
                $sql .= 'AND a.userId = ' . (int) $action. ' ';
                break;
        }

        $sql .= 'GROUP BY a.award, a.playerId '
              . 'ORDER BY a.award ASC, total DESC, p.name ';

        if (!empty($limit)) {
            $sql .= 'LIMIT ' . (int) $limit;
        }

        // Kicks
        $query = sprintf($sql, -1);
        $result = $connection->query($query);
        $result->setFetchMode(Phalcon_Db::DB_ASSOC);

        $kicks    = array();
        $kicksMax = 0;

        while ($item = $result->fetchArray()) {
            $kicksMax = (0 == $kicksMax) ? $item['total'] : $kicksMax;
            $name     = $item['playerName'];
            $kicks[]  = array(
                'total'   => (int) $item['total'],
                'name'    => $name,
                'percent' => (int) ($item['total'] * 100 / $kicksMax),
            );
        }

        // Game balls
        $query = sprintf($sql, 1);
        $result = $connection->query($query);
        $result->setFetchMode(Phalcon_Db::DB_ASSOC);

        $gameballs    = array();
        $gameballsMax = 0;

        while ($item = $result->fetchArray()) {
            $gameballsMax = (0 == $gameballsMax) ?
                            $item['total']       :
                            $gameballsMax;
            $name         = $item['playerName'];
            $gameballs[]  = array(
                'total'   => (int) $item['total'],
                'name'    => $name,
                'percent' => (int) ($item['total'] * 100 / $gameballsMax),
            );
        }

        $result = array('gameballs' => $gameballs, 'kicks' => $kicks);

        echo json_encode($result);
    }
}
