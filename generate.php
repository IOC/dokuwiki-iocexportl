<?php
/**
 * LaTeX Plugin: Generate Latex document
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Marc Català <mcatala@ioc.cat>
 */

if (!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_TEMPLATES')) define('DOKU_PLUGIN_TEMPLATES',DOKU_PLUGIN.'iocexportl/templates/');
if (!defined('DOKU_PLUGIN_LATEX_TMP')) define('DOKU_PLUGIN_LATEX_TMP',DOKU_PLUGIN.'tmp/latex/');

require_once(DOKU_INC.'/inc/init.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');

global $conf;

$id = getID();
$unitzero = FALSE;
$tmp_dir = '';
$media_path = 'lib/exe/fetch.php?media=';
$exportallowed = FALSE;
$meta_params = array('autoria', 'ciclenom', 'creditcodi', 'creditnom', 'familia');
$img_src = array('familyicon_administracio.png','familyicon_electronica.png', 'familyicon_infantil.png', 'familyicon_informatica.png');
$ioclanguage = array('CA' => 'catalan', 'DE' => 'german', 'EN' => 'english','ES' => 'catalan','FR' => 'frenchb','IT' => 'italian');
$ioclangcontinue = array('CA' => 'continuació', 'DE' => 'fortsetzung', 'EN' => 'continued','ES' => 'continuación','FR' => 'suite','IT' => 'continua');
//Due listings problems whith header it's necessary to replace extended characters
$ini_characters = array('á', 'é', 'í', 'ó', 'ú', 'à', 'è', 'ò', 'ï', 'ü', 'ñ', 'ç','Á', 'É', 'Í', 'Ó', 'Ú', 'À', 'È', 'Ò', 'Ï', 'Ü', 'Ñ', 'Ç');
$end_characters = array("\'{a}", "\'{e}", "\'{i}", "\'{o}", "\'{u}", "\`{a}", "\`{e}", "\`{o}", '\"{i}', '\"{u}', '\~{n}', '\c{c}', "\'{A}", "\'{E}", "\'{I}", "\'{O}", "\'{U}", "\`{A}", "\`{E}", "\`{O}", '\"{I}', '\"{U}', '\~{N}', '\c{C}');

if (!checkPerms()) return FALSE;
$exportallowed = isset($conf['plugin']['iocexportl']['allowexport']);
if (!$exportallowed && !auth_isadmin()) return FALSE;

$time_start = microtime(TRUE);

//get seccions to export
$toexport = explode(',',$_POST['toexport']);

$output_filename = str_replace(':','_',$id);
if ($_POST['mode'] !== 'zip' && $_POST['mode'] !== 'pdf') return FALSE;
if (!auth_isadmin() && $_POST['mode'] === 'zip') return FALSE;
if (file_exists(DOKU_PLUGIN_TEMPLATES.'header.ltx')){
    //read header
    $latex = io_readFile(DOKU_PLUGIN_TEMPLATES.'header.ltx');
    session_start();
    $tmp_dir = rand();
    $_SESSION['tmp_dir'] = $tmp_dir;
    if (!file_exists(DOKU_PLUGIN_LATEX_TMP.$tmp_dir)){
        mkdir(DOKU_PLUGIN_LATEX_TMP.$tmp_dir, 0775, TRUE);
        mkdir(DOKU_PLUGIN_LATEX_TMP.$tmp_dir.'/media', 0775, TRUE);
    }
    if (!auth_isadmin()){
        $latex .= '\draft{Provisional}' . DOKU_LF;
        $_SESSION['draft'] = TRUE;
    }
    if (!file_exists(DOKU_PLUGIN_TEMPLATES.'frontpage.ltx')){
        session_destroy();
        return FALSE;
    }
    //get all pages and activitites
    $data = getData();

    //FrontPage
    renderFrontpage($latex, $data);
    $latex .= '\frontpageparskip'.DOKU_LF;
    $_SESSION['createbook'] = TRUE;
    //Sets default language
    $lang = preg_replace('/\n/', '', $_POST['ioclanguage']);
    $language = empty($_POST['ioclanguage'])?$ioclanguage['CA']:$ioclanguage[$lang];
    $latex = preg_replace('/@IOCLANGUAGE@/', $language, $latex, 1);
    $latex = preg_replace('/@IOCLANGCONTINUE@/', $ioclangcontinue[$lang], $latex, 1);
    //Render a non unit zero
    if (!$unitzero){
        $_SESSION['chapter'] = 1;
        //Intro
        foreach ($data[0]['intro'] as $page){
            $text = io_readFile(wikiFN($page));
            $instructions = get_latex_instructions($text);
            $latex .= p_latex_render('iocexportl', $instructions, $info);
        }
        //Content
        foreach ($data[0]['pageid'] as $page){
            //Check whether this page has to be exported
            if (!in_array($page, $toexport)){
                continue;
            }
            $text = io_readFile(wikiFN($page));
            $instructions = get_latex_instructions($text);
            $latex .= p_latex_render('iocexportl', $instructions, $info);
            //render activities
            if (array_key_exists($page, $data[0]['activities'])){
                $_SESSION['activities'] = TRUE;
                foreach ($data[0]['activities'][$page] as $act){
                    //Check whether this page has to be exported
                    if (!in_array($act, $toexport)){
                        continue;
                    }
                    $text = io_readFile(wikiFN($act));
                    $instructions = get_latex_instructions($text);
                    $latex .= p_latex_render('iocexportl', $instructions, $info);
                }
                $_SESSION['activities'] = FALSE;
            }
        }
    }else{//Render unit zero
        $_SESSION['u0'] = TRUE;
        $text = io_readFile(wikiFN($id));
        $text = preg_replace('/(\={6} ?.*? ?\={6}\n{2,}\={5} [M|m]eta \={5}\n{2,}( {2,4}\* \*\*[^\*]+\*\*:.*\n?)+)/', '', $text);
        preg_match('/(?<=\={5} [C|c]redits \={5})\n+(.*?\n?)+(?=\={5} [C|c]opyright \={5})/', $text, $matches);
        if (isset($matches[0])){
            $latex .= '\creditspacingline\creditspacingpar\scriptsize' . DOKU_LF;
            $matches[0] = preg_replace('/^\n+/', '', $matches[0]);
            $matches[0] = preg_replace('/\n{2,3}/', DOKU_LF.'@IOCBR@'.DOKU_LF, $matches[0]);
            $instructions = get_latex_instructions($matches[0]);
            $latex .= p_latex_render('iocexportl', $instructions, $info);
            $latex = preg_replace('/@IOCBR@/', '\par\vspace{2ex} ', $latex);
            $text = preg_replace('/(\={5} [C|c]redits \={5}\n{2,}(.*?\n?)+)(?=\={5} [C|c]opyright \={5})/', '', $text);
            preg_match('/(?<=\={5} copyright \={5})\n+(.*?\n?)+\{\{[^\}]+\}\}/', $text, $matches);
            if (isset($matches[0])){
				$matches[0] = preg_replace('/\n{2,3}/', DOKU_LF.'@IOCBR@'.DOKU_LF, $matches[0]);
                $latex .= '\vfill'.DOKU_LF;
                $instructions = get_latex_instructions($matches[0]);
                $latex .= p_latex_render('iocexportl', $instructions, $info);
				$latex = preg_replace('/@IOCBR@/', '\par\vspace{2ex} ', $latex);
                $text = preg_replace('/\={5} [C|c]opyright \={5}\n+(.*?\n?)+\{\{[^\}]+\}\}\n+/', '', $text);
                preg_match('/(.*?\n)+.*?http.*?\n+(?=\={6} .*? \={6})/', $text, $matches);
                if (isset($matches[0])){
                    $latex .= '\creditspacingline\creditspacingpar\tiny\par\vspace{2ex}'.DOKU_LF.DOKU_LF;
                    $matches[0] = preg_replace('/(http.*)/', DOKU_LF.DOKU_LF.'$1', $matches[0]);
					$matches[0] = preg_replace('/\n{2,3}/', DOKU_LF.'@IOCBR@'.DOKU_LF, $matches[0]);
                    $instructions = get_latex_instructions($matches[0]);
                    $latex .= p_latex_render('iocexportl', $instructions, $info);
					$latex = preg_replace('/@IOCBR@/', '\par\vspace{2ex} ', $latex);
                    $text = preg_replace('/(.*?\n)+.*?http.*?\n+(?=\={6} .*? \={6})/', '', $text);
                }
            }
        }
        $latex .= '\restoregeometry' . DOKU_LF;
        $latex .= '\defaultspacingpar\defaultspacingline' . DOKU_LF;
        $latex .= '\normalfont\normalsize' . DOKU_LF;
        $instructions = get_latex_instructions($text);
        $latex .= p_latex_render('iocexportl', $instructions, $info);
    }
    //replace IOCQRCODE
    $qrcode = '';
    if ($_SESSION['qrcode']){
        $qrcode = '\usepackage{pst-barcode,auto-pst-pdf}';
    }
    $latex = preg_replace('/@IOCQRCODE@/', $qrcode, $latex, 1);
    session_destroy();
    //Footer
    if (file_exists(DOKU_PLUGIN_TEMPLATES.'footer.ltx')){
        $latex .= io_readFile(DOKU_PLUGIN_TEMPLATES.'footer.ltx');
    }
}
if ($_POST['mode'] === 'zip'){
    createZip($output_filename,DOKU_PLUGIN_LATEX_TMP.$tmp_dir,$latex);
}else{
    createLatex($output_filename,DOKU_PLUGIN_LATEX_TMP.$tmp_dir,$latex);
}
removeDir(DOKU_PLUGIN_LATEX_TMP.$tmp_dir);

    /**
     *
     * Render frontpage
     * @param string $latex
     * @param array $data
     */
    function renderFrontpage(&$latex, $data){
        global $tmp_dir;
        global $unitzero;
        global $img_src;
        global $ini_characters;
        global $end_characters;

        if ($unitzero){
            $latex .= io_readFile(DOKU_PLUGIN_TEMPLATES.'frontpage_u0.ltx');
            $latex = preg_replace('/@IOC_EXPORT_FAMILIA@/', $data[1]['familia'], $latex);
            if (preg_match('/administraci/i', $data[1]['familia'])){
                $family = 0;
            }elseif (preg_match('/electricitat/i', $data[1]['familia'])){
                $family = 1;
            }elseif (preg_match('/socioculturals/', $data[1]['familia'])){
                $family = 2;
            }else{
                $family = 3;
            }
            copy(DOKU_PLUGIN.'iocexportl/templates/'.$img_src[$family], DOKU_PLUGIN_LATEX_TMP.$tmp_dir.'/media/'.$img_src[$family]);
            $latex = preg_replace('/@IOC_EXPORT_IMGFAMILIA@/', 'media/'.$img_src[$family], $latex);
            $latex = preg_replace('/@IOC_EXPORT_NOMCOMPLERT@/', trim($data[1]['nomcomplert']), $latex);
            $latex = preg_replace('/@IOC_EXPORT_NOMCOMPLERT_H@/', trim(wordwrap($data[1]['nomcomplert'],77,'\break ')), $latex);
            $latex = preg_replace('/@IOC_EXPORT_CREDIT@/', $data[1]['creditcodi'], $latex);
            $latex = preg_replace('/@IOC_EXPORT_CICLENOM@/', $data[1]['ciclenom'], $latex);
        }else{
            $latex .= io_readFile(DOKU_PLUGIN_TEMPLATES.'frontpage.ltx');
            $latex = preg_replace('/@IOC_EXPORT_NOMCOMPLERT@/', trim($data[1]['nomcomplert']), $latex);
            $header_nomcomplert = str_replace($ini_characters, $end_characters, $data[1]['nomcomplert']);
            $latex = preg_replace('/@IOC_EXPORT_NOMCOMPLERT_H@/', trim(wordwrap($header_nomcomplert,77,'\break ')), $latex);
            $latex = preg_replace('/@IOC_EXPORT_AUTOR@/', $data[1]['autoria'], $latex, 1);
            if (!isset($data[1]['extra'])){
                $data[1]['extra'] = '';
            }
            $latex = preg_replace('/@IOC_EXPORT_EXTRA@/', $data[1]['extra'], $latex, 1);
            $header_creditnom = str_replace($ini_characters, $end_characters, $data[1]['creditnom']);
            $latex = preg_replace('/@IOC_EXPORT_CREDIT@/', $header_creditnom, $latex);
        }
    }

    /**
     *
     * Compile latex document to create a pdf file
     * @param string $filename
     * @param string $path
     * @param string $text
     */
    function createLatex($filename, $path, &$text){
        //Replace media relative URI's for absolute URI's
        $text = preg_replace('/\{media\//', '{'.$path.'/media/', $text);
        io_saveFile($path.'/'.$filename.'.tex', $text);
        $shell_escape = '';
        if ($_SESSION['qrcode']){
            $shell_escape = '-shell-escape';
        }
        @exec('cd '.$path.' && pdflatex -draftmode '.$shell_escape.' -halt-on-error ' .$filename.'.tex' , $sortida, $result);
        if ($result === 0){
            @exec('cd '.$path.' && pdflatex -draftmode '.$shell_escape.' -halt-on-error ' .$filename.'.tex' , $sortida, $result);
            //One more to calculate correctly size tables
            @exec('cd '.$path.' && pdflatex '.$shell_escape.' -halt-on-error ' .$filename.'.tex' , $sortida, $result);
        }
        if ($result !== 0){
            getLogError($path, $filename);
        }else{
            returnData($path, $filename.'.pdf', 'pdf');
        }
    }

    /**
     *
     * Returns pdf/zip file info
     * @param string $path
     * @param string $filename
     * @param string $type
     */
    function returnData($path, $filename, $type){
        global $id;
        global $media_path;
        global $conf;
        global $time_start;

        if (file_exists($path.'/'.$filename)){
            $error = '';
            //Return pdf number pages
            if ($type === 'pdf'){
                $num_pages = @exec("pdfinfo " . $path . "/" . $filename . " | awk '/Pages/ {print $2}'");
            }
            $filesize = filesize($path . "/" . $filename);
            $filesize = filesize_h($filesize);
            $dest = preg_replace('/:/', '/', $id);
            $dest = dirname($dest);
            if (!file_exists($conf['mediadir'].'/'.$dest)){
                mkdir($conf['mediadir'].'/'.$dest, 0755, TRUE);
            }
            $filename_dest = (auth_isadmin())?$filename:basename($filename, '.'.$type).'_draft.'.$type;
            //Replace log extension to txt, and show where error is
            if ($type === 'log'){
                $filename_dest = preg_replace('/\.log$/', '.txt', $filename_dest, 1);
                $error = io_grep($path.'/'.$filename, '/^!/', 1);
                $line = io_grep($path.'/'.$filename, '/^l.\d+/', 1);
                preg_match('/\d+/', $line[0], $matches);
                $error = preg_replace('/!/', '('.$matches[0].') ', $error);
            }
            copy($path.'/'.$filename, $conf['mediadir'].'/'.$dest .'/'.$filename_dest);
            $dest = preg_replace('/\//', ':', $dest);
            $time_end = microtime(TRUE);
            $time = round($time_end - $time_start, 2);
            if ($type === 'pdf'){
                $result = array($type, $media_path.$dest.':'.$filename_dest.'&time='.gettimeofday(TRUE), $filename_dest, $filesize, $num_pages, $time);
            }else{
                $result = array($type, $media_path.$dest.':'.$filename_dest.'&time='.gettimeofday(TRUE), $filename_dest, $filesize, $time, $error);
            }
        }else{
            $result = 'Error en la creació del arixu: ' . $filename;
        }
        echo json_encode($result);
    }

    /**
     *
     * Create a zip file with tex file and all media files
     * @param string $filename
     * @param string $path
     * @param string $text
     */
    function createZip($filename, $path, &$text){
        $zip = new ZipArchive;
        $res = $zip->open($path.'/'.$filename.'.zip', ZipArchive::CREATE);
        if ($res === TRUE) {
            $zip->addFromString($filename.'.tex', $text);
            $zip->addEmptyDir('media');
            $files = array();
            getFiles($path.'/media', $files);
            foreach($files as $f){
                $zip->addFile($f, 'media/'.basename($f));
            }
            $zip->close();
            returnData($path, $filename.'.zip', 'zip');
        }else{
            getLogError($filename);
        }
    }

    /**
     *
     * Returns log file on latex compilation
     * @param string $path
     * @param string $filename
     */
    function getLogError($path, $filename){
        global $tmp_dir;
        global $conf;
        $output = array();

        if(auth_isadmin()){
            returnData($path, $filename.'.log', 'log');
        }else{
            @exec('tail -n 20 '.$path.'/'.$filename.'.log;', $output);
            io_saveFile($path.'/'.filename.'.log', implode(DOKU_LF, $output));
            returnData($path, $filename.'.log', 'log');
        }
    }

    /**
     *
     * Fill files var with all media files stored on directory var
     * @param string $directory
     * @param string $files
     */
    function getFiles($directory, &$files){
        if(!file_exists($directory) || !is_dir($directory)) {
                return FALSE;
        } elseif(!is_readable($directory)) {
            return FALSE;
        } else {
            $directoryHandle = opendir($directory);
            while ($contents = readdir($directoryHandle)) {
                if($contents != '.' && $contents != '..') {
                    if (preg_match('/.*?\.pdf|.*?\.png|.*?\.jpg/', $contents)){
                        $path = $directory . "/" . $contents;
                        if(!is_dir($path)) {
                            array_push($files, $path);
                        }
                    }
                }
            }
            closedir($directoryHandle);
            return TRUE;
        }
    }

	/**
	 *
     * Remove specified dir
     * @param string $directory
     */
    function removeDir($directory) {
        if(!file_exists($directory) || !is_dir($directory)) {
            return FALSE;
        } elseif(!is_readable($directory)) {
            return FALSE;
        } else {
            $directoryHandle = opendir($directory);

            while ($contents = readdir($directoryHandle)) {
                if($contents != '.' && $contents != '..') {
                    $path = $directory . "/" . $contents;

                    if(is_dir($path)) {
                        removeDir($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            closedir($directoryHandle);

            if(file_exists($directory)) {
                if(!rmdir($directory)) {
                    return FALSE;
                }
            }
            return TRUE;
        }
    }

    /**
     *
     * Check whether user has right acces level
     */
    function checkPerms() {
        global $ID;
        global $USERINFO;
        $ID = getID();
        $user = $_SERVER['REMOTE_USER'];
        $groups = $USERINFO['grps'];
        $aclLevel = auth_aclcheck($ID,$user,$groups);
        // AUTH_ADMIN, AUTH_READ,AUTH_EDIT,AUTH_CREATE,AUTH_UPLOAD,AUTH_DELETE
        return ($aclLevel >=  AUTH_UPLOAD);
      }

    /**
     *
     * Fill data var with wiki pages using a customized structure
     * @param array $data
     * @param boolean $struct
     */
    function getPageNames(&$data, $struct = FALSE){
        global $id;
        global $conf;

        $data['intro'] = array();
        $data['pageid'] = array();
        if (!$struct){
            $exists = FALSE;
            $file = wikiFN($id);
            if (@file_exists($file)) {
                $matches = array();
                $txt =  io_readFile($file);
                preg_match_all('/(?<=\={5} [T|t]oc \={5})\n+(\s{2,4}\*\s+\[\[[^\]]+\]\] *\n?)+/i', $txt, $matches);
                $pages = implode('\n', $matches[0]);
                //get exercises and activities
                $pages = getActivities($data, $pages);
                preg_match_all('/\[\[([^|]+).*?\]\] */', $pages, $matches);
                $counter = 0;
                foreach ($matches[1] as $page){
                    resolve_pageid(getNS($id),$page,$exists);
                    if ($exists){
                        if ($counter < 2){
                            array_push($data['intro'], $page);
                        }else{
                            array_push($data['pageid'], $page);
                        }
                    }
                    $counter += 1;
                }
            }
        }else{
            $result = array();
            preg_match('/(\w+:)+pdf:\w+\b/', $id, $result);
            $ns = preg_replace('/:/' ,'/', $result[0]);
            search($result,$conf['datadir'],'search_index', null, $ns);
            foreach ($result as $pagename){
                if (is_array($pagename) && !preg_match('/:imatges/', $pagename['id'])
                    && !preg_match('/:pdfindex/', $pagename['id'])){
                    if (preg_match('/:introduccio/', $pagename['id'])){
                        $data['intro'][0] = $pagename['id'];
                    }elseif (preg_match('/:objectius/', $pagename['id'])){
                        $data['intro'][1] = $pagename['id'];
                    }else{
                        array_push($data, $pagename['id']);
                    }
                }
            }
        }
    }

    /**
     *
     * Fill data var with activities and return pages without it
     * @param array $data
     * @param string $pages
     */
    function getActivities(&$data, $pages){
        global $id;

        $matches = array();
        $data['activities'] = array();
        //return all pages with activities
        preg_match_all('/\s{2}\*\s+\[\[.*?\]\]\n(\s{4}\*\s+\[\[.*?\]\] *\n?)+/', $pages, $matches);
        foreach ($matches[0] as $match){
            //return page namespace
            preg_match('/\s{2}\*\s+\[\[([^|]+).*?\]\]/', $match, $ret);
            if (!isset($ret[1])){
                continue;
            }else{
                $masterid = $ret[1];
                resolve_pageid(getNS($id),$masterid,$exists);
                //return all activities for active page
                preg_match_all('/\s{4}\*\s+\[\[([^|]+).*?\]\]/', $match, $ret);
                foreach ($ret[1] as $r){
                    if (!isset($data['activities'][$masterid])){
                        $data['activities'][$masterid] = array();
                    }
                    array_push($data['activities'][$masterid], $r);
                }
            }
        }
        //remove activities and exercises
        $pages = preg_replace('/    \*\s+\[\[.*?\]\]\n?/', '', $pages);
        return $pages;
    }

    /**
     *
     * Get and return uri wiki pages
     */
    function getData(){
        global $id;
        global $unitzero;
        global $meta_params;

        $data = array();
        $data[0] = array();
        $data[1] = array();
        $file = wikiFN($id);
        $inf = NULL;
        if (@file_exists($file)) {
            $info = io_grep($file, '/(?<=\={6} )[^\=]*/', 0, TRUE);
            $data[1]['nomcomplert'] = $info[0][0];
            $text = io_readFile($file);
            $info = array();
            preg_match_all('/(?<=\={5} [M|m]eta \={5}\n\n)\n*( {2,4}\* \*\*.*?\*\*:.*\n?)+/', $text, $info, PREG_SET_ORDER);
            if (!empty($info[0][0])){
                $text = $info[0][0];
                preg_match_all('/ {2,4}\* (\*\*(.*?)\*\*:)(.*)/m', $text, $info, PREG_SET_ORDER);
                foreach ($info as $i){
                    $key = trim($i[2]);
                    if (in_array($key, $meta_params)){
                        $data[1][$key] = trim($i[3]);
                    }else{
                        $instructions = get_latex_instructions(trim($i[1].$i[3]));
                        $latex = p_latex_render('iocexportl', $instructions, $inf);
                        $data[1]['extra'] = $latex;
                    }
                }
            }
            //get page names
            if (key_exists('familia', $data[1])){
                $unitzero = TRUE;
            }else{
                getPageNames($data[0]);
            }
            return $data;
        }
        return FALSE;
    }
