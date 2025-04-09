<?php

/**
 * @version 20190415.11
 */
chdir(__DIR__);
define('APP_LOADED', 1);
include_once('prep.php');
include_once('user-check.php');
include_once('pre.inc');

$GET = SAP::getInstance('GET');
$POST = SAP::getInstance('POST');

if ($POST->rn_file) {
    $prefix = "{$POST->centre_num}{$POST->cand_num}{$POST->subject_code}";
    $filename = "{$POST->centre_num}{$POST->cand_num}{$POST->subject_code}-1.pdf";
    $file1 = DATA_DIR .  DIRECTORY_SEPARATOR . $POST->cover;
    if ($POST->cover) {
        $outputNameA = FINAL_DIR .  DIRECTORY_SEPARATOR . "{$prefix}CS.pdf";
        rename($file1, $outputNameA);
    }
    if ($POST->project) {
        $x = explode('.', $POST->project);
        $c = count($x) - 1;
        $ext = $x[$c];
        $file2 = DATA_DIR .  DIRECTORY_SEPARATOR . $POST->project;
        $outputNameA = FINAL_DIR .  DIRECTORY_SEPARATOR . "{$prefix}-1.{$ext}";
        rename($file2, $outputNameA);
    }
    if ($POST->markscheme) {
        $x = explode('.', $POST->markscheme);
        $c = count($x) - 1;
        $ext = $x[$c];
        $file2 = DATA_DIR .  DIRECTORY_SEPARATOR . $POST->markscheme;
        $outputNameA = FINAL_DIR .  DIRECTORY_SEPARATOR . "{$prefix}MS.{$ext}";
        rename($file2, $outputNameA);
    }
    Header('Location: ' . LOCUS);
    die();
}
if ($POST->make_file) {
    // __Er($POST);

    $prefix = "{$POST->centre_num}{$POST->cand_num}{$POST->subject_code}";
    $filename = "{$POST->centre_num}{$POST->cand_num}{$POST->subject_code}-1.pdf";
    $file1 = DATA_DIR .  DIRECTORY_SEPARATOR . $POST->cover;
    $outputName = FINAL_DIR .  DIRECTORY_SEPARATOR . $filename;

    if ($POST->project || $POST->markscheme) {
        $file2 = $file3 = '';


        $PDF_MERGER = EXPDF::merger();
        $PDF_MERGER->addPDF($file1);
        if ($POST->project) {
            $file2 = DATA_DIR .  DIRECTORY_SEPARATOR . $POST->project;
            $PDF_MERGER->addPDF($file2);
        }
        if ($POST->markscheme) {
            $file3 = DATA_DIR .  DIRECTORY_SEPARATOR . $POST->markscheme;
            $PDF_MERGER->addPDF($file3);
        }

        $PDF_MERGER->merge('file', $outputName);
        unlink($file1);
        if ($file2) unlink($file2);
        if ($file3) unlink($file3);
    } else {
        $fcount = 0;
        $dirIter = ELIX::FileSystem()->getDirectoryIterator(FINAL_DIR);
        $len = strlen($prefix);
        $suffix = [];
        foreach ($dirIter as $F) {
            if ($F->isDot()) {
                continue;
            }
            if (substr($F->getbasename('.pdf'), 0, $len) === $prefix) {
                $fcount++;
                $suffix[] = substr($F->getbasename('.pdf'), $len + 1);
            }
        }
        if ($fcount) {
            $suffix[] = $fcount;
            $fcount = max($suffix);
        }
        $fcount++;
        $filename = "{$POST->centre_num}{$POST->cand_num}{$POST->subject_code}-{$fcount}.pdf";
        $outputName = FINAL_DIR .  DIRECTORY_SEPARATOR . $filename;
        rename($file1, $outputName);
    }
    Header('Location: ' . LOCUS);
    die();

    /**/

    /*$fileArray = [];
    $fileArray[] = $file1;
    $fileArray[] = $file2;
    $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$outputName ";
    
//Add each pdf file to the end of the command
foreach($fileArray as $file) {
    $cmd .= $file." ";
}
$result = shell_exec($cmd);
*/
}

$is_sequencing = $GET->sequencing;

$RHTML = SAP::getInstance('HTML');
$RHTML->html()->class('no-js');

$head = $RHTML->html()->head();
$head->Append('<script>(function(e,t,n){var r=e.querySelectorAll("html")[0];r.className=r.className.replace(/(^|\s)no-js(\s|$)/,"$1js$2")})(document,window,0);</script>');

$body = $RHTML->body();



$body->append('
<form class="box" id="upload-form" method="post" action="upload.php" enctype="multipart/form-data">
  <div class="box__input">
    <input class="box__file" type="file" name="file[]" id="file" data-multiple-caption="{count} files selected" multiple />
    <label for="file"><strong>Choose a file</strong><span class="box__dragndrop"> or drag it here</span>.</label>
    <button class="box__button" type="submit">Upload</button>
  </div>
  <div class="box__uploading">Uploading&hellip;</div>
  <div class="box__success">Done!</div>
  <div class="box__error">Error! <span></span>.</div>
</form>');


$container = $body->create('div')->class('container');


$dirIter = ELIX::FileSystem()->getDirectoryIterator(DATA_DIR);


if ($is_sequencing) {
    $form = $container->create('form')->method('post')->id('sequencing-form');
    $form->class('box_form');
    $el = $form->input('select')->name('subject_code')->class('form-control');
    $el->required(true);
    $el_csec = $el->addOptGroup('CSEC');
    $el_cape1 = $el->addOptGroup('Cape Unit 1');
    $el_cape2 = $el->addOptGroup('Cape Unit 2');
    foreach ($csec_subjects as $mcode => $name) {
        if (substr($mcode, 0, 3) == '021') {
            $el_cape1->addOption($name, $mcode);
        } elseif (substr($mcode, 0, 3) == '022') {
            $el_cape2->addOption($name, $mcode);
        } else {
            $el_csec->addOption($name, $mcode);
        }
    }

    $el = $form->input('text')->name('centre_num')->class('form-control')->value('100111')->required(true)->minlength(6)->maxlength(6)->list('dl_centre');
    $el = $form->input('text')->name('cand_num')->class('form-control')->autofucus(true)->required(true)->minlength(4)->maxlength(4)->list('dl_candno');
} else {
    $form = $container->create('form')->method('post')->id('merger-form');
    $form->class('box_form');
    $el = $form->input('select')->name('subject_code')->class('form-control');
    $el->required(true);
    $el->addOption('Select 1', '');
    $el_csec = $el->addOptGroup('CSEC');
    $el_cape1 = $el->addOptGroup('Cape Unit 1');
    $el_cape2 = $el->addOptGroup('Cape Unit 2');
    foreach ($csec_subjects as $mcode => $name) {
        if (substr($mcode, 0, 3) == '021') {
            $el_cape1->addOption($name, $mcode);
        } elseif (substr($mcode, 0, 3) == '022') {
            $el_cape2->addOption($name, $mcode);
        } else {
            $el_csec->addOption($name, $mcode);
        }
    }

    $el = $form->input('text')->name('centre_num')->class('form-control')->value('100111')->required(true)->minlength(6)->maxlength(6)->list('dl_centre');
    $el = $form->input('text')->name('cand_num')->class('form-control')->autofucus(true)->required(true)->minlength(4)->maxlength(4)->list('dl_candno');

    $fs = $form->create('fieldset');
    $fs->legend('Cover sheet');
    $el = $fs->input('select')->name('cover')->class('form-control');
    $el_pdf = $el->addOptGroup('PDF');
    $el_other = $el->addOptGroup('other');
    foreach ($dirIter as $F) {
        if ($F->isDot()) {
            continue;
        }
        $ext = strtolower(substr($F->getfilename(), -4) );
        if ($ext== '.pdf') {
            $el_pdf->addOption($F->getbasename(), $F->getfilename());
        } else {
            $el_other->addOption($F->getbasename(), $F->getfilename());
        }
    }
    $fs = $form->create('fieldset');
    $fs->legend('Project');
    $el = $fs->input('select')->name('project')->class('form-control');
    $el->addOption('Just name it', '');
    $el_pdf = $el->addOptGroup('PDF');
    $el_other = $el->addOptGroup('other');
    foreach ($dirIter as $F) {
        if ($F->isDot()) {
            continue;
        }
        $ext = strtolower(substr($F->getfilename(), -4) );
        if ($ext== '.pdf') {
            $el_pdf->addOption($F->getbasename(), $F->getfilename());
        } else {
            $el_other->addOption($F->getbasename(), $F->getfilename());
        }
    }

    $fs = $form->create('fieldset');
    $fs->legend('Markscheme');
    $el = $fs->input('select')->name('markscheme')->class('form-control');
    $el->addOption('Ignore', '');
    $el_pdf = $el->addOptGroup('PDF');
    $el_other = $el->addOptGroup('other');
    foreach ($dirIter as $F) {
        if ($F->isDot()) {
            continue;
        }
        $ext = strtolower(substr($F->getfilename(), -4) );
        if ($ext== '.pdf') {
            $el_pdf->addOption($F->getbasename(), $F->getfilename());
        } else {
            $el_other->addOption($F->getbasename(), $F->getfilename());
        }
    }
    $form->create('button')->class('btn')->type('submit')->name('rn_file')->value(1)->append('rename files');
    $form->create('button')->class('btn')->type('submit')->name('make_file')->value(1)->append('merge files');
}


$dl = $body->create('datalist')->id('dl_centre');
$dl->addOption('100111')->append('Day school');
$dl->addOption('100243')->append('Evening school');

$dl = $body->create('datalist')->id('dl_candno');
$csvFile = date('Y') . 'cand.csv';
$xlines = csvToArray($csvFile);

foreach ($xlines as $line) {
    if (empty($line[3])) {
        continue;
    }
    $dl->addOption(substr($line[3], -4))->append("{$line[1]} {$line[0]} {$line[2]} {$line[3]}");
}



$container = $body->create('div')->class('container')->id('file_list');
$GET->included = 1;
$container->Append(include('file_list.php'));

$container = $body->create('div')->class('container');
$container->create('a')
    //->target('_blank')
    ->class('btn')
    ->href($is_sequencing ? '?sequencing=0' : '?sequencing=1')
    ->append($is_sequencing ? 'Turn off sequencing' : 'Turn on sequencing');

$container->create('a')
    ->class('btn')
    ->target('_blank')
    ->href('sequence.php?delete_my_files=dmf' . $_SESSION['cxc-user-csrf'])
    ->append("Delete User File Cache");
$container->create('a')
    ->class('btn')
    ->href('?logout=1')
    ->append("log out ({$_SESSION['cxc-user']})");

$body->create('style')->append(file_get_contents('style.css'));
$body->append("<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>");
$body->create('script')->Append(file_get_contents('js.js'));
$RHTML->send();

die();
