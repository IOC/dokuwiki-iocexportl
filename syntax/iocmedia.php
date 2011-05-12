<?php
/**
 * Plugin iocmedia : manage media content
 *
 * 
 * @author     Marc Català <mcatala@ioc.cat>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version    15/02/2011
 */
 
if(!defined('DOKU_INC')) die();
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');
 

class syntax_plugin_iocexportl_iocmedia extends DokuWiki_Syntax_Plugin {

    static $vimeo = 'http://player.vimeo.com/video/@VIDEO@';
    static $youtube = 'http://www.youtube.com/watch?v=@VIDEO@';

   /**
    * Get an associative array with plugin info.
    */
    function getInfo(){
        return array(
            'author' => 'Marc Català',
            'email'  => 'mcatala@ioc.cat',
            'date'   => '2011-01-27',
            'name'   => 'IOC media Plugin',
            'desc'   => 'Plugin to parse media files',
            'url'    => 'http://ioc.gencat.cat/',
        );
    }
 
    function getType(){ 
        return 'container'; 
    }
    
    function getPType(){ 
        return 'block'; 
    } //stack, block, normal
    
    function getSort(){ 
        return 319; //{{uri}} dokuwiki has 320 priority
    }
 
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{\s?(?:imgb|vimeo|youtube).*?>[^}]+\}\}', $mode, 'plugin_iocexportl_iocmedia');
    }

    /**
     * Handle the match
     */

    function handle($match, $state, $pos, &$handler){
        $command = substr($match,2,-2);

        // title
        list($command,$title) = explode('|',$command);
        $title = trim($title);
        
        $command = trim($command);

        // get site and video
        list($site,$url) = explode('>',$command);
        
        //remove imgb number
        $site = preg_replace('/(imgb)\s*\d+/', '$1', $site);
        $match = preg_replace('/(imgb|vimeo|youtube).*?>/', '', $match);
        return array($match, $site, $url, $title);
    }

   /**
    * output
    */
    function render($mode, &$renderer, $indata) {
        if ($mode !== 'iocexportl') return FALSE;
        list($data, $site, $url, $title) = $indata;
        if ($site === 'imgb'){
            $_SESSION['imgB'] = TRUE;
            $instructions = get_latex_instructions($data);            
            $renderer->doc .= '\imgB{';
            $renderer->doc .= p_latex_render($mode, $instructions, $info);            
            $renderer->doc .= '}'.DOKU_LF;
            $_SESSION['imgB'] = FALSE;
        }elseif($site === 'vimeo' || $site === 'youtube'){
            $_SESSION['qrcode'] = TRUE;
            $type = ($site === 'vimeo')?self::$vimeo:self::$youtube;
            $data = preg_replace('/@VIDEO@/', $url, $type);
            qrcode_media_url($renderer, $data, $title, $site);
            /*
            $renderer->doc .= '\begin{mediaurl}{'.$renderer->_xmlEntities($data).'}';
            $_SESSION['video_url'] = TRUE;
            $this->doc .= '\parbox[c]{\linewidth}{\raggedright ';                   
            $renderer->_latexAddImage(DOKU_PLUGIN . 'iocexportl/templates/'.$site.'.png','32',null,null,null,$data);
            $this->doc .= '}';
            $_SESSION['video_url'] = FALSE;
            $renderer->doc .= '& \hspace{-2mm}';
            $this->doc .= '\parbox[c]{\linewidth}{\raggedright ';            
            $renderer->externallink($data,$title);
            $this->doc .= '}';
            $renderer->doc .= '\end{mediaurl}';*/
        }
        return TRUE;
    }
}
