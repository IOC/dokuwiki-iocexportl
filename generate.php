<?php

if (!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../../');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if (!defined('DOKU_PLUGIN_TEMPLATES')) define('DOKU_PLUGIN_TEMPLATES',DOKU_PLUGIN.'iocexportl/templates/');
if (!defined('DOKU_PLUGIN_LATEX_TMP')) define('DOKU_PLUGIN_LATEX_TMP',DOKU_PLUGIN.'tmp/latex/');

require_once(DOKU_INC.'/inc/init.php');
require_once(DOKU_PLUGIN.'iocexportl/lib/renderlib.php');

global $conf;

$id = getID();
$unitzero = false;
$tmp_dir = '';
$media_path = 'lib/exe/fetch.php?media=';
$exportallowed = false;
$img_src = array('familyicon_electronica.png', 'familyicon_infantil.png', 'familyicon_informatica.png');

if (!checkPerms()) return false;

$exportallowed = isset($conf['plugin']['iocexportl']['allowexport']);
if (!$exportallowed && !auth_isadmin()) return false;

$time_start = microtime(true);

$output_filename = str_replace(':','_',$id);
if ($_POST['mode'] !== 'zip' && $_POST['mode'] !== 'pdf' ) return false;
if (!auth_isadmin() && $_POST['mode'] === 'zip') return false;

if (file_exists(DOKU_PLUGIN_TEMPLATES.'header.ltx')){
    //read header
    $latex = io_readFile(DOKU_PLUGIN_TEMPLATES.'header.ltx');
    session_start();
    $tmp_dir = rand();
    $_SESSION['tmp_dir'] = $tmp_dir;
    if (!file_exists(DOKU_PLUGIN_LATEX_TMP.$tmp_dir)){
        mkdir(DOKU_PLUGIN_LATEX_TMP.$tmp_dir, 0775, true);
        mkdir(DOKU_PLUGIN_LATEX_TMP.$tmp_dir.'/media', 0775, true);
    }
    if (!auth_isadmin()){
        $latex .= '\draft{Provisional}' . DOKU_LF;
        $_SESSION['draft'] = true;
    }
    if (!file_exists(DOKU_PLUGIN_TEMPLATES.'frontpage.ltx')){
        session_destroy();
        return false;                
    }
    //get all pages and activitites
    $data = getData();
    //FrontPage
    renderFrontpage($latex, $data);
    $latex .= '\frontpageparskip'.DOKU_LF;
    $_SESSION['createbook'] = true;
    //Render a non unit zero
    if (!$unitzero){
        $_SESSION['chapter'] = 1;
        //Intro
        foreach ($data[0]['intro'] as $page){
            $text = io_readFile(wikiFN($page));
            $instructions = get_latex_instructions($text);
            $latex .= p_render('iocexportl', $instructions, $info);
        }
        //Content
        foreach ($data[0]['pageid'] as $page){
            $text = io_readFile(wikiFN($page));
            $instructions = get_latex_instructions($text);
            $latex .= p_render('iocexportl', $instructions, $info);
            //render activities
            if (array_key_exists($page, $data[0]['activities'])){
                $_SESSION['activities'] = true;
                foreach ($data[0]['activities'][$page] as $act){
                    $text = io_readFile(wikiFN($act));
                    $instructions = get_latex_instructions($text);
                    $latex .= p_render('iocexportl', $instructions, $info);
                }
                $_SESSION['activities'] = false;
            }
        }
    }else{//Render unit zero
        $_SESSION['u0'] = true;
        $text = io_readFile(wikiFN($id));
        $text = preg_replace('/(\={6} .*? \={6}\n{2,}\={5} Meta \={5}\n{2,}( {2,4}\* \*\*\w+\*\*:.*\n?)+)/', '', $text);
        preg_match('/(?<=\={5} Credits \={5})\n+(.*?\n?)+(?=\={5} copyright \={5})/', $text, $matches);
        if (isset($matches[0])){
            $latex .= '\creditspacingpar\scriptsize\credits' . DOKU_LF;
            $matches[0] = preg_replace('/^\n+/', '', $matches[0]);
            $matches[0] = preg_replace('/\n{2,3}/', '@IOCBR@', $matches[0]);
            $instructions = get_latex_instructions($matches[0]);
            $latex .= p_render('iocexportl', $instructions, $info);
            $latex = preg_replace('/@IOCBR@/', DOKU_LF.DOKU_LF.'\vspace*{5mm} ', $latex);
            $text = preg_replace('/(\={5} Credits \={5}\n{2,}(.*?\n?)+)(?=\={5} copyright \={5})/', '', $text);
            preg_match('/(?<=\={5} copyright \={5})\n+(.*?\n?)+\{\{[^\}]+\}\}/', $text, $matches);
            if (isset($matches[0])){
                $latex .= '\vfill'.DOKU_LF;
                $instructions = get_latex_instructions($matches[0]);
                $latex .= p_render('iocexportl', $instructions, $info);
                $text = preg_replace('/\={5} copyright \={5}\n+(.*?\n?)+\{\{[^\}]+\}\}\n+/', '', $text);
                preg_match('/(.*?\n)+.*?http.*?\n+(?=\={6} .*? \={6})/', $text, $matches);
                if (isset($matches[0])){
                    $latex .= '\renewcommand{\baselinestretch}{1.9}\tiny'.DOKU_LF;
                    $matches[0] = preg_replace('/(http.*)/', DOKU_LF.DOKU_LF.'$1', $matches[0]);
                    $instructions = get_latex_instructions($matches[0]);
                    $latex .= p_render('iocexportl', $instructions, $info);
                    $text = preg_replace('/(.*?\n)+.*?http.*?\n+(?=\={6} .*? \={6})/', '', $text);
                }
            }
        }
        $latex .= '\restoregeometry' . DOKU_LF;
        $latex .= '\defaultspacingpar\defaultspacingline' . DOKU_LF;
        $latex .= '\normalfont\normalsize' . DOKU_LF;
        $instructions = get_latex_instructions($text);
        $latex .= p_render('iocexportl', $instructions, $info);
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

    function renderFrontpage(&$latex, $data){
        global $tmp_dir;
        global $unitzero;
        global $img_src;
        
        $name_length = 30;
        $credit_length = 61;
        $pos_credit = 21.4;
        if ($unitzero){
            $latex .= io_readFile(DOKU_PLUGIN_TEMPLATES.'frontpage_u0.ltx');
            $latex = preg_replace('/@IOC_EXPORT_FAMILIA@/', $data[1]['familia'], $latex);
            if (preg_match('/electricitat/i', $data[1]['familia'])){
                $family = 0;
            }elseif (preg_match('/socioculturals/', $data[1]['familia'])){
                $family = 1;
            }else{
                $family = 2;
            }
            copy(DOKU_PLUGIN.'iocexportl/templates/'.$img_src[$family], DOKU_PLUGIN_LATEX_TMP.$tmp_dir.'/media/'.$img_src[$family]);
            $latex = preg_replace('/@IOC_EXPORT_IMGFAMILIA@/', 'media/'.$img_src[$family], $latex);
            $latex = preg_replace('/@IOC_EXPORT_NOMCOMPLERT@/', trim($data[1]['nomcomplert']), $latex);
            $size = strlen(trim($data[1]['nomcomplert']));
            $latex = preg_replace('/@IOC_EXPORT_CREDIT@/', $data[1]['creditcodi'], $latex);
            if (strlen(trim($data[1]['creditnom'])) > $credit_length){
                $inc = 0;
            }else{
                $inc = 0.3;
            }
            $latex = preg_replace('/@IOC_EXPORT_POS_CICLENOM@/', '1cm,'.strval($pos_credit + $inc).'cm', $latex, 1);
            $latex = preg_replace('/@IOC_EXPORT_CICLENOM@/', $data[1]['ciclenom'], $latex);
        }else{
            $latex .= io_readFile(DOKU_PLUGIN_TEMPLATES.'frontpage.ltx');
            $latex = preg_replace('/@IOC_EXPORT_NOMCOMPLERT@/', trim($data[1]['nomcomplert']), $latex);
            $size = strlen(trim($data[1]['nomcomplert']));
            $latex = preg_replace('/@IOC_EXPORT_AUTOR@/', $data[1]['autor'], $latex, 1);
            if (strlen(trim($data[1]['creditnom'])) > $credit_length){
                $inc = 0;
            }else{
                $inc = 0.3;
            }
            $latex = preg_replace('/@IOC_EXPORT_POS_CREDITNOM@/', '1cm,'.strval($pos_credit + $inc).'cm', $latex, 1);
            $latex = preg_replace('/@IOC_EXPORT_CREDIT@/', $data[1]['creditnom'], $latex);
        }
    }
    
    function createLatex($filename, $path, &$text){
        //Replace media relative URI's for absolute URI's
        $text = preg_replace('/\{media\//', '{'.$path.'/media/', $text);
        io_saveFile($path.'/'.$filename.'.tex', $text);
        $shell_escape = '';
        if ($_SESSION['qrcode']){
            $shell_escape = '-shell-escape';
        }
        exec('cd '.$path.' && pdflatex '.$shell_escape.' -halt-on-error ' .$filename.'.tex' , $sortida, $result);
        if ($result === 0){
            exec('cd '.$path.' && pdflatex '.$shell_escape.' -halt-on-error ' .$filename.'.tex' , $sortida, $result);
            //One more to calculate correctly size tables
            exec('cd '.$path.' && pdflatex '.$shell_escape.' -halt-on-error ' .$filename.'.tex' , $sortida, $result);
        }
        if ($result !== 0){
            showLogError($path, $filename);
        }else{
            returnData($path, $filename.'.pdf', 'pdf');
        }
    }
    
    function returnData($path, $filename, $type){
        global $id;
        global $media_path;
        global $conf;
        global $time_start;
        if (file_exists($path.'/'.$filename)){
            //Return pdf number pages
            if ($type === 'pdf'){
                $num_pages = exec("pdfinfo " . $path . "/" . $filename . " | awk '/Pages/ {print $2}'");
            }
            $filesize = filesize($path . "/" . $filename);
            $filesize = filesize_h($filesize);
            $dest = preg_replace('/:/', '/', $id);
            $dest = dirname($dest);
            if (!file_exists($conf['mediadir'].'/'.$dest)){
                mkdir($conf['mediadir'].'/'.$dest, 0755, true);
            }
            $filename_dest = (auth_isadmin())?$filename:basename($filename, '.'.$type).'_draft.'.$type;
            copy($path.'/'.$filename, $conf['mediadir'].'/'.$dest .'/'.$filename_dest);                
            $dest = preg_replace('/\//', ':', $dest);
            $time_end = microtime(true);
            $time = round($time_end - $time_start, 2);
            if ($type === 'pdf'){
                $result = array($type, $media_path.$dest.':'.$filename_dest.'&time='.gettimeofday(true), $filename_dest, $filesize, $num_pages, $time);
            }else{
                $result = array($type, $media_path.$dest.':'.$filename_dest.'&time='.gettimeofday(true), $filename_dest, $filesize, $time);
            } 
        }else{
            $result = 'Error en la creació del arixu: ' . $filename;
        }
        echo json_encode($result);
    }
    
    function createZip($filename,$path,&$text){
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
            showLogError($filename);
        }
    }
    
    function showLogError($path, $filename){
        global $tmp_dir;
        global $conf;
        $output = array();
        
        if(auth_isadmin()){
            returnData($path, $filename.'.log', 'log');
        }else{
            exec('tail -n 20 '.$path.'/'.$filename.'.log;', $output);
            io_saveFile($path.'/'.filename.'.log', implode(DOKU_LF, $output));
            returnData($path, $filename.'.log', 'log');
        }
    }
    
    function getFiles($directory, &$files){
        if(!file_exists($directory) || !is_dir($directory)) {
                return false;
        } elseif(!is_readable($directory)) {
            return false;
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

            return true;
        }
    }
    
	/**
     * Remove specified dir 
     */   
    function removeDir($directory) {
        if(!file_exists($directory) || !is_dir($directory)) { 
            return false; 
        } elseif(!is_readable($directory)) { 
            return false; 
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
                    return false; 
                } 
            } 
            return true; 
        }
    }
    
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
    
    function getPageNames(&$data, $struct = false){
        global $id;        
        global $conf;

        $data['intro'] = array();
        $data['pageid'] = array();
        if (!$struct){
            $exists = false;
            $file = wikiFN($id);
            if (@file_exists($file)) {
                $matches = array();
                $txt =  io_readFile($file);
                preg_match_all('/(?<=\={5} toc \={5})\n+(\s{2,4}\*\s\[\[.*?\]\]\n?)+/i', $txt, $matches);
                $pages = implode('\n', $matches[0]);
                //get exercises and activities
                $pages = getActivities($data, $pages);
                preg_match_all('/\[\[([^|]+).*?\]\]+/', $pages, $matches);
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
    
    function getActivities(&$data, $pages){
        global $id;
        
        $matches = array();
        $data['activities'] = array();
        //return all pages with activities
        preg_match_all('/\s{2}\*\s\[\[.*?\]\]\n(\s{4}\*\s\[\[.*?\]\]\n?)+/', $pages, $matches);
        foreach ($matches[0] as $match){
            //return page namespace
            preg_match('/\s{2}\*\s\[\[([^|]+).*?\]\]/', $match, $ret);
            if (!isset($ret[1])){
                continue;
            }else{
                $masterid = $ret[1];
                resolve_pageid(getNS($id),$masterid,$exists);
                //return all activities for active page
                preg_match_all('/\s{4}\*\s\[\[([^|]+).*?\]\]/', $match, $ret);
                foreach ($ret[1] as $r){
                    if (!isset($data['activities'][$masterid])){
                        $data['activities'][$masterid] = array();
                    }
                    array_push($data['activities'][$masterid], $r);
                }
            }
        }
        //remove activities and exercises
        $pages = preg_replace('/    \*\s\[\[.*?\]\]\n?/', '', $pages);
        return $pages;
    }
    
    function getData(){
        global $id;
        global $unitzero;

        $data = array();
        $data[0] = array();
        $data[1] = array();
        $file = wikiFN($id);
        if (@file_exists($file)) {
            $info = io_grep($file,'/(?<=\={6} )[^\=]*/',0,true);
            $data[1]['nomcomplert'] = $info[0][0];
            $text = io_readFile($file);
            $info = array();
            preg_match_all('/(?<=\={5} Meta \={5}\n\n)\n*( {2,4}\* \*\*\w+\*\*:.*\n?)+/', $text, $info, PREG_SET_ORDER);
            if (!empty($info[0][0])){
                $text = $info[0][0];
                preg_match_all('/ {2,4}\* \*\*(\w+)\*\*:(.*)/m', $text, $info, PREG_SET_ORDER);
                foreach ($info as $i){
                    $key = trim($i[1]);
                    $data[1][$key] = trim($i[2]);
                }
            }
            //get page names
            if (key_exists('familia', $data[1])){
                $unitzero = true;
            }else{
                getPageNames($data[0]);
            }
            return $data;
        }
        return false;
    }
