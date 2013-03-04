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

if (!defined('DOKU_IOCEXPORT_COUNTER_TYPE_TOTAL')) 
        define('DOKU_IOCEXPORT_COUNTER_TYPE_TOTAL',0);
if (!defined('DOKU_IOCEXPORT_COUNTER_TYPE_NEWCONTENT')) 
        define('DOKU_IOCEXPORT_COUNTER_TYPE_NEWCONTENT',1);

$id = getID();
if (!checkPerms()) return FALSE;
 $path = wikiFN($id);
 session_start();
 countCharacters($path);
 session_destroy();

    /**
    *
    * Count characters for the path indicated
    * @param string $path
    */
    function countCharacters($path){
        global $id;

        if (file_exists($path)){
            $text = io_readFile($path);
            $text = preg_replace('/<noprint>\n?<noweb>\n?(<verd>.*?<\/verd>)\n?<\/noweb>\n?<\/noprint>/', '$1',$text);
            $instructions = get_latex_instructions($text);
            $clean_text = p_latex_render('ioccounter', $instructions, $info);
            if (preg_match('/::IOCVERDINICI::/', $clean_text)){
                $result['counterType'] =  DOKU_IOCEXPORT_COUNTER_TYPE_NEWCONTENT;
                $matches = array();
                preg_match_all('/(?<=::IOCVERDINICI::)(.*?)(?=::IOCVERDFINAL::)/', $clean_text, $matches, PREG_SET_ORDER);
                $newContent = '';
                foreach ($matches as $m){
                    $newContent .= $m[1];
                }
                $reusedContent = preg_replace('/::IOCVERDINICI::.*?::IOCVERDFINAL::/', '', $clean_text);
            }else if (preg_match('/::IOCNEWCONTENTINICI::/', $clean_text)){
                $result['counterType'] =  DOKU_IOCEXPORT_COUNTER_TYPE_NEWCONTENT;
                $matches = array();
                preg_match_all('/(?<=::IOCNEWCONTENTINICI::)(.*?)(?=::IOCNEWCONTENTFINAL::)/', $clean_text, $matches, PREG_SET_ORDER);
                $newContent = '';
                foreach ($matches as $m){
                    $newContent .= $m[1];
                }
                $result['newContentCounter']['tag']='de nova creació';
                $result['newContentCounter']['value']=mb_strlen($newContent);
                $reusedContent = preg_replace('/::IOCNEWCONTENTINICI::.*?::IOCNEWCONTENTFINAL::/', '', $clean_text);
                $result['reusedContentCounter']['tag']='de reaprofitament';
                $result['reusedContentCounter']['value']=mb_strlen($reusedContent)+sizeof($matches);
                $totalCounter = $result['reusedContentCounter']['value']
                                + $result['newContentCounter']['value'];
            }else{
                $result['counterType'] =  DOKU_IOCEXPORT_COUNTER_TYPE_TOTAL;               
                $totalCounter=mb_strlen($clean_text);
            }
            $result['totalCounter'] = array('tag' => 'Total',
                    'value' => $totalCounter,);
        }else{
            $result = null;
        }
        echo json_encode($result);
    }

    /**
     *
     * Check whether user has right acces level
     */
    function checkPerms() {
        global $id;
        global $USERINFO;

        $user = $_SERVER['REMOTE_USER'];
        $groups = $USERINFO['grps'];
        $aclLevel = auth_aclcheck($id,$user,$groups);
        // AUTH_ADMIN, AUTH_READ,AUTH_EDIT,AUTH_CREATE,AUTH_UPLOAD,AUTH_DELETE
        return ($aclLevel >=  AUTH_UPLOAD);
      }
