<?php
/**
 * Example Action Plugin:   Example Component.
 * @license    GPL (http://www.gnu.org/licenses/gpl.html)
 * @author     Marc Català 		<mcatala@ioc.cat>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_TEMPLATES')) define('DOKU_PLUGIN_TEMPLATES',DOKU_PLUGIN.'iocexportl/templates/');
if (!defined('DOKU_PLUGIN_LATEX_TMP')) define('DOKU_PLUGIN_LATEX_TMP',DOKU_PLUGIN.'tmp/latex/');

require_once(DOKU_PLUGIN.'action.php');
//require_once(DOKU_PLUGIN.'renderer.php');

class action_plugin_iocexportl extends DokuWiki_Action_Plugin{

    var $exportallowed = false;
    var $id = '';

    function getInfo() {
        return array(
                'author' => 'Marc Català',
                'email'  => 'mcatala@ioc.cat',
                'date'   => '18-01-2011',
                'name'   => 'Export Form Plugin',
                'desc'   => 'Creates an export form to create latex document',
                'url'    => 'http://ioc.gencat.cat',
                );
    }

    function register(&$controller) {
        global $ACT;
        if ($ACT === 'show'){
            //$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE',  $this, '_jquery');
            $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'showform', array());
            $controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'counter', array());
        }
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'ioctoolbar_buttons', array ());
    }
    
		/**
         * Hook js script into page headers.
         */
        function _jquery(&$event, $param) {
        	global $ACT;
		    if ($ACT === 'show'){ 
				   $event->data['script'][] = array(
				                        'type'    => 'text/javascript',
				                        'charset' => 'utf-8',
				                        '_data'   => '',
				                        'src'     => $this->getConf('jquery_url'));
			}
	    }
   
    function showform(&$event){
	    global $conf;
        global $INFO;

		$this->id = getID();
        $this->exportallowed = (isset($conf['plugin']['iocexportl']['allowexport']) && $conf['plugin']['iocexportl']['allowexport']);
        if (!$this->isExportPage()) return false;
        if ($event->data != 'show') return false;
        if (!$INFO['writable']) return false;
        if (!$this->checkPerms()) return false;
        //Always admin can export
        if ($this->exportallowed || auth_isadmin()){
	        echo $this->getform();
        }
        return true;
    }

    function counter(&$event) {
        if ($this->checkPerms() && $this->showcounts()){
            echo '<script type="text/javascript" src="'.DOKU_BASE.'lib/plugins/iocexportl/lib/lib.js"></script>';
        }
    }

   
    function showcounts(){
        global $conf;
        $this->id = getID();
        $counter = (isset($conf['plugin']['iocexportl']['counter']) && $conf['plugin']['iocexportl']['counter']);
        return $counter && preg_match('/:(pdfindex|htmlindex)$/', $this->id, $matches);
    }
    
    function checkPerms() {
        global $ID;
        global $USERINFO;
        //$QUERY = trim($_REQUEST['id']);
        $ID    = getID();
        $user = $_SERVER['REMOTE_USER'];
        $groups = $USERINFO['grps'];
        $aclLevel = auth_aclcheck($ID,$user,$groups);
        // AUTH_ADMIN, AUTH_READ,AUTH_EDIT,AUTH_CREATE,AUTH_UPLOAD,AUTH_DELETE
        return ($aclLevel >=  AUTH_UPLOAD);
      }
    
    function isExportPage(){
        return preg_match('/:pdfindex$/', $this->id, $matches);        
/*        $file = wikiFN($this->id);
        if (@file_exists($file)) {
            $data = io_grep($file,'/^~~EXPORTFORM[^\r\n]*?~~/',0,true);
            return !empty($data);
        }
        return false;*/
    }

    function getform(){
        $ret  = "<br /><br />";
        $ret .= "<div class=\"bar\">\n";
        $ret .= "<strong>Exportació IOC: </strong>";
	    $ret .= " <form action=\"lib/plugins/iocexportl/generate.php\" id=\"export__form\" method=\"post\" >\n";
	    $checked = 'checked="checked"';
	    if(auth_isadmin()){
	        $ret .= "  <input type=\"radio\" name=\"mode\" value=\"zip\" checked=\"checked\" /> Zip";
	        $checked = '';
	    }
        $ret .= "  <input type=\"radio\" name=\"mode\" value=\"pdf\" $checked /> PDF";
        $ret .= "  <input type=\"hidden\" name=\"pageid\" value=\"".$this->id."\" />";
        $ret .= "  <input type=\"submit\" name=\"submit\" id=\"id_submit\" value=\"Exporta\" class=\"button\" />\n";
	    $ret .= " </form>\n";
	    $ret .= "<span id=\"exportacio\"></span>";
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
                                'open'   => '::figure:\n',
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
                                'open'   => '::table:\n  :id:\n',
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
                        ),
            'block'  => true,
        );
    }
}
