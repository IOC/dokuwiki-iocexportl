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
        $command = substr($match,2,-2);

        // title
        list($command,$title) = explode('|',$command);
        $title = trim($title);

        $command = trim($command);

        // get site and video
        list($site,$url) = explode('>',$command);

        //remove imgb number
        $match = preg_replace('/(vimeo|youtube).*?>/', '', $match);
        return array($match, $site, $url, $title);
    }

   /**
    * output
    */
    function render($mode, &$renderer, $indata) {
        if ($mode === 'iocexportl'){
            list($data, $site, $url, $title) = $indata;
            if($site === 'vimeo' || $site === 'youtube'){
                $_SESSION['qrcode'] = TRUE;
                $type = ($site === 'vimeo')?self::$vimeo:self::$youtube;
                $data = preg_replace('/@VIDEO@/', $url, $type);
                qrcode_media_url($renderer, $data, $title, $site);
            }
            return TRUE;
        }elseif ($mode === 'ioccounter'){
            $renderer->doc .= $title;
        }elseif ($mode === 'xhtml'){
            $renderer->doc .= '<a href="'.$data.'" title="'.$title.'">'.$data.'</a>';
        }elseif ($mode === 'iocxhtml'){
            $renderer->doc .= '<a href="'.$data.'" title="'.$title.'">'.$data.'</a>';
        }
        return FALSE;
    }
}
