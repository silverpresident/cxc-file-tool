<?php
/**
 * @version 20190415.11
 */

if (!defined('APP_LOADED')) die('Direct file access is not allowed');
 
defined('DATA_DIR') OR define('DATA_DIR', __DIR__ . DIRECTORY_SEPARATOR .'d');
defined('FINAL_DIR') OR define('FINAL_DIR', __DIR__ . DIRECTORY_SEPARATOR .'f');
defined('SEQ_DIR') OR define('SEQ_DIR', __DIR__ . DIRECTORY_SEPARATOR .'f');
@mkdir(DATA_DIR);
@mkdir(FINAL_DIR);
@mkdir(SEQ_DIR);

$csec_subjects = [
'01254090' =>'Additional Mathematics',
'01210090' =>'Caribbean History',
'01216090' =>'Economics',
'01251090' =>'Electronic Document Preparation and Management',
'01218090' =>'English A',
'01219090' =>'English B',
'01225090' =>'Geography',
'01253090' =>'Human and Social Biology',
'01229090' =>'Information Technology (ZIP files permitted)',
'01234090' =>'Mathematics',
'01237090' =>'Office Administration',
'01252090' =>'Physical Education and Sport',
'01239090' =>'Principles of Accounts',
'01240090' =>'Principles of Business',
'01241090' =>'Religious Education',
'01243090' =>'Social Studies',
'01248090' =>'Theatre Arts',
 
'02101090' =>'Accounting Unit 1',
'02201090' =>'Accounting Unit 2',
'02102090' =>'Agricultural Science Unit 1',
 '02202090' =>'Agricultural Science Unit 2',
'02142090' =>'Animation and Game Design (ZIP files permitted) Unit 1',
'02242090' =>'Animation and Game Design (ZIP files permitted) Unit 2',
'02105090' =>'Applied Mathematics Unit 1',
'02205090' =>'Applied Mathematics Unit 2',
'02111090' =>'Caribbean Studies Unit 1',
'02114090' =>'Communication Studies Unit 1',
'02115090' =>'Computer Science Unit 1',
'02215090' =>'Computer Science Unit 2',
'02159090' =>'Digital Media (ZIP files permitted) Unit 1',
 '02259090' =>'Digital Media (ZIP files permitted) Unit 2',
'02116090' =>'Economics Unit 1',
'02216090' =>'Economics Unit 2',
'02160090' =>'Entrepreneurship Unit 1',
'02260090' =>'Entrepreneurship Unit 2',
'02120090' =>'Environmental Science Unit 1',
'02220090' =>'Environmental Science Unit 2',
'02172090' =>'Financial Services Unit 1',
'02272090' =>'Financial Services Unit 2',
'02121090' =>'Food and Nutrition Unit 1',
'02221090' =>'Food and Nutrition Unit 2',
'02122090' =>'French Unit 1',
'02222090' =>'French Unit 2',
'02125090' =>'Geography Unit 1',
'02225090' =>'Geography Unit 2',
'02165090' =>'Green Engineering Unit 1',
'02265090' =>'Green Engineering Unit 2',
'02127090' =>'History Unit 1',
'02227090' =>'History Unit 2',
'02129090' =>'Information Technology Unit 1',
'02229090' =>'Information Technology Unit 2',
'02167090' =>'Integrated Mathematics',
'02131090' =>'Law Unit 1',
'02231090' =>'Law Unit 2',
'02132090' =>'Literatures In English Unit 1',
'02232090' =>'Literatures In English Unit 2',
'02166090' =>'Logistics and Supply Chain Operations Unit 1',
'02266090' =>'Logistics and Supply Chain Operations Unit 2',
'02133090' => 'Management of Business Unit 1',
'02233090' => 'Management of Business Unit 2',
'02134090' => 'Pure Mathematics Unit 1',
'02234090' => 'Pure Mathematics Unit 2',
'02144090' => 'Sociology Unit 1',
'02244090' => 'Sociology Unit 2',
'02145090' => 'Spanish Unit 1',
'02245090' => 'Spanish Unit 2',
'02163090' => 'Tourism Unit 1',
'02263090' => 'Tourism Unit 2',
];


function split_name($basename){
    $nm=[];
    $nm['mc'] = substr($basename,10,8);
    $nm['cand'] = substr($basename,0,10);
    $nm['centre'] = substr($basename,0,6);
    $nm['remainder'] = substr($basename,18);
       
    return $nm;
}
function csvToArray($csvFile){
    $file_to_read = fopen($csvFile, 'r');
    while (!feof($file_to_read) ) {
        $lines[] = fgetcsv($file_to_read, 100, ',');
    }
    fclose($file_to_read);
    return $lines;
}