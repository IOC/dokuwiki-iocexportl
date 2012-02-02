<?php
/**
 * Graphviz Syntax Plugin
 * @author     Marc Català <mcatala@ioc.cat>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');


if (!class_exists('syntax_plugin_graphviz')) return;

class syntax_plugin_iocexportl_iocgraphviz extends syntax_plugin_graphviz {

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<graphviz.*?>\n.*?\n</graphviz>',$mode,'plugin_iocexportl_iocgraphviz');
    }

    /**
     * Create output
     */
    function render($format, &$R, $data) {
        if(parent::render($format, $R, $data)){
            return true;
        }
		if($format == 'iocxhtml'){
            $lpath = '../';
            if($_SESSION['iocintro']){
                $lpath = '';
            }
            $img  = parent::_imgfile($data);
            if (!isset($_SESSION['graphviz_images'])){
                $_SESSION['graphviz_images'] = array();
            }
            array_push($_SESSION['graphviz_images'], $img);
            $_SESSION['figure'] = TRUE;
            $_SESSION['figlabel'] = '';
            $_SESSION['figtitle'] = '';
            $_SESSION['figlarge'] = FALSE;
            $_SESSION['figfooter'] = '';
            $R->doc .= '<div class="iocfigure">';
            $R->doc .= $R->_media($img);
            $R->doc .= '</div>';
            $_SESSION['figure'] = FALSE;
            $_SESSION['figlabel'] = '';
            $_SESSION['figtitle'] = '';
            $_SESSION['figlarge'] = FALSE;
            $_SESSION['figfooter'] = '';
            return true;
        }elseif($format == 'iocexportl'){
            $_SESSION['figure'] = TRUE;
            $_SESSION['figlabel'] = '';
            $_SESSION['figtitle'] = '';
            $_SESSION['figfooter'] = '';
            $src  = parent::_imgfile($data);
            $width = ($data['width'])?$data['width']:NULL;
            $height = ($data['height'])?$data['height']:NULL;
            $R->_latexAddImage($src, $width, $height);
            $_SESSION['figure'] = FALSE;
            $_SESSION['figlabel'] = '';
            $_SESSION['figtitle'] = '';
            $_SESSION['figfooter'] = '';
            return true;
        }
        return false;
    }
}
