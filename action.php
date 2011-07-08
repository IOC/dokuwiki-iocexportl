<?php
/**
 * Action Plugin:   iocexportl.
 * @license    GPL (http://www.gnu.org/licenses/gpl.html)
 * @author     Marc Català 		<mcatala@ioc.cat>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_TEMPLATES')) define('DOKU_PLUGIN_TEMPLATES',DOKU_PLUGIN.'iocexportl/templates/');
if (!defined('DOKU_PLUGIN_LATEX_TMP')) define('DOKU_PLUGIN_LATEX_TMP',DOKU_PLUGIN.'tmp/latex/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_iocexportl extends DokuWiki_Action_Plugin{

    var $exportallowed = FALSE;
    var $id = '';
    var $language = 'CA';

    function register(&$controller) {
        global $ACT;
        if ($ACT === 'show'){
            $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'getLanguage', array());
            $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'showform', array());
            $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'counter', array());
            $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'chooseactivities', array());
            $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'numbering', array());
        }
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'ioctoolbar_buttons', array ());
    }

    function showform(&$event){
	    global $conf;
        global $INFO;

		$this->id = getID();
        $this->exportallowed = (isset($conf['plugin']['iocexportl']['allowexport']) && $conf['plugin']['iocexportl']['allowexport']);
        if (!$this->isExportPage()) return FALSE;
        if ($event->data != 'show') return FALSE;
        if (!$INFO['writable']) return FALSE;
        if (!$this->checkPerms()) return FALSE;
        //Always admin can export
        if ($this->exportallowed || auth_isadmin()){
	        echo $this->getform();
        }
        return TRUE;
    }

    function counter(&$event) {
        if ($this->checkPerms() && $this->showcounts()){
            echo '<script type="text/javascript" src="'.DOKU_BASE.'lib/plugins/iocexportl/lib/counter.js"></script>';
        }
    }

    function chooseactivities(&$event) {
        if ($this->isExportPage() && ($this->exportallowed || auth_isadmin())){
            echo '<script type="text/javascript" src="'.DOKU_BASE.'lib/plugins/iocexportl/lib/chooser.js"></script>';
        }
    }

    function numbering(&$event) {
        if ($event->data != 'edit' && $event->data != 'preview' && !$this->isExportPage()){
            echo '<script type="text/javascript" src="'.DOKU_BASE.'lib/plugins/iocexportl/lib/numbering.js"></script>';
        }
    }

    function showcounts(){
        global $conf;
        $this->id = getID();
        $file = wikiFN($this->id);
        $bool = io_grep($file, '/~~NOCOUNT~~/', 1);
        $counter = (isset($conf['plugin']['iocexportl']['counter']) && $conf['plugin']['iocexportl']['counter']);
        return !$bool && $counter && preg_match('/^(?!talk).*?:(pdfindex|htmlindex)$/', $this->id, $matches);
    }

    function checkPerms() {
        global $ID;
        global $USERINFO;
        $ID    = getID();
        $user = $_SERVER['REMOTE_USER'];
        $groups = $USERINFO['grps'];
        $aclLevel = auth_aclcheck($ID,$user,$groups);
        // AUTH_ADMIN, AUTH_READ,AUTH_EDIT,AUTH_CREATE,AUTH_UPLOAD,AUTH_DELETE
        return ($aclLevel >=  AUTH_UPLOAD);
      }

    function isExportPage(){
        return preg_match('/^(?!talk).*?:pdfindex$/', $this->id, $matches);
    }

    function getLanguage(){
        $this->id = getID();
        $file = wikiFN($this->id);
        $lang = io_grep($file, '/^~~(?:ca|de|en|es|fr|it)~~$/i', 1);
        if (isset($lang[0])){
            $lang = strtoupper($lang[0]);
            $this->language = preg_replace('/~/', '', $lang);
        }
    }

    function getform(){
        global $conf;
        $url = '';
        $path_filename = str_replace(':','/',$this->id);
        $filename = str_replace(':','_',basename($this->id)).'.pdf';
        $path_filename = $conf['mediadir'].'/'.dirname($path_filename).'/'.$filename;
        if (file_exists($path_filename)){
            $media_path = 'lib/exe/fetch.php?media='.str_replace('/', ':',dirname(str_replace(':','/',$this->id))).':'.$filename;
            setlocale(LC_TIME, 'ca_ES.utf8');
            $url = '<a class="media mediafile mf_pdf" href="'.$media_path.'">'.$filename.'</a> <strong>'.strftime("%e %B %Y %r", filemtime($path_filename)).'</strong>';
        }
        $ret  = "<br /><br />";
        $ret .= "<div class=\"iocexport\">\n";
        $ret .= "<strong>Exportació IOC: </strong>";
	    $ret .= " <form action=\"lib/plugins/iocexportl/generate.php\" id=\"export__form\" method=\"post\" >\n";
	    if(auth_isadmin()){
	        $ret .= "  <input type=\"radio\" name=\"mode\" value=\"zip\" /> Zip";
	    }
        $ret .= "  <input type=\"radio\" name=\"mode\" value=\"pdf\" checked=\"checked\" /> PDF";
        $ret .= "  <input type=\"hidden\" name=\"pageid\" value=\"".$this->id."\" />";
        $ret .= "  <input type=\"hidden\" name=\"ioclanguage\" value=\"".$this->language."\" />";
        $ret .= "  <input type=\"submit\" name=\"submit\" id=\"id_submit\" value=\"Exporta\" class=\"button\" />\n";
	    $ret .= " </form>\n";
	    $ret .= "<span id=\"exportacio\">".$url."</span>";
	    $ret .= "</div>";
	    $ret .= "<script type=\"text/javascript\" src =\"lib/plugins/iocexportl/lib/form.js\"></script>";
        return $ret;
    }

    /**
     * Inserts the toolbar button
     */
    function ioctoolbar_buttons(& $event, $param) {
        $event->data[] = array (
            'type'   => 'picker',
            'title'  => $this->getLang('toolbar_btn'),
            'icon'   => '../../plugins/iocexportl/img/ico_toolbar.png',
            'class'  => 'ioctoolbar',
            'list'   => array(
                           array(
                                'type'   => 'format',
                                'title'  => $this->getLang('figure_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_figure.png',
                                'key'    => '1',
                                'open'   => '::figure:\n  :title:\n  :copyright:\n  :license:\n',
                                'close'  => '\n:::\n',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('figlink_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_figlink.png',
                                'key'    => '2',
                                'open'   => ':figure:',
                                'close'  => ':',
                                ),
                           array(
                                'type'   => 'format',
                                'title'  => $this->getLang('table_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_table.png',
                                'key'    => '3',
                                'open'   => '::table:\n  :title:\n  :footer:\n',
                                'close'  => '\n:::\n',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('tablink_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_tablink.png',
                                'key'    => '4',
                                'open'   => ':table:',
                                'close'  => ':',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('text_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_text.png',
                                'key'    => '5',
                                'open'   => '::text:\n  :title:\n',
                                'close'  => '\n:::\n',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('textlarge_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_textlarge.png',
                                'key'    => '6',
                                'open'   => '::text:\n  :title:\n  :large:\n',
                                'close'  => '\n:::\n',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('example_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_example.png',
                                'key'    => '7',
                                'open'   => '::example:\n  :title:\n',
                                'close'  => '\n:::\n',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('note_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_note.png',
                                'key'    => '8',
                                'open'   => '::note:\n',
                                'close'  => '\n:::\n',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('reference_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_reference.png',
                                'key'    => '9',
                                'open'   => '::reference:\n',
                                'close'  => '\n:::\n',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('important_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_important.png',
                                'key'    => '10',
                                'open'   => '::important:\n',
                                'close'  => '\n:::\n',
                                ),
                            array(
                                'type'   => 'format',
                                'title'  => $this->getLang('quote_btn'),
                                'icon'   => '../../plugins/iocexportl/img/ico_quote.png',
                                'key'    => '11',
                                'open'   => '::quote:\n',
                                'close'  => '\n:::\n',
                                ),
                        ),
            'block'  => TRUE,
        );
    }
}
