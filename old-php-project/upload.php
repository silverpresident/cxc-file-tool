<?php

/**
 * @version 20190415.11
 */
chdir(__DIR__);
define('APP_LOADED', 1);
include_once('prep.php');
include_once('user-check.php');
include_once('pre.inc');


SAP::setLogLevel('DEVELOPER');
$result = ['success' => 0, 'error' => 0, 'reload' => 0];
$FILES = SAP::getInstance('FILES');
$POST = SAP::getInstance('POST');

// __er('a=>',$_POST);
// __er('a=>',$_FILES);
if ($FILES->count()) {
    //$FILE = $FILES->get('file');
    $i = 0;
    if ($POST->is_sequencing) {
        $prefix = "{$POST->centre_num}{$POST->cand_num}{$POST->subject_code}";

        $num = get_next_seq($prefix);

        foreach ($FILES as $FILE) {

            //__er($i++,$FILE);
            if ($FILE->hasError()) {
                $result['error'] = $FILE->getErrorMessage();
            } else {
                $result['success'] = 1;
                $result['new_file_list'] = 1;
                $filename = "{$prefix}-{$num}.{$FILE->extension}";
                $FILE->save(SEQ_DIR . DIRECTORY_SEPARATOR . $filename);
                $FILE->delete();
            }
            $num++;
        }
    } else {
        foreach ($FILES as $FILE) {

            //__er($i++,$FILE);
            if ($FILE->hasError()) {
                $result['error'] = $FILE->getErrorMessage();
            } else {
                $result['success'] = 1;
                $result['reload'] = 1;
                $FILE->save(DATA_DIR . DIRECTORY_SEPARATOR . $FILE->name);
                $FILE->delete();
            }
        }
    }
}
$is_success = 0;
$error_msg = '';
die(json_encode($result));

function get_next_seq($prefix)
{
    $n = 0;
    $dirIter = ELIX::FileSystem()->getDirectoryIterator(SEQ_DIR);
    $plen = strlen($prefix);
    foreach ($dirIter as $F) {
        if (substr($F->getFilename(), 0, $plen) == $prefix) {
            $sub = substr($F->getFilename(), $plen + 1);
            $x = explode('.', $sub);
            $sub = (int)$x[0];
            if ($sub > $n) {
                $n = $sub;
            }
        }
    }
    $n++;
    return $n;
}
