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

    static $vimeo = 'http://www.vimeo.com/moogaloop.swf?clip_id=@VIDEO@';
    static $youtube = 'http://www.youtube.com/v/@VIDEO@?allowFullScreen=true&allowScriptAccess=always&fs=1';

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
        return 'substition';
    }

    function getPType(){
        return 'block';
    } //stack, block, normal

    function getSort(){
        return 318; //{{uri}} dokuwiki has 320 priority
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{\s?(?:vimeo|youtube).*?>[^}]+\}\}', $mode, 'plugin_iocexportl_iocmedia');
    }

    /**
     * Handle the match
     */

    function handle($match, $state, $pos, &$handler){
        // remove {{ }}
        $command = substr($match,2,-2);

        // title
        list($command,$title) = explode('|',$command);
        $title = trim($title);

        $command = trim($command);

        // get site and video
        list($site,$url) = explode('>',$command);

        // what size?
        list($url,$param) = explode('?',$url,2);
        if(preg_match('/(\d+)x(\d+)/i',$param,$m)){
            // custom
            $width  = $m[1];
            $height = $m[2];
        }elseif(strpos($param,'small') !== false){
            // small
            $width  = 255;
            $height = 210;
        }elseif(strpos($param,'large') !== false){
            // large
            $width  = 520;
            $height = 406;
        }else{                                          // medium
            $width  = 425;
            $height = 350;
        }

        return array($site, $url, $title, $width, $height);
    }

   /**
    * output
    */
    function render($mode, &$renderer, $data) {
        if ($mode === 'iocexportl'){
            list($site, $url, $title) = $data;
            if($site === 'vimeo' || $site === 'youtube'){
                $_SESSION['qrcode'] = TRUE;
                $type = ($site === 'vimeo')?self::$vimeo:self::$youtube;
                $url = preg_replace('/@VIDEO@/', $url, $type);
                qrcode_media_url($renderer, $url, $title, $site);
            }
            return TRUE;
        }elseif ($mode === 'ioccounter'){
            $renderer->doc .= $title;
        }elseif ($mode === 'xhtml' || $mode === 'iocxhtml'){
            list($site, $url, $title, $width, $height) = $data;
            if($site === 'vimeo' || $site === 'youtube'){
                $type = ($site === 'vimeo')?self::$vimeo:self::$youtube;
                $url = preg_replace('/@VIDEO@/', $url, $type);
            }
            $renderer->doc .= '<div class="mediavideo">';
            $renderer->doc .= html_flashobject(
                            $url,
                            $width,
                            $height);
            $renderer->doc .= '</div>';
        }
        return FALSE;
    }
}
