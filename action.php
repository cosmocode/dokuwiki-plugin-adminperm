<?php

/**
 * DokuWiki Plugin adminperm (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */
class action_plugin_adminperm extends DokuWiki_Action_Plugin
{

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     *
     * @return void
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('ADMINPLUGIN_ACCESS_CHECK', 'AFTER', $this, 'handle_accesscheck');
        $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'handle_menu');
    }

    /**
     * Override Access to Admin Plugins
     *
     * Called for event: ADMINPLUGIN_ACCESS_CHECK
     *
     * @param Doku_Event $event event object by reference
     * @param mixed $param [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     *
     * @return void
     */
    public function handle_accesscheck(Doku_Event $event, $param)
    {
        global $INFO;
        global $INPUT;

        if ($event->data['hasAccess']) return; // access already granted? do nothing
        if (!$INPUT->server->str('REMOTE_USER')) return; // no user available?

        /** @var admin_plugin_adminperm $plugin */
        $plugin = plugin_load('admin', 'adminperm');
        $cnf = $plugin->load();

        /** @var DokuWiki_Admin_Plugin $instance */
        $instance = $event->data['instance'];
        $pname = $instance->getPluginName();

        // any override available?
        if (empty($cnf[$pname])) return;

        // check for match
        if (auth_isMember($cnf[$pname], $INPUT->server->str('REMOTE_USER'), $INFO['userinfo']['grps'])) {
            $event->data['hasAccess'] = true; // we found an override
        }

        // nope, still nothing
    }

    /**
     * Readd the Admin Menu Item if the user is in an override
     *
     * Called for event: MENU_ITEMS_ASSEMBLY
     *
     * @param Doku_Event $event event object by reference
     * @param mixed $param [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     *
     * @return void
     */
    public function handle_menu(Doku_Event $event, $param)
    {
        global $INPUT;
        global $INFO;
        if (!$INPUT->server->str('REMOTE_USER')) return; // no user available?
        if ($INFO['ismanager']) return; // already access to admin?
        if ($event->data['view'] !== 'user') return; // we modify the user menu only

        /** @var admin_plugin_adminperm $plugin */
        $plugin = plugin_load('admin', 'adminperm');
        $cnf = $plugin->load();

        // check if there is any override for the user
        $override = false;
        foreach ($cnf as $plugin => $members) {
            if (auth_isMember($members, $INPUT->server->str('REMOTE_USER'), $INFO['userinfo']['grps'])) {
                $override = true;
                break;
            }
        }
        if (!$override) return;

        // add the admin menu item
        $item = new \dokuwiki\Menu\Item\Admin();
        array_splice($event->data['items'], -1, 0, [$item]);
    }

}

