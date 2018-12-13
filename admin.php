<?php
/**
 * DokuWiki Plugin adminperm (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */


class admin_plugin_adminperm extends DokuWiki_Admin_Plugin
{
    protected $config = DOKU_CONF . 'adminperm.json';


    /**
     * @return int sort number in admin menu
     */
    public function getMenuSort()
    {
        return 255;
    }

    /**
     * @return bool true if only access for superuser, false is for superusers and moderators
     */
    public function forAdminOnly()
    {
        return true;
    }

    /**
     * Should carry out any processing required by the plugin.
     */
    public function handle()
    {
        global $INPUT;
        if ($INPUT->post->has('d') && checkSecurityToken()) {
            if ($this->save($INPUT->post->arr('d'))) {
                msg($this->getLang('saved'), 1);
            }
        }
    }

    /**
     * Render HTML output, e.g. helpful text and a form
     */
    public function html()
    {
        echo $this->locale_xhtml('intro');

        $cnf = $this->load(true);
        $plugins = plugin_list('admin');
        sort($plugins);

        $form = new \dokuwiki\Form\Form();
        $form->addFieldsetOpen($this->getLang('legend'));
        foreach ($plugins as $plugin) {
            /** @var DokuWiki_Admin_Plugin $obj */
            $obj = plugin_load('admin', $plugin);
            if ($obj === null) continue;

            $label = $plugin . ($obj->forAdminOnly() ? ' (A)' : ' (M)');

            $form->addTextInput('d[' . $plugin . ']', $label)->addClass('block')->val($cnf[$plugin] ?: '');
        }

        if (file_exists($this->config) && !is_writable($this->config)) {
            msg(sprintf($this->getLang('nosave'), $this->config), -1);
        } else {
            $form->addButton('submit', $this->getLang('save'));

        }

        echo $form->toHTML();
    }

    /**
     * Load the current config
     *
     * @param bool $refresh force a reload of the config instead of relying on the static copy
     * @return string[]
     */
    public function load($refresh = false)
    {
        static $config = null;
        if ($config === null || $refresh) {
            $config = [];
            if (file_exists($this->config)) {
                $config = json_decode(io_readFile($this->config, false), true);
            }
        }
        return $config;
    }

    /**
     * Save the given config
     *
     * @param string[] $data
     * @return bool
     */
    public function save($data)
    {
        $orig = $this->load(true);
        $data = array_merge($orig, $data);
        $data = array_map('trim', $data);
        $data = array_filter($data);

        return io_saveFile($this->config, json_encode($data, JSON_PRETTY_PRINT));
    }
}

