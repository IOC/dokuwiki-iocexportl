<?php
/**
 * LaTeX Plugin: Calculate characters
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Marc Català <mcatala@ioc.cat>
 */

if (!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
                    
require_once(DOKU_INC.'/inc/init.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');

$id = getID(); 
if (!checkPerms()) return false;
 $path = wikiFN($id);
 session_start();
 countCharacters($path);
 session_destroy();

    function countCharacters($path){
        global $id;

        if (file_exists($path)){
            $text = io_readFile($path);
            $text = preg_replace('/<noprint>\n?<noweb>\n?(<verd>.*?<\/verd>)\n?<\/noweb>\n?<\/noprint>/', '$1',$text);
            $instructions = get_latex_instructions($text);
            $clean_text = p_latex_render('ioccounter', $instructions, &$info);
            if (preg_match('/::IOCVERDINICI::/', $clean_text)){
                //print_r($clean_text);
                $matches = array();
                preg_match_all('/(?<=::IOCVERDINICI::)(.*?)(?=::IOCVERDFINAL::)/', $clean_text, $matches, PREG_SET_ORDER);
                $verd = '';
                foreach ($matches as $m){
                    $verd .= $m[1];
                }
                $noverd = preg_replace('/::IOCVERDINICI::.*?::IOCVERDFINAL::/', '', $clean_text);
                $result = array($id, mb_strlen($noverd), 'Material de reaprofitament', mb_strlen($verd), 'Material de nova creació');
            }else{
                $result = array($id, mb_strlen($clean_text));
            }
        }else{
            $result = null;
            $result = 'ERROR';
        }
        echo json_encode($result);
    }
    
    
    function checkPerms() {
        global $id;
        global $USERINFO;

        $user = $_SERVER['REMOTE_USER'];
        $groups = $USERINFO['grps'];
        $aclLevel = auth_aclcheck($id,$user,$groups);
        // AUTH_ADMIN, AUTH_READ,AUTH_EDIT,AUTH_CREATE,AUTH_UPLOAD,AUTH_DELETE
        return ($aclLevel >=  AUTH_UPLOAD);
      }
